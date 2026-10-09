import type { UseLookupReturn } from '@/hooks/use-lookup';
import { useLookup } from '@/hooks/use-lookup';
import cep from '@/routes/cep';

export type CepAddress = {
    zip_code: string;
    street: string | null;
    neighborhood: string | null;
    city: string;
    state: string;
    ibge_code: string | null;
    ibge_state_code: string | null;
};

const messages = {
    notFound: 'CEP não encontrado. Preencha o endereço manualmente.',
    unavailable:
        'Não foi possível consultar o CEP agora. Preencha o endereço manualmente.',
};

const route = (zipCode: string) => cep.show(zipCode);

/**
 * Look up the address of a CEP through the application's BrasilAPI proxy.
 */
export function useCepLookup(): UseLookupReturn<CepAddress> {
    return useLookup<CepAddress>(route, messages);
}
