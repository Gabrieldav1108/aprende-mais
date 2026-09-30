import { Button, Input, Label, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import course from '@/routes/course';

type Props = {
    courseId: number;
    durationTerms: number;
};

export default function CourseSubjectForm({ courseId, durationTerms }: Props) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">
                Adicionar matéria ao curso
            </h2>
            <Form
                action={course.subjects.store(courseId)}
                resetOnSuccess
                className="grid gap-4 sm:grid-cols-5 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Cálculo I"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code">Código</Label>
                            <Input
                                id="code"
                                name="code"
                                placeholder="MAT-101"
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="workload_hours">
                                Carga horária
                            </Label>
                            <Input
                                id="workload_hours"
                                name="workload_hours"
                                type="number"
                                min={1}
                                placeholder="80"
                                required
                            />
                            <InputError message={errors.workload_hours} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="suggested_term_number">
                                Semestre sugerido
                            </Label>
                            <Input
                                id="suggested_term_number"
                                name="suggested_term_number"
                                type="number"
                                min={1}
                                max={durationTerms}
                                placeholder="1"
                            />
                            <InputError
                                message={errors.suggested_term_number}
                            />
                        </div>

                        <Button
                            type="submit"
                            className="sm:col-span-5 sm:w-fit"
                        >
                            {processing && <Spinner />}
                            Adicionar matéria
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
