import { Label, ListBox, Select } from '@heroui/react';
import InputError from '@/components/input-error';

export type SelectOption = {
    id: string | number;
    label: string;
};

type Props = {
    name: string;
    label: string;
    options: SelectOption[];
    defaultValue?: string | number | null;
    error?: string;
    isRequired?: boolean;
    isDisabled?: boolean;
    variant?: 'primary' | 'secondary';
    fullWidth?: boolean;
};

/**
 * Convert an enum map (value => label) into select options.
 */
export function optionsFromMap(map: Record<string, string>): SelectOption[] {
    return Object.entries(map).map(([id, label]) => ({ id, label }));
}

export default function FormSelect({
    name,
    label,
    options,
    defaultValue,
    error,
    isRequired = false,
    isDisabled = false,
    variant,
    fullWidth = false,
}: Props) {
    return (
        <div className="grid min-w-0 gap-2">
            <Select
                name={name}
                isRequired={isRequired}
                isDisabled={isDisabled}
                variant={variant}
                fullWidth={fullWidth}
                placeholder="Selecione"
                defaultSelectedKey={defaultValue ?? undefined}
            >
                <Label>{label}</Label>
                <Select.Trigger>
                    <Select.Value />
                    <Select.Indicator />
                </Select.Trigger>
                <Select.Popover>
                    <ListBox items={options}>
                        {(option) => (
                            <ListBox.Item
                                id={option.id}
                                textValue={option.label}
                            >
                                {option.label}
                            </ListBox.Item>
                        )}
                    </ListBox>
                </Select.Popover>
            </Select>
            <InputError message={error} />
        </div>
    );
}
