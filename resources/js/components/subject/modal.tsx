import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import SubjectForm from '@/components/subject/form';
import type { SubjectOptions } from '@/components/subject/form';
import type { Subject } from '@/components/subject/table';

type Props = SubjectOptions & {
    state: ReturnType<typeof useOverlayState>;
    subject?: Subject;
};

/**
 * Modal used both to register a new subject and to edit an existing one.
 */
export default function SubjectModal({ state, subject, ...options }: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {subject ? 'Editar matéria' : 'Nova matéria'}
                            </Modal.Heading>
                        </Modal.Header>
                        <SubjectForm
                            {...options}
                            subject={subject}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
