import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import CourseForm from '@/components/course/form';
import type { SubjectOption } from '@/components/course/form';
import type { Course } from '@/components/course/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    course?: Course;
    subjectOptions: SubjectOption[];
    levels: Record<string, string>;
    statuses: Record<string, string>;
};

/**
 * Modal used both to register a new course and to edit an existing one.
 */
export default function CourseModal({
    state,
    course,
    subjectOptions,
    levels,
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
                                {course ? 'Editar curso' : 'Novo curso'}
                            </Modal.Heading>
                        </Modal.Header>
                        <CourseForm
                            course={course}
                            subjectOptions={subjectOptions}
                            levels={levels}
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
