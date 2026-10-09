<?php

use App\Exceptions\CnpjLookupException;
use App\Models\User;
use App\Services\CnpjService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * @return array<string, mixed>
 */
function brasilApiCnpj(array $overrides = []): array
{
    return [
        'cnpj' => '19131243000197',
        'razao_social' => 'OPEN KNOWLEDGE BRASIL',
        'nome_fantasia' => 'REDE PELO CONHECIMENTO LIVRE',
        'situacao_cadastral' => 2,
        'descricao_situacao_cadastral' => 'ATIVA',
        'data_inicio_atividade' => '2013-10-03',
        'email' => 'CONTATO@OKFN.ORG.BR',
        'ddd_telefone_1' => '1123851939',
        'ddd_telefone_2' => '',
        'cep' => '01311902',
        'descricao_tipo_de_logradouro' => 'AVENIDA',
        'logradouro' => 'PAULISTA 37',
        'numero' => '37',
        'complemento' => 'ANDAR 4',
        'bairro' => 'BELA VISTA',
        'municipio' => 'SAO PAULO',
        'uf' => 'SP',
        'codigo_municipio_ibge' => 3550308,
        ...$overrides,
    ];
}

test('cnpj check digits are validated', function (string $cnpj, bool $valid) {
    expect(CnpjService::isValid($cnpj))->toBe($valid);
})->with([
    'valid' => ['19131243000197', true],
    'another valid' => ['11222333000181', true],
    'wrong check digit' => ['19131243000198', false],
    'repeated digits' => ['11111111111111', false],
    'too short' => ['1913124300019', false],
]);

test('the service maps the company and takes the accented address from the CEP lookup', function () {
    Http::fake([
        'brasilapi.com.br/api/cnpj/v1/19131243000197' => Http::response(brasilApiCnpj()),
        'brasilapi.com.br/api/cep/v2/01311902' => Http::response([
            'cep' => '01311902', 'state' => 'SP', 'city' => 'São Paulo', 'neighborhood' => 'Bela Vista',
            'street' => 'Avenida Paulista', 'ibge' => ['city' => '3550308', 'state' => '35'],
        ]),
    ]);

    expect(app(CnpjService::class)->lookup('19.131.243/0001-97'))->toBe([
        'cnpj' => '19131243000197',
        'legal_name' => 'OPEN KNOWLEDGE BRASIL',
        'trade_name' => 'REDE PELO CONHECIMENTO LIVRE',
        'status' => 'ATIVA',
        'is_active' => true,
        'opened_at' => '2013-10-03',
        'email' => 'contato@okfn.org.br',
        'phones' => ['1123851939'],
        'address' => [
            'zip_code' => '01311902',
            'street' => 'Avenida Paulista',
            'number' => '37',
            'complement' => 'Andar 4',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'ibge_code' => '3550308',
            'ibge_state_code' => '35',
        ],
    ]);
});

test('the service keeps the registered address when the CEP lookup fails', function () {
    Http::fake([
        'brasilapi.com.br/api/cnpj/*' => Http::response(brasilApiCnpj()),
        'brasilapi.com.br/api/cep/*' => Http::response('', 500),
    ]);

    expect(app(CnpjService::class)->lookup('19131243000197')['address'])->toMatchArray([
        'street' => 'Avenida Paulista 37',
        'neighborhood' => 'Bela Vista',
        'city' => 'Sao Paulo',
        'ibge_code' => '3550308',
        'ibge_state_code' => '35',
    ]);
});

test('the service flags companies that are not active', function () {
    Http::fake([
        'brasilapi.com.br/api/cnpj/*' => Http::response(brasilApiCnpj(['situacao_cadastral' => 8, 'descricao_situacao_cadastral' => 'BAIXADA'])),
        'brasilapi.com.br/api/cep/*' => Http::response([], 404),
    ]);

    expect(app(CnpjService::class)->lookup('19131243000197'))
        ->is_active->toBeFalse()
        ->status->toBe('BAIXADA');
});

test('the service caches found companies', function () {
    Http::fake([
        'brasilapi.com.br/api/cnpj/*' => Http::response(brasilApiCnpj()),
        'brasilapi.com.br/api/cep/*' => Http::response([], 404),
    ]);
    $service = app(CnpjService::class);

    $service->lookup('19131243000197');
    $service->lookup('19.131.243/0001-97');

    // One request for the CNPJ and one for its CEP; the second lookup comes from the cache.
    Http::assertSentCount(2);
});

test('the service does not call BrasilAPI for an invalid cnpj', function () {
    Http::fake();

    expect(app(CnpjService::class)->lookup('19131243000198'))->toBeNull();

    Http::assertNothingSent();
});

test('the service returns null for an unregistered cnpj', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response(['message' => 'CNPJ não encontrado'], 404)]);

    expect(app(CnpjService::class)->lookup('11222333000181'))->toBeNull();
});

test('the service throws when BrasilAPI is unavailable', function (Closure $response) {
    Http::fake(['brasilapi.com.br/*' => $response]);

    app(CnpjService::class)->lookup('19131243000197');
})->with([
    'server error' => fn () => fn () => Http::response(['type' => 'internal_error'], 500),
    'connection error' => fn () => fn () => throw new ConnectionException('timeout'),
    'unexpected body' => fn () => fn () => Http::response(['foo' => 'bar']),
])->throws(CnpjLookupException::class);

test('guests cannot look up a cnpj', function () {
    $this->getJson(route('cnpj.show', '19131243000197'))->assertUnauthorized();
});

test('the endpoint returns the company of a cnpj', function () {
    Http::fake([
        'brasilapi.com.br/api/cnpj/*' => Http::response(brasilApiCnpj()),
        'brasilapi.com.br/api/cep/*' => Http::response([], 404),
    ]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('cnpj.show', '19131243000197'))
        ->assertOk()
        ->assertJson(['legal_name' => 'OPEN KNOWLEDGE BRASIL', 'address' => ['zip_code' => '01311902', 'state' => 'SP']]);
});

test('the endpoint answers 404 for an invalid or unregistered cnpj', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response([], 404)]);
    $user = User::factory()->create();

    $this->actingAs($user)->getJson(route('cnpj.show', '19131243000198'))->assertNotFound();
    $this->actingAs($user)->getJson(route('cnpj.show', '11222333000181'))
        ->assertNotFound()
        ->assertJson(['message' => 'CNPJ não encontrado.']);
});

test('the endpoint answers 503 when BrasilAPI is unavailable', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response('', 500)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('cnpj.show', '19131243000197'))
        ->assertServiceUnavailable();
});
