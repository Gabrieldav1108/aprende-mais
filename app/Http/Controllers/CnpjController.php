<?php

namespace App\Http\Controllers;

use App\Exceptions\CnpjLookupException;
use App\Services\CnpjService;
use Illuminate\Http\JsonResponse;

class CnpjController extends Controller
{
    public function __construct(private readonly CnpjService $cnpjs) {}

    /**
     * Return the company registered under the given CNPJ, used to fill registration forms.
     */
    public function __invoke(string $cnpj): JsonResponse
    {
        try {
            $company = $this->cnpjs->lookup($cnpj);
        } catch (CnpjLookupException $e) {
            report($e);

            return response()->json(['message' => __('Não foi possível consultar o CNPJ agora.')], 503);
        }

        if ($company === null) {
            return response()->json(['message' => __('CNPJ não encontrado.')], 404);
        }

        return response()->json($company);
    }
}
