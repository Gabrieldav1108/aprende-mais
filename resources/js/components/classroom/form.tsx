import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
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
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the classroom modal; the form closes the modal once saved.
 */
export default function ClassroomForm({
    classroom: existing,
    courses,
    terms,
    teachers,
    shifts,
    statuses,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);

    return (
        <Form
            action={
                existing ? classroom.update(existing.id) : classroom.store()
            }
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-2 sm:items-start">
                        {!isEditing && (
                            <div className="grid min-w-0 sm:col-span-2">
                                <FormSelect
                                    name="course_id"
                                    label="Curso"
                                    isRequired
                                    variant="secondary"
                                    fullWidth
                                    options={courses.map((course) => ({
                                        id: course.id,
                                        label: `${course.code} · ${course.name}`,
                                    }))}
                                    error={errors.course_id}
                                />
                            </div>
                        )}

                        <div className="grid min-w-0 sm:col-span-2">
                            <FormSelect
                                name="academic_term_id"
                                label="Período letivo"
                                isRequired
                                variant="secondary"
                                fullWidth
                                options={terms.map((term) => ({
                                    id: term.id,
                                    label: term.name,
                                }))}
                                defaultValue={existing?.academic_term_id}
                                error={errors.academic_term_id}
                            />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="name"
                                name="name"
                                placeholder="Turma A"
                                defaultValue={existing?.name}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="code">Código</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="code"
                                name="code"
                                placeholder="ES-3A"
                                defaultValue={existing?.code}
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="term_number">
                                Semestre do curso
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
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
                            variant="secondary"
                            fullWidth
                            options={optionsFromMap(shifts)}
                            defaultValue={existing?.shift}
                            error={errors.shift}
                        />

                        <FormSelect
                            name="status"
                            label="Status"
                            isRequired
                            variant="secondary"
                            fullWidth
                            options={optionsFromMap(statuses)}
                            defaultValue={existing?.status ?? 'active'}
                            error={errors.status}
                        />

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="capacity">Vagas</Label>
                            <Input
                                variant="secondary"
                                fullWidth
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
                            variant="secondary"
                            fullWidth
                            options={teachers.map((teacher) => ({
                                id: teacher.id,
                                label: teacher.person.name,
                            }))}
                            defaultValue={existing?.homeroom_teacher_id}
                            error={errors.homeroom_teacher_id}
                        />

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="room">Sala</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="room"
                                name="room"
                                placeholder="Sala 101"
                                defaultValue={existing?.room ?? ''}
                            />
                            <InputError message={errors.room} />
                        </div>
                    </Modal.Body>

                    <Modal.Footer>
                        <Button
                            type="button"
                            variant="secondary"
                            onPress={onCancel}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" isDisabled={processing}>
                            {processing && <Spinner />}
                            {isEditing ? 'Salvar alterações' : 'Criar turma'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
