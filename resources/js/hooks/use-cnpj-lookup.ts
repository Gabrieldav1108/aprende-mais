import type { AddressValue } from '@/components/address-fields';
import type { UseLookupReturn } from '@/hooks/use-lookup';
import { useLookup } from '@/hooks/use-lookup';
import cnpj from '@/routes/cnpj';

export type CnpjCompany = {
    cnpj: string;
    legal_name: string;
    trade_name: string | null;
    status: string | null;
    is_active: boolean;
    opened_at: string | null;
    email: string | null;
    phones: string[];
    address: AddressValue;
};

const messages = {
    notFound: 'CNPJ não encontrado na Receita Federal.',
    unavailable: 'Não foi possível consultar o CNPJ agora.',
};

const route = (document: string) => cnpj.show(document);

/**
 * Look up the company registered under a CNPJ through the application's BrasilAPI proxy.
 */
export function useCnpjLookup(): UseLookupReturn<CnpjCompany> {
    return useLookup<CnpjCompany>(route, messages);
}
