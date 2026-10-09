import type { UrlMethodPair } from '@inertiajs/core';
import { useHttp } from '@inertiajs/react';
import { useCallback, useRef, useState } from 'react';

export type LookupMessages = {
    notFound: string;
    unavailable: string;
};

export type UseLookupReturn<TResult> = {
    lookup: (key: string) => Promise<TResult | null>;
    processing: boolean;
    error: string | null;
    clearError: () => void;
};

/**
 * Fetch data from a JSON lookup endpoint (CEP, CNPJ, ...) that answers 404 when nothing is found.
 * Only the most recent lookup updates the state, so typing fast never shows a stale result.
 */
export function useLookup<TResult>(
    route: (key: string) => UrlMethodPair,
    messages: LookupMessages,
): UseLookupReturn<TResult> {
    const { submit } = useHttp<Record<string, never>, TResult>();
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const latestRequest = useRef(0);

    const clearError = useCallback(() => setError(null), []);

    const lookup = useCallback(
        async (key: string): Promise<TResult | null> => {
            const request = ++latestRequest.current;

            setProcessing(true);
            setError(null);

            try {
                const result = await submit(route(key));

                return request === latestRequest.current ? result : null;
            } catch (exception) {
                if (request === latestRequest.current) {
                    const status = (
                        exception as { response?: { status?: number } }
                    ).response?.status;

                    setError(
                        status === 404
                            ? messages.notFound
                            : messages.unavailable,
                    );
                }

                return null;
            } finally {
                if (request === latestRequest.current) {
                    setProcessing(false);
                }
            }
        },
        [submit, route, messages],
    );

    return { lookup, processing, error, clearError };
}
