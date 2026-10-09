import { Input, Label, Spinner } from '@heroui/react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import type { CnpjCompany } from '@/hooks/use-cnpj-lookup';
import { useCnpjLookup } from '@/hooks/use-cnpj-lookup';
import { formatCpfCnpj, maskCpfCnpj } from '@/lib/format';

type Props = {
    defaultValue?: string | null;
    error?: string;
    name?: string;
    /** Called with the company once a complete CNPJ is found, to fill the rest of the form. */
    onCompanyFound?: (company: CnpjCompany) => void;
};

const onlyDigits = (value: string): string => value.replace(/\D/g, '');

/**
 * CPF / CNPJ input with mask that looks up the company as soon as a complete CNPJ is typed.
 */
export default function CpfCnpjField({
    defaultValue,
    error,
    name = 'cpf_cnpj',
    onCompanyFound,
}: Props) {
    const {
        lookup,
        processing,
        error: lookupError,
        clearError,
    } = useCnpjLookup();
    const [value, setValue] = useState(formatCpfCnpj(defaultValue));
    const [company, setCompany] = useState<CnpjCompany | null>(null);
    // The stored document needs no lookup; only a newly typed CNPJ does.
    const lastLookedUp = useRef(onlyDigits(defaultValue ?? ''));

    const change = async (typed: string) => {
        const masked = maskCpfCnpj(typed);
        const digits = onlyDigits(masked);

        setValue(masked);

        if (digits.length !== 14) {
            clearError();
            setCompany(null);
            lastLookedUp.current = '';

            return;
        }

        if (digits === lastLookedUp.current) {
            return;
        }

        lastLookedUp.current = digits;
        setCompany(null);

        const found = await lookup(digits);

        if (!found) {
            return;
        }

        setCompany(found);
        onCompanyFound?.(found);
    };

    return (
        <div className="grid min-w-0 gap-2">
            <Label htmlFor={name}>CPF / CNPJ</Label>
            <div className="relative">
                <Input
                    variant="secondary"
                    fullWidth
                    id={name}
                    name={name}
                    inputMode="numeric"
                    placeholder="000.000.000-00"
                    value={value}
                    onChange={(event) => change(event.target.value)}
                />
                {processing && (
                    <Spinner
                        size="sm"
                        aria-label="Buscando CNPJ"
                        className="absolute top-1/2 right-3 -translate-y-1/2"
                    />
                )}
            </div>
            {company && (
                <p className="text-xs text-muted-foreground">
                    {company.trade_name ?? company.legal_name}
                    {!company.is_active && company.status && (
                        <span className="ml-1 font-medium text-red-600 dark:text-red-400">
                            · Situação na Receita: {company.status}
                        </span>
                    )}
                </p>
            )}
            <InputError message={error ?? lookupError ?? undefined} />
        </div>
    );
}
