import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Teacher } from '@/components/classroom/table';
import FormSelect from '@/components/form-select';
import InputError from '@/components/input-error';
import course from '@/routes/course';

type Props = {
    courseId: number;
    durationTerms: number;
    teachers: Teacher[];
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the subject modal; the form closes the modal once saved.
 */
export default function CourseSubjectForm({
    courseId,
    durationTerms,
    teachers,
    onSuccess,
    onCancel,
}: Props) {
    return (
        <Form
            action={course.subjects.store(courseId)}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-2 sm:items-start">
                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            <Label htmlFor="subject_name">Nome</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="subject_name"
                                name="name"
                                placeholder="Cálculo I"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="sm:col-span-2">
                            <FormSelect
                                name="teacher_id"
                                label="Professor"
                                isRequired
                                variant="secondary"
                                fullWidth
                                options={teachers.map((teacher) => ({
                                    id: teacher.id,
                                    label: teacher.person.name,
                                }))}
                                error={errors.teacher_id}
                            />
                        </div>
                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="subject_code">Código</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="subject_code"
                                name="code"
                                placeholder="MAT-101"
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="workload_hours">
                                Carga horária
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="workload_hours"
                                name="workload_hours"
                                type="number"
                                min={1}
                                placeholder="80"
                                required
                            />
                            <InputError message={errors.workload_hours} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="suggested_term_number">
                                Semestre sugerido
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
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
                            Adicionar matéria
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
