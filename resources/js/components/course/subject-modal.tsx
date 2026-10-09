import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import type { Teacher } from '@/components/classroom/table';
import CourseSubjectForm from '@/components/course/subject-form';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    courseId: number;
    durationTerms: number;
    teachers: Teacher[];
};

/**
 * Modal used to add a subject to the course catalog.
 */
export default function CourseSubjectModal({
    state,
    courseId,
    durationTerms,
    teachers,
}: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>Nova matéria</Modal.Heading>
                        </Modal.Header>
                        <CourseSubjectForm
                            courseId={courseId}
                            durationTerms={durationTerms}
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
