<?php

use App\Exceptions\CepLookupException;
use App\Models\Person;
use App\Models\User;
use App\Services\CepService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * @return array<string, mixed>
 */
function brasilApiCep(array $overrides = []): array
{
    return [
        'cep' => '01001000',
        'state' => 'SP',
        'city' => 'São Paulo',
        'neighborhood' => 'Sé',
        'street' => 'Praça da Sé',
        'service' => 'open-cep',
        'ibge' => ['city' => '3550308', 'state' => '35'],
        'location' => ['type' => 'Point', 'coordinates' => ['longitude' => '-46.63', 'latitude' => '-23.55']],
        ...$overrides,
    ];
}

test('the service maps the BrasilAPI response to the address fields', function () {
    Http::fake(['brasilapi.com.br/api/cep/v2/01001000' => Http::response(brasilApiCep())]);

    expect(app(CepService::class)->lookup('01001-000'))->toBe([
        'zip_code' => '01001000',
        'street' => 'Praça da Sé',
        'neighborhood' => 'Sé',
        'city' => 'São Paulo',
        'state' => 'SP',
        'ibge_code' => '3550308',
        'ibge_state_code' => '35',
    ]);
});

test('the service caches found addresses', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response(brasilApiCep())]);
    $service = app(CepService::class);

    $service->lookup('01001000');
    $service->lookup('01001-000');

    Http::assertSentCount(1);
});

test('the service turns a blank street into null for city-wide CEPs', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response(brasilApiCep(['street' => '', 'neighborhood' => null]))]);

    expect(app(CepService::class)->lookup('01001000'))
        ->street->toBeNull()
        ->neighborhood->toBeNull();
});

test('the service returns null for unknown or malformed CEPs', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response(['name' => 'CepPromiseError', 'type' => 'service_error'], 404)]);
    $service = app(CepService::class);

    expect($service->lookup('00000000'))->toBeNull()
        ->and($service->lookup('123'))->toBeNull();

    Http::assertSentCount(1);
});

test('the service throws when BrasilAPI is unavailable', function (Closure $response) {
    Http::fake(['brasilapi.com.br/*' => $response]);

    app(CepService::class)->lookup('01001000');
})->with([
    'server error' => fn () => fn () => Http::response('', 500),
    'connection error' => fn () => fn () => throw new ConnectionException('timeout'),
    'unexpected body' => fn () => fn () => Http::response(['foo' => 'bar']),
])->throws(CepLookupException::class);

test('guests cannot look up a CEP', function () {
    $this->getJson(route('cep.show', '01001000'))->assertUnauthorized();
});

test('the endpoint returns the address of a CEP', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response(brasilApiCep())]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('cep.show', '01001000'))
        ->assertOk()
        ->assertJson(['zip_code' => '01001000', 'city' => 'São Paulo', 'state' => 'SP', 'ibge_code' => '3550308']);
});

test('the endpoint answers 404 for an unknown CEP', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response([], 404)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('cep.show', '00000000'))
        ->assertNotFound()
        ->assertJson(['message' => 'CEP não encontrado.']);
});

test('the endpoint answers 503 when BrasilAPI is unavailable', function () {
    Http::fake(['brasilapi.com.br/*' => Http::response('', 502)]);

    $this->actingAs(User::factory()->create())
        ->getJson(route('cep.show', '01001000'))
        ->assertServiceUnavailable();
});

test('the IBGE codes found by the CEP are stored with the address', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('user.store'), [
        'name' => 'Ana Prado',
        'email' => 'ana@example.com',
        'password' => 'password-123',
        'password_confirmation' => 'password-123',
        'roles' => ['student'],
        'address' => [
            'zip_code' => '01001-000', 'street' => 'Praça da Sé', 'neighborhood' => 'Sé', 'city' => 'São Paulo',
            'state' => 'SP', 'number' => '1', 'ibge_code' => '3550308', 'ibge_state_code' => '35',
        ],
    ])->assertSessionHasNoErrors();

    expect(Person::where('name', 'Ana Prado')->sole()->address)
        ->ibge_code->toBe('3550308')
        ->ibge_state_code->toBe('35');
});
