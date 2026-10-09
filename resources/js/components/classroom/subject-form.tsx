import { Button, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import FormSelect from '@/components/form-select';
import classroom from '@/routes/classroom';

export type CourseSubject = {
    id: number;
    name: string;
    code: string;
    workload_hours: number;
    teacher: { person: { name: string } } | null;
};

type Props = {
    classroomId: number;
    courseSubjects: CourseSubject[];
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the offer-subject modal; the form closes the modal once saved.
 */
export default function ClassroomSubjectForm({
    classroomId,
    courseSubjects,
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
                            options={courseSubjects
                                .filter((subject) => subject.teacher !== null)
                                .map((subject) => ({
                                    id: subject.id,
                                    label: `${subject.code} · ${subject.name} (${subject.workload_hours}h) · ${subject.teacher?.person.name}`,
                                }))}
                            error={errors.subject_id}
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
