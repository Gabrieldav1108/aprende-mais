const onlyDigits = (value: string): string => value.replace(/\D/g, '');

/**
 * Format a stored CPF (11 digits) or CNPJ (14 digits) for display.
 */
export function formatCpfCnpj(value: string | null | undefined): string {
    const digits = onlyDigits(value ?? '');

    if (digits.length === 11) {
        return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    }

    if (digits.length === 14) {
        return digits.replace(
            /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
            '$1.$2.$3/$4-$5',
        );
    }

    return value ?? '';
}

/**
 * Mask a CPF (up to 11 digits) or CNPJ (12 to 14 digits) while it is typed.
 */
export function maskCpfCnpj(value: string): string {
    const digits = onlyDigits(value).slice(0, 14);

    if (digits.length <= 11) {
        return digits
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }

    return digits
        .replace(/(\d{2})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1/$2')
        .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
}

/**
 * Format a stored phone number (DDD + number) for display.
 */
export function formatPhone(value: string | null | undefined): string {
    const digits = onlyDigits(value ?? '');

    if (digits.length === 11) {
        return digits.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    }

    if (digits.length === 10) {
        return digits.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    }

    return value ?? '';
}

/**
 * Mask a zip code while it is typed (00000-000), keeping at most 8 digits.
 */
export function maskZipCode(value: string): string {
    const digits = onlyDigits(value).slice(0, 8);

    return digits.length > 5
        ? `${digits.slice(0, 5)}-${digits.slice(5)}`
        : digits;
}

/**
 * Format a stored zip code (8 digits) for display.
 */
export function formatZipCode(value: string | null | undefined): string {
    const digits = onlyDigits(value ?? '');

    return digits.length === 8
        ? digits.replace(/(\d{5})(\d{3})/, '$1-$2')
        : (value ?? '');
}
