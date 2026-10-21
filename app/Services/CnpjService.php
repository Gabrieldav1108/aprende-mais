<?php

namespace App\Services;

use App\Exceptions\CepLookupException;
use App\Exceptions\CnpjLookupException;
use App\Rules\CnpjRule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Looks up Brazilian companies (CNPJ) through the BrasilAPI CNPJ v1 endpoint.
 *
 * @see https://brasilapi.com.br/docs#tag/CNPJ
 *
 * @phpstan-type CnpjAddress array{zip_code: string|null, street: string|null, number: string|null, complement: string|null, neighborhood: string|null, city: string|null, state: string|null, ibge_code: string|null, ibge_state_code: string|null}
 * @phpstan-type Company array{cnpj: string, legal_name: string, trade_name: string|null, status: string|null, is_active: bool, opened_at: string|null, email: string|null, phones: list<string>, address: CnpjAddress}
 */
class CnpjService
{
    public function __construct(private readonly CepService $ceps) {}

    /**
     * Find the company registered under a CNPJ, or null when it is invalid or does not exist.
     *
     * Found companies are cached for a short time, since their registration may change.
     *
     * @return Company|null
     *
     * @throws CnpjLookupException when the service is unavailable.
     */
    public function lookup(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);

        if (! self::isValid($cnpj)) {
            return null;
        }

        $cacheKey = "cnpj:{$cnpj}";

        if (($cached = Cache::get($cacheKey)) !== null) {
            return $cached;
        }

        $company = $this->fetch($cnpj);

        if ($company !== null) {
            Cache::put($cacheKey, $company, now()->addHours(config('services.brasilapi.cnpj_cache_hours')));
        }

        return $company;
    }

    /**
     * Whether the given digits form a CNPJ with valid check digits.
     */
    public static function isValid(string $cnpj): bool
    {
        return CnpjRule::isValid($cnpj);
    }

    /**
     * Request the CNPJ from BrasilAPI and map the response to the company fields.
     *
     * @return Company|null
     */
    private function fetch(string $cnpj): ?array
    {
        try {
            $response = Http::baseUrl(config('services.brasilapi.url'))
                ->acceptJson()
                ->timeout(config('services.brasilapi.timeout'))
                ->get("cnpj/v1/{$cnpj}");
        } catch (ConnectionException $e) {
            throw new CnpjLookupException('Could not reach BrasilAPI.', $cnpj, $e);
        }

        // BrasilAPI answers 404 when the CNPJ is not registered and 400 when it is invalid.
        if (in_array($response->status(), [400, 404], true)) {
            return null;
        }

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new CnpjLookupException('BrasilAPI failed to look up the CNPJ.', $cnpj, $e);
        }

        $data = $response->json();

        if (! is_array($data) || blank($data['razao_social'] ?? null)) {
            throw new CnpjLookupException('BrasilAPI returned an unexpected CNPJ response.', $cnpj);
        }

        return [
            'cnpj' => $cnpj,
            'legal_name' => trim($data['razao_social']),
            'trade_name' => $this->text($data['nome_fantasia'] ?? null),
            'status' => $this->text($data['descricao_situacao_cadastral'] ?? null),
            'is_active' => (int) ($data['situacao_cadastral'] ?? 0) === 2,
            'opened_at' => $this->text($data['data_inicio_atividade'] ?? null),
            'email' => $this->email($data['email'] ?? null),
            'phones' => $this->phones($data),
            'address' => $this->address($data),
        ];
    }

    /**
     * Build the address of the company.
     *
     * The Federal Revenue stores it in upper case without accents, so street, neighborhood, city and state
     * come from the CEP lookup when it knows the CEP, and the rest keeps the registered data.
     *
     * @param  array<string, mixed>  $data
     * @return CnpjAddress
     */
    private function address(array $data): array
    {
        $zipCode = preg_replace('/\D/', '', (string) ($data['cep'] ?? '')) ?: null;
        $ibgeCode = $this->text($data['codigo_municipio_ibge'] ?? null);
        $street = trim(($this->text($data['descricao_tipo_de_logradouro'] ?? null) ?? '').' '.($this->text($data['logradouro'] ?? null) ?? ''));

        $address = [
            'zip_code' => $zipCode !== null && strlen($zipCode) === 8 ? $zipCode : null,
            'street' => $this->titleCase($street),
            'number' => $this->text($data['numero'] ?? null),
            'complement' => $this->titleCase($this->text($data['complemento'] ?? null)),
            'neighborhood' => $this->titleCase($this->text($data['bairro'] ?? null)),
            'city' => $this->titleCase($this->text($data['municipio'] ?? null)),
            'state' => ($state = $this->text($data['uf'] ?? null)) !== null ? strtoupper($state) : null,
            'ibge_code' => $ibgeCode,
            'ibge_state_code' => $ibgeCode !== null ? substr($ibgeCode, 0, 2) : null,
        ];

        if ($address['zip_code'] === null) {
            return $address;
        }

        try {
            $found = $this->ceps->lookup($address['zip_code']);
        } catch (CepLookupException $e) {
            report($e);

            return $address;
        }

        if ($found === null) {
            return $address;
        }

        return [
            ...$address,
            'street' => $found['street'] ?? $address['street'],
            'neighborhood' => $found['neighborhood'] ?? $address['neighborhood'],
            'city' => $found['city'],
            'state' => $found['state'],
            'ibge_code' => $found['ibge_code'] ?? $address['ibge_code'],
            'ibge_state_code' => $found['ibge_state_code'] ?? $address['ibge_state_code'],
        ];
    }

    /**
     * Collect the registered phones (DDD + number) that look valid.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function phones(array $data): array
    {
        return collect([$data['ddd_telefone_1'] ?? null, $data['ddd_telefone_2'] ?? null])
            ->map(fn ($phone) => preg_replace('/\D/', '', (string) $phone))
            ->filter(fn (string $phone) => strlen($phone) >= 10 && strlen($phone) <= 11)
            ->unique()
            ->values()
            ->all();
    }

    private function email(mixed $value): ?string
    {
        $email = $this->text($value);

        return $email !== null && filter_var($email, FILTER_VALIDATE_EMAIL) ? Str::lower($email) : null;
    }

    private function titleCase(?string $value): ?string
    {
        return $value !== null ? Str::title(Str::lower($value)) : null;
    }

    /**
     * Trim a text field, turning blank values into null.
     */
    private function text(mixed $value): ?string
    {
        return is_scalar($value) && filled(trim((string) $value)) ? trim((string) $value) : null;
    }
}
