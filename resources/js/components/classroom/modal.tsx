import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import ClassroomForm from '@/components/classroom/form';
import type { CourseOption, TermOption } from '@/components/classroom/form';
import type { Classroom, Teacher } from '@/components/classroom/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    classroom?: Classroom;
    courses: CourseOption[];
    terms: TermOption[];
    teachers: Teacher[];
    shifts: Record<string, string>;
    statuses: Record<string, string>;
};

/**
 * Modal used both to register a new classroom and to edit an existing one.
 */
export default function ClassroomModal({
    state,
    classroom,
    courses,
    terms,
    teachers,
    shifts,
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
                                {classroom ? 'Editar turma' : 'Nova turma'}
                            </Modal.Heading>
                        </Modal.Header>
                        <ClassroomForm
                            classroom={classroom}
                            courses={courses}
                            terms={terms}
                            teachers={teachers}
                            shifts={shifts}
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
