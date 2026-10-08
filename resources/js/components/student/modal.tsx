import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import StudentForm from '@/components/student/form';
import type { Student } from '@/components/student/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    student?: Student;
    people: { id: number; name: string }[];
    courses: { id: number; name: string }[];
    statuses: Record<string, string>;
};

/**
 * Modal used both to register a new student and to edit an existing one.
 */
export default function StudentModal({
    state,
    student,
    people,
    courses,
    statuses,
}: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {student ? 'Editar aluno' : 'Novo aluno'}
                            </Modal.Heading>
                        </Modal.Header>
                        <StudentForm
                            student={student}
                            people={people}
                            courses={courses}
                            statuses={statuses}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
