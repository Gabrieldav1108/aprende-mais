import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import UserForm from '@/components/user/form';
import type { Person } from '@/components/user/table';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    person?: Person;
};

/**
 * Modal used both to register a new user and to edit an existing one.
 */
export default function UserModal({ state, person }: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="lg" scroll="outside">
                    <Modal.Dialog className="sm:max-w-3xl">
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {person ? 'Editar usuário' : 'Novo usuário'}
                            </Modal.Heading>
                        </Modal.Header>
                        <UserForm
                            person={person}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
