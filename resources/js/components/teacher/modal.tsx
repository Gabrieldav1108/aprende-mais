import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import TeacherForm from '@/components/teacher/form';
import type { Teacher } from '@/components/teacher/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    teacher?: Teacher;
};

/**
 * Modal used both to register a new teacher and to edit an existing one.
 */
export default function TeacherModal({ state, teacher }: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="lg" scroll="outside">
                    <Modal.Dialog className="sm:max-w-3xl">
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
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
