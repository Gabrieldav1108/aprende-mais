import { useHttp } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';
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

export type UseCepLookupReturn = {
    lookup: (zipCode: string) => Promise<CepAddress | null>;
    processing: boolean;
    error: string | null;
    clearError: () => void;
};

/**
 * Look up the address of a CEP through the application's BrasilAPI proxy.
 */
export function useCepLookup(): UseCepLookupReturn {
    const { submit } = useHttp<Record<string, never>, CepAddress>();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const latestRequest = useRef(0);

    const clearError = useCallback(() => setError(null), []);

    const lookup = useCallback(
        async (zipCode: string): Promise<CepAddress | null> => {
            const request = ++latestRequest.current;

            setProcessing(true);
            setError(null);

            try {
                const address = await submit(cep.show(zipCode));

                // A newer lookup started while this one was running: ignore its result.
                return request === latestRequest.current ? address : null;
            } catch (exception) {
                if (request === latestRequest.current) {
                    const status = (
                        exception as { response?: { status?: number } }
                    ).response?.status;

                    setError(
                        status === 404
                            ? 'CEP não encontrado. Preencha o endereço manualmente.'
                            : 'Não foi possível consultar o CEP agora. Preencha o endereço manualmente.',
                    );
                }

                return null;
            } finally {
                if (request === latestRequest.current) {
                    setProcessing(false);
                }
            }
        },
        [submit],
    );

    return { lookup, processing, error, clearError };
}
