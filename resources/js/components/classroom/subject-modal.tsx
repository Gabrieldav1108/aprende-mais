import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import ClassroomSubjectForm from '@/components/classroom/subject-form';
import type { CourseSubject } from '@/components/classroom/subject-form';
import type { Teacher } from '@/components/classroom/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    classroomId: number;
    courseSubjects: CourseSubject[];
    teachers: Teacher[];
};

/**
 * Modal used to offer a course subject in the classroom with a teacher.
 */
export default function ClassroomSubjectModal({
    state,
    classroomId,
    courseSubjects,
    teachers,
}: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>Ofertar matéria</Modal.Heading>
                        </Modal.Header>
                        <ClassroomSubjectForm
                            classroomId={classroomId}
                            courseSubjects={courseSubjects}
                            teachers={teachers}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
