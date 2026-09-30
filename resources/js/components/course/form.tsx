import { Button, Input, Label, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Course } from '@/components/course/table';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import course from '@/routes/course';

type Props = {
    course?: Course;
    levels: Record<string, string>;
    statuses: Record<string, string>;
};

export default function CourseForm({
    course: existing,
    levels,
    statuses,
}: Props) {
    const isEditing = Boolean(existing);

    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">
                {isEditing ? 'Editar curso' : 'Novo curso'}
            </h2>
            <Form
                action={existing ? course.update(existing.id) : course.store()}
                resetOnSuccess={!isEditing}
                className="grid gap-4 sm:grid-cols-4 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Engenharia de Software"
                                defaultValue={existing?.name}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code">Código</Label>
                            <Input
                                id="code"
                                name="code"
                                placeholder="ENG-SW"
                                defaultValue={existing?.code}
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="duration_terms">
                                Duração (semestres)
                            </Label>
                            <Input
                                id="duration_terms"
                                name="duration_terms"
                                type="number"
                                min={1}
                                max={20}
                                placeholder="8"
                                defaultValue={existing?.duration_terms}
                                required
                            />
                            <InputError message={errors.duration_terms} />
                        </div>

                        <FormSelect
                            name="level"
                            label="Nível"
                            isRequired
                            options={optionsFromMap(levels)}
                            defaultValue={existing?.level}
                            error={errors.level}
                        />

                        <FormSelect
                            name="status"
                            label="Status"
                            isRequired
                            options={optionsFromMap(statuses)}
                            defaultValue={existing?.status ?? 'active'}
                            error={errors.status}
                        />

                        <Button
                            type="submit"
                            className="sm:col-span-4 sm:w-fit"
                        >
                            {processing && <Spinner />}
                            {isEditing ? 'Salvar alterações' : 'Criar curso'}
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
