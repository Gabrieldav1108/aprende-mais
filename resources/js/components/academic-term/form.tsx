import { Button, Input, Label, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import academicTerm from '@/routes/academic-term';

type Props = {
    statuses: Record<string, string>;
};

const semesterOptions = [
    { id: 1, label: '1º semestre' },
    { id: 2, label: '2º semestre' },
];

export default function AcademicTermForm({ statuses }: Props) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">Novo período letivo</h2>
            <Form
                action={academicTerm.store()}
                resetOnSuccess
                className="grid gap-4 sm:grid-cols-5 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2">
                            <Label htmlFor="year">Ano</Label>
                            <Input
                                id="year"
                                name="year"
                                type="number"
                                min={2000}
                                max={2999}
                                placeholder="2026"
                                required
                            />
                            <InputError message={errors.year} />
                        </div>

                        <FormSelect
                            name="number"
                            label="Semestre"
                            isRequired
                            options={semesterOptions}
                            error={errors.number}
                        />

                        <div className="grid gap-2">
                            <Label htmlFor="starts_at">Início</Label>
                            <Input
                                id="starts_at"
                                name="starts_at"
                                type="date"
                                required
                            />
                            <InputError message={errors.starts_at} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ends_at">Fim</Label>
                            <Input
                                id="ends_at"
                                name="ends_at"
                                type="date"
                                required
                            />
                            <InputError message={errors.ends_at} />
                        </div>

                        <FormSelect
                            name="status"
                            label="Status"
                            isRequired
                            options={optionsFromMap(statuses)}
                            defaultValue="planned"
                            error={errors.status}
                        />

                        <Button
                            type="submit"
                            className="sm:col-span-5 sm:w-fit"
                        >
                            {processing && <Spinner />}
                            Criar período
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
