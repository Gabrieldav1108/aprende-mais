import { Button, Input, Label, ListBox, Select, TextField } from '@heroui/react';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { student as studentIndex } from '@/routes';

export type StudentFilters = {
    search?: string;
    course_id?: string;
    status?: string;
    sort_by?: string;
    direction?: 'asc' | 'desc';
    per_page?: string;
};

type Option = { id: string; label: string };

type Props = {
    filters: StudentFilters;
    courses: { id: number; name: string }[];
    statuses: Record<string, string>;
};

// "Todos" não existe no banco: é só uma opção do select que vira filtro vazio.
const ALL = 'all';

const sortOptions: Option[] = [
    { id: 'name', label: 'Nome' },
    { id: 'enrollment_number', label: 'Matrícula' },
    { id: 'course', label: 'Curso' },
    { id: 'status', label: 'Status' },
];

const perPageOptions: Option[] = ['10', '15', '25', '50'].map((n) => ({
    id: n,
    label: `${n} por página`,
}));

const toForm = (filters: StudentFilters) => ({
    search: filters.search ?? '',
    course_id: filters.course_id ?? '',
    status: filters.status ?? '',
    sort_by: filters.sort_by ?? 'name',
    direction: filters.direction ?? 'asc',
    per_page: filters.per_page ?? '15',
});

function FilterSelect({
    label,
    value,
    options,
    onChange,
}: {
    label: string;
    value: string;
    options: Option[];
    onChange: (value: string) => void;
}) {
    return (
        <Select
            className="w-48"
            placeholder={label}
            value={value}
            onChange={(key) => onChange(key === null ? '' : String(key))}
        >
            <Label>{label}</Label>
            <Select.Trigger>
                <Select.Value />
                <Select.Indicator />
            </Select.Trigger>
            <Select.Popover>
                <ListBox>
                    {options.map((option) => (
                        <ListBox.Item
                            key={option.id}
                            id={option.id}
                            textValue={option.label}
                        >
                            {option.label}
                            <ListBox.ItemIndicator />
                        </ListBox.Item>
                    ))}
                </ListBox>
            </Select.Popover>
        </Select>
    );
}

export default function StudentFilterBar({ filters, courses, statuses }: Props) {
    const [form, setForm] = useState(() => toForm(filters));

    const set = (field: keyof typeof form, value: string) =>
        setForm((current) => ({ ...current, [field]: value }));

    const visit = (query: Record<string, string>) =>
        router.get(studentIndex().url, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });

    const submit = (event: FormEvent) => {
        event.preventDefault();

        // Campos vazios ficam fora da URL.
        visit(Object.fromEntries(Object.entries(form).filter(([, v]) => v !== '')));
    };

    const clear = () => {
        setForm(toForm({}));
        visit({});
    };

    const courseOptions: Option[] = [
        { id: ALL, label: 'Todos os cursos' },
        ...courses.map((course) => ({ id: String(course.id), label: course.name })),
    ];

    const statusOptions: Option[] = [
        { id: ALL, label: 'Todos os status' },
        ...Object.entries(statuses).map(([value, label]) => ({ id: value, label })),
    ];

    return (
        <form onSubmit={submit} className="flex flex-wrap items-end gap-3">
            <TextField
                className="w-64"
                value={form.search}
                onChange={(value) => set('search', value)}
            >
                <Label>Buscar</Label>
                <Input placeholder="Nome, matrícula ou e-mail" />
            </TextField>

            <FilterSelect
                label="Curso"
                value={form.course_id || ALL}
                options={courseOptions}
                onChange={(v) => set('course_id', v === ALL ? '' : v)}
            />

            <FilterSelect
                label="Status"
                value={form.status || ALL}
                options={statusOptions}
                onChange={(v) => set('status', v === ALL ? '' : v)}
            />

            <FilterSelect
                label="Ordenar por"
                value={form.sort_by}
                options={sortOptions}
                onChange={(v) => set('sort_by', v)}
            />

            {/* "Crescente / Decrescente" como na imagem de referência */}
            <div className="flex gap-1">
                <Button
                    type="button"
                    variant={form.direction === 'asc' ? 'primary' : 'secondary'}
                    onPress={() => set('direction', 'asc')}
                >
                    Crescente
                </Button>
                <Button
                    type="button"
                    variant={form.direction === 'desc' ? 'primary' : 'secondary'}
                    onPress={() => set('direction', 'desc')}
                >
                    Decrescente
                </Button>
            </div>

            <FilterSelect
                label="Itens por página"
                value={form.per_page}
                options={perPageOptions}
                onChange={(v) => set('per_page', v)}
            />

            <Button type="button" variant="secondary" onPress={clear}>
                Limpar
            </Button>
            <Button type="submit">Consultar</Button>
        </form>
    );
}