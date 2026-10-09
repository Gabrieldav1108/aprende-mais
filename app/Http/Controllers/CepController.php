<?php

namespace App\Http\Controllers;

use App\Exceptions\CepLookupException;
use App\Services\CepService;
use Illuminate\Http\JsonResponse;

class CepController extends Controller
{
    public function __construct(private readonly CepService $ceps) {}

    /**
     * Return the address of the given CEP, used to fill address forms.
     */
    public function __invoke(string $cep): JsonResponse
    {
        try {
            $address = $this->ceps->lookup($cep);
        } catch (CepLookupException $e) {
            report($e);

            return response()->json(['message' => __('Não foi possível consultar o CEP agora.')], 503);
        }

        if ($address === null) {
            return response()->json(['message' => __('CEP não encontrado.')], 404);
        }

        return response()->json($address);
    }
}
