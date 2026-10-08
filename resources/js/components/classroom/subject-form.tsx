import { Button, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Teacher } from '@/components/classroom/table';
import FormSelect from '@/components/form-select';
import classroom from '@/routes/classroom';

export type CourseSubject = {
    id: number;
    name: string;
    code: string;
    workload_hours: number;
};

type Props = {
    classroomId: number;
    courseSubjects: CourseSubject[];
    teachers: Teacher[];
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the offer-subject modal; the form closes the modal once saved.
 */
export default function ClassroomSubjectForm({
    classroomId,
    courseSubjects,
    teachers,
    onSuccess,
    onCancel,
}: Props) {
    return (
        <Form
            action={classroom.subjects.store(classroomId)}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4">
                        <FormSelect
                            name="subject_id"
                            label="Matéria do curso"
                            isRequired
                            variant="secondary"
                            fullWidth
                            options={courseSubjects.map((subject) => ({
                                id: subject.id,
                                label: `${subject.code} · ${subject.name} (${subject.workload_hours}h)`,
                            }))}
                            error={errors.subject_id}
                        />

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
