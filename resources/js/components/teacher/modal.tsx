import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import TeacherForm from '@/components/teacher/form';
import type { Teacher } from '@/components/teacher/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    teacher?: Teacher;
    people: { id: number; name: string }[];
};

/**
 * Modal used both to register a new teacher and to edit an existing one.
 */
export default function TeacherModal({ state, teacher, people }: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {teacher
                                    ? 'Editar professor'
                                    : 'Novo professor'}
                            </Modal.Heading>
                        </Modal.Header>
                        <TeacherForm
                            teacher={teacher}
                            people={people}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
