<?php

use App\Rules\CnpjRule;
use App\Rules\CpfRule;
use Illuminate\Support\Facades\Validator;

/**
 * Whether the value passes the given document rule.
 */
function passesDocumentRule(CpfRule|CnpjRule $rule, mixed $value): bool
{
    return Validator::make(['document' => $value], ['document' => [$rule]])->passes();
}

test('the cpf rule accepts a valid cpf with or without the mask', function (string $cpf) {
    expect(passesDocumentRule(new CpfRule, $cpf))->toBeTrue();
})->with(['111.222.333-96', '11122233396', '529.982.247-25']);

test('the cpf rule rejects malformed or invalid cpfs', function (mixed $cpf) {
    expect(passesDocumentRule(new CpfRule, $cpf))->toBeFalse();
})->with([
    'wrong check digits' => '111.222.333-97',
    'repeated digits' => '111.111.111-11',
    'too short' => '1112223339',
    'partial mask' => '111222333-96',
    'letters' => '111.222.333-9A',
    'a cnpj' => '11222333000181',
    'not a string' => 11122233396,
]);

test('the cpf rule explains whether the format or the check digits are wrong', function () {
    $validator = Validator::make(['cpf' => '123'], ['cpf' => [new CpfRule]]);
    expect($validator->errors()->first('cpf'))->toBe('O campo cpf deve estar no formato 000.000.000-00.');

    $validator = Validator::make(['cpf' => '111.222.333-97'], ['cpf' => [new CpfRule]]);
    expect($validator->errors()->first('cpf'))->toBe('O cpf informado não é um CPF válido.');
});

test('the cnpj rule accepts a valid cnpj with or without the mask', function (string $cnpj) {
    expect(passesDocumentRule(new CnpjRule, $cnpj))->toBeTrue();
})->with(['11.222.333/0001-81', '11222333000181', '33.000.167/0001-01']);

test('the cnpj rule rejects malformed or invalid cnpjs', function (mixed $cnpj) {
    expect(passesDocumentRule(new CnpjRule, $cnpj))->toBeFalse();
})->with([
    'wrong check digits' => '11.222.333/0001-80',
    'repeated digits' => '00.000.000/0000-00',
    'too short' => '1122233300018',
    'partial mask' => '11222333/0001-81',
    'letters' => '11.222.333/0001-8A',
    'a cpf' => '11122233396',
    'not a string' => 11222333000181,
]);

test('the cnpj rule explains whether the format or the check digits are wrong', function () {
    $validator = Validator::make(['cnpj' => '123'], ['cnpj' => [new CnpjRule]]);
    expect($validator->errors()->first('cnpj'))->toBe('O campo cnpj deve estar no formato 00.000.000/0000-00.');

    $validator = Validator::make(['cnpj' => '11.222.333/0001-80'], ['cnpj' => [new CnpjRule]]);
    expect($validator->errors()->first('cnpj'))->toBe('O cnpj informado não é um CNPJ válido.');
});
