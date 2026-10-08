import { Button, Input, Label, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Classroom, Teacher } from '@/components/classroom/table';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import classroom from '@/routes/classroom';

export type CourseOption = {
    id: number;
    name: string;
    code: string;
};

export type TermOption = {
    id: number;
    name: string;
};

type Props = {
    classroom?: Classroom;
    courses: CourseOption[];
    terms: TermOption[];
    teachers: Teacher[];
    shifts: Record<string, string>;
    statuses: Record<string, string>;
};

export default function ClassroomForm({
    classroom: existing,
    courses,
    terms,
    teachers,
    shifts,
    statuses,
}: Props) {
    const isEditing = Boolean(existing);

    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">
                {isEditing ? 'Editar turma' : 'Nova turma'}
            </h2>
            <Form
                action={
                    existing ? classroom.update(existing.id) : classroom.store()
                }
                resetOnSuccess={!isEditing}
                className="grid gap-4 sm:grid-cols-4 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        {!isEditing && (
                            <div className="sm:col-span-2">
                                <FormSelect
                                    name="course_id"
                                    label="Curso"
                                    isRequired
                                    options={courses.map((course) => ({
                                        id: course.id,
                                        label: `${course.code} · ${course.name}`,
                                    }))}
                                    error={errors.course_id}
                                />
                            </div>
                        )}

                        <div className="sm:col-span-2">
                            <FormSelect
                                name="academic_term_id"
                                label="Período letivo"
                                isRequired
                                options={terms.map((term) => ({
                                    id: term.id,
                                    label: term.name,
                                }))}
                                defaultValue={existing?.academic_term_id}
                                error={errors.academic_term_id}
                            />
                        </div>

                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Turma A"
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
                                placeholder="ES-3A"
                                defaultValue={existing?.code}
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="term_number">
                                Semestre do curso
                            </Label>
                            <Input
                                id="term_number"
                                name="term_number"
                                type="number"
                                min={1}
                                max={20}
                                placeholder="1"
                                defaultValue={existing?.term_number}
                                required
                            />
                            <InputError message={errors.term_number} />
                        </div>

                        <FormSelect
                            name="shift"
                            label="Turno"
                            isRequired
                            options={optionsFromMap(shifts)}
                            defaultValue={existing?.shift}
                            error={errors.shift}
                        />

                        <FormSelect
                            name="status"
                            label="Status"
                            isRequired
                            options={optionsFromMap(statuses)}
                            defaultValue={existing?.status ?? 'active'}
                            error={errors.status}
                        />

                        <div className="grid gap-2">
                            <Label htmlFor="capacity">Vagas</Label>
                            <Input
                                id="capacity"
                                name="capacity"
                                type="number"
                                min={1}
                                placeholder="40"
                                defaultValue={existing?.capacity ?? 40}
                                required
                            />
                            <InputError message={errors.capacity} />
                        </div>

                        <FormSelect
                            name="homeroom_teacher_id"
                            label="Professor regente"
                            options={teachers.map((teacher) => ({
                                id: teacher.id,
                                label: teacher.person.name,
                            }))}
                            defaultValue={existing?.homeroom_teacher_id}
                            error={errors.homeroom_teacher_id}
                        />

                        <div className="grid gap-2">
                            <Label htmlFor="room">Sala</Label>
                            <Input
                                id="room"
                                name="room"
                                placeholder="Sala 101"
                                defaultValue={existing?.room ?? ''}
                            />
                            <InputError message={errors.room} />
                        </div>

                        <Button
                            type="submit"
                            className="sm:col-span-4 sm:w-fit"
                        >
                            {processing && <Spinner />}
                            {isEditing ? 'Salvar alterações' : 'Criar turma'}
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
