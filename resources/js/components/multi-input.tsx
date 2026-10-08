import { Button, Input, Label } from '@heroui/react';
import { Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import InputError from '@/components/input-error';

type Props = {
    name: string;
    label: string;
    initialValues?: string[];
    placeholder?: string;
    type?: string;
    errors?: Record<string, string>;
    addLabel: string;
    variant?: 'primary' | 'secondary';
};

type Entry = { key: number; value: string };

/**
 * A repeatable text field submitted as `name[]`; blank entries are dropped by the server.
 */
export default function MultiInput({
    name,
    label,
    initialValues = [],
    placeholder,
    type = 'text',
    errors = {},
    addLabel,
    variant,
}: Props) {
    const [nextKey, setNextKey] = useState(initialValues.length);
    const [entries, setEntries] = useState<Entry[]>(
        initialValues.map((value, key) => ({ key, value })),
    );

    const add = () => {
        setEntries((current) => [...current, { key: nextKey, value: '' }]);
        setNextKey(nextKey + 1);
    };

    const remove = (key: number) =>
        setEntries((current) => current.filter((entry) => entry.key !== key));

    const update = (key: number, value: string) =>
        setEntries((current) =>
            current.map((entry) =>
                entry.key === key ? { ...entry, value } : entry,
            ),
        );

    return (
        <div className="grid min-w-0 gap-2">
            <Label>{label}</Label>
            {entries.map((entry, index) => (
                <div key={entry.key} className="grid gap-1">
                    <div className="flex gap-2">
                        <Input
                            aria-label={`${label} ${index + 1}`}
                            name={`${name}[]`}
                            type={type}
                            variant={variant}
                            fullWidth
                            placeholder={placeholder}
                            value={entry.value}
                            onChange={(event) =>
                                update(entry.key, event.target.value)
                            }
                            className="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            isIconOnly
                            aria-label={`Remover ${label.toLowerCase()} ${index + 1}`}
                            onPress={() => remove(entry.key)}
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                    <InputError message={errors[`${name}.${index}`]} />
                </div>
            ))}
            <Button
                type="button"
                variant="outline"
                size="sm"
                className="w-fit"
                onPress={add}
            >
                <Plus className="size-4" />
                {addLabel}
            </Button>
        </div>
    );
}
