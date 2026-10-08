import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import AcademicTermForm from '@/components/academic-term/form';
import type { AcademicTerm } from '@/components/academic-term/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    term?: AcademicTerm;
    statuses: Record<string, string>;
};

/**
 * Modal used both to register a new academic term and to edit an existing one.
 */
export default function AcademicTermModal({ state, term, statuses }: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {term
                                    ? 'Editar período letivo'
                                    : 'Novo período letivo'}
                            </Modal.Heading>
                        </Modal.Header>
                        <AcademicTermForm
                            term={term}
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
