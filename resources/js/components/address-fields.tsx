import { Input, Label, Spinner } from '@heroui/react';
import { useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { useCepLookup } from '@/hooks/use-cep-lookup';
import { formatZipCode, maskZipCode } from '@/lib/format';

export type AddressValue = {
    zip_code: string | null;
    street: string | null;
    neighborhood: string | null;
    city: string | null;
    state: string | null;
    number: string | null;
    complement: string | null;
    ibge_code?: string | null;
    ibge_state_code?: string | null;
};

type Props = {
    address?: AddressValue | null;
    errors: Partial<Record<string, string>>;
    /** Prefix of the submitted fields, e.g. `address` submits `address[zip_code]`. */
    name?: string;
};

type LookedUpFields = {
    street: string;
    neighborhood: string;
    city: string;
    state: string;
    ibge_code: string;
    ibge_state_code: string;
};

const onlyDigits = (value: string): string => value.replace(/\D/g, '');

/**
 * Address inputs that fill street, neighborhood, city and state from the CEP as soon as it is complete.
 * Renders its fields as siblings, so the parent decides the grid (built for a 4-column grid).
 */
export default function AddressFields({
    address,
    errors,
    name = 'address',
}: Props) {
    const { lookup, processing, error, clearError } = useCepLookup();
    const [zipCode, setZipCode] = useState(formatZipCode(address?.zip_code));
    const [fields, setFields] = useState<LookedUpFields>({
        street: address?.street ?? '',
        neighborhood: address?.neighborhood ?? '',
        city: address?.city ?? '',
        state: address?.state ?? '',
        ibge_code: address?.ibge_code ?? '',
        ibge_state_code: address?.ibge_state_code ?? '',
    });
    const lastLookedUp = useRef(onlyDigits(address?.zip_code ?? ''));
    const streetRef = useRef<HTMLInputElement>(null);
    const numberRef = useRef<HTMLInputElement>(null);

    const fieldName = (field: string) => `${name}[${field}]`;
    const fieldId = (field: string) => `${name}_${field}`;
    const fieldError = (field: string) => errors[`${name}.${field}`];

    const setField = (field: keyof LookedUpFields, value: string) =>
        setFields((current) => ({ ...current, [field]: value }));

    const changeZipCode = async (value: string) => {
        const masked = maskZipCode(value);
        const digits = onlyDigits(masked);

        setZipCode(masked);

        if (digits.length !== 8) {
            clearError();
            lastLookedUp.current = '';

            return;
        }

        if (digits === lastLookedUp.current) {
            return;
        }

        lastLookedUp.current = digits;
        // The IBGE codes belong to the previous CEP until the new one is found.
        setFields((current) => ({
            ...current,
            ibge_code: '',
            ibge_state_code: '',
        }));

        const found = await lookup(digits);

        if (!found) {
            return;
        }

        setFields({
            street: found.street ?? '',
            neighborhood: found.neighborhood ?? '',
            city: found.city,
            state: found.state,
            ibge_code: found.ibge_code ?? '',
            ibge_state_code: found.ibge_state_code ?? '',
        });

        // CEPs of a whole city have no street, so the user types it.
        (found.street ? numberRef : streetRef).current?.focus();
    };

    return (
        <>
            <input
                type="hidden"
                name={fieldName('ibge_code')}
                value={fields.ibge_code}
            />
            <input
                type="hidden"
                name={fieldName('ibge_state_code')}
                value={fields.ibge_state_code}
            />

            <div className="grid min-w-0 gap-2">
                <Label htmlFor={fieldId('zip_code')}>CEP</Label>
                <div className="relative">
                    <Input
                        variant="secondary"
                        fullWidth
                        id={fieldId('zip_code')}
                        name={fieldName('zip_code')}
                        inputMode="numeric"
                        autoComplete="postal-code"
                        placeholder="00000-000"
                        value={zipCode}
                        onChange={(event) => changeZipCode(event.target.value)}
                    />
                    {processing && (
                        <Spinner
                            size="sm"
                            aria-label="Buscando CEP"
                            className="absolute top-1/2 right-3 -translate-y-1/2"
                        />
                    )}
                </div>
                <InputError
                    message={fieldError('zip_code') ?? error ?? undefined}
                />
            </div>

            <div className="grid min-w-0 gap-2 sm:col-span-2">
                <Label htmlFor={fieldId('street')}>Rua</Label>
                <Input
                    ref={streetRef}
                    variant="secondary"
                    fullWidth
                    id={fieldId('street')}
                    name={fieldName('street')}
                    autoComplete="address-line1"
                    value={fields.street}
                    onChange={(event) => setField('street', event.target.value)}
                />
                <InputError message={fieldError('street')} />
            </div>

            <div className="grid min-w-0 gap-2">
                <Label htmlFor={fieldId('number')}>Número</Label>
                <Input
                    ref={numberRef}
                    variant="secondary"
                    fullWidth
                    id={fieldId('number')}
                    name={fieldName('number')}
                    defaultValue={address?.number ?? ''}
                />
                <InputError message={fieldError('number')} />
            </div>

            <div className="grid min-w-0 gap-2">
                <Label htmlFor={fieldId('complement')}>Complemento</Label>
                <Input
                    variant="secondary"
                    fullWidth
                    id={fieldId('complement')}
                    name={fieldName('complement')}
                    autoComplete="address-line2"
                    defaultValue={address?.complement ?? ''}
                />
                <InputError message={fieldError('complement')} />
            </div>

            <div className="grid min-w-0 gap-2">
                <Label htmlFor={fieldId('neighborhood')}>Bairro</Label>
                <Input
                    variant="secondary"
                    fullWidth
                    id={fieldId('neighborhood')}
                    name={fieldName('neighborhood')}
                    value={fields.neighborhood}
                    onChange={(event) =>
                        setField('neighborhood', event.target.value)
                    }
                />
                <InputError message={fieldError('neighborhood')} />
            </div>

            <div className="grid min-w-0 gap-2">
                <Label htmlFor={fieldId('city')}>Cidade</Label>
                <Input
                    variant="secondary"
                    fullWidth
                    id={fieldId('city')}
                    name={fieldName('city')}
                    autoComplete="address-level2"
                    value={fields.city}
                    onChange={(event) => setField('city', event.target.value)}
                />
                <InputError message={fieldError('city')} />
            </div>

            <div className="grid min-w-0 gap-2">
                <Label htmlFor={fieldId('state')}>UF</Label>
                <Input
                    variant="secondary"
                    fullWidth
                    id={fieldId('state')}
                    name={fieldName('state')}
                    autoComplete="address-level1"
                    maxLength={2}
                    placeholder="SP"
                    value={fields.state}
                    onChange={(event) =>
                        setField('state', event.target.value.toUpperCase())
                    }
                />
                <InputError message={fieldError('state')} />
            </div>
        </>
    );
}
