<?php

namespace App\Services;

use App\Exceptions\CepLookupException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Looks up Brazilian zip codes (CEP) through the BrasilAPI CEP v2 endpoint.
 *
 * @see https://brasilapi.com.br/docs#tag/CEP-V2
 */
class CepService
{
    /**
     * Find the address of a CEP, or null when it does not exist.
     *
     * Found addresses are cached, since a CEP rarely changes.
     *
     * @return array{zip_code: string, street: string|null, neighborhood: string|null, city: string, state: string, ibge_code: string|null, ibge_state_code: string|null}|null
     *
     * @throws CepLookupException when the service is unavailable.
     */
    public function lookup(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        $cacheKey = "cep:{$cep}";

        if (($cached = Cache::get($cacheKey)) !== null) {
            return $cached;
        }

        $address = $this->fetch($cep);

        if ($address !== null) {
            Cache::put($cacheKey, $address, now()->addDays(config('services.brasilapi.cache_days')));
        }

        return $address;
    }

    /**
     * Request the CEP from BrasilAPI and map the response to the address fields.
     *
     * @return array{zip_code: string, street: string|null, neighborhood: string|null, city: string, state: string, ibge_code: string|null, ibge_state_code: string|null}|null
     */
    private function fetch(string $cep): ?array
    {
        try {
            $response = Http::baseUrl(config('services.brasilapi.url'))
                ->acceptJson()
                ->timeout(config('services.brasilapi.timeout'))
                ->get("cep/v2/{$cep}");
        } catch (ConnectionException $e) {
            throw new CepLookupException('Could not reach BrasilAPI.', $cep, $e);
        }

        // BrasilAPI answers 404 when no provider knows the CEP and 400 when its format is invalid.
        if (in_array($response->status(), [400, 404], true)) {
            return null;
        }

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw new CepLookupException('BrasilAPI failed to look up the CEP.', $cep, $e);
        }

        $data = $response->json();

        if (! is_array($data) || blank($data['city'] ?? null) || blank($data['state'] ?? null)) {
            throw new CepLookupException('BrasilAPI returned an unexpected CEP response.', $cep);
        }

        return [
            'zip_code' => $cep,
            'street' => $this->text($data['street'] ?? null),
            'neighborhood' => $this->text($data['neighborhood'] ?? null),
            'city' => $data['city'],
            'state' => strtoupper($data['state']),
            'ibge_code' => $this->text($data['ibge']['city'] ?? null),
            'ibge_state_code' => $this->text($data['ibge']['state'] ?? null),
        ];
    }

    /**
     * Trim a text field, turning blank values (CEPs of a whole city have no street) into null.
     */
    private function text(mixed $value): ?string
    {
        return is_scalar($value) && filled(trim((string) $value)) ? trim((string) $value) : null;
    }
}
