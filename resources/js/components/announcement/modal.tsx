import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import AnnouncementForm from '@/components/announcement/form';
import type { Announcement } from '@/components/announcement/list';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    classroomId: number;
    classroomSubjectId: number;
    announcement?: Announcement;
};

/**
 * Modal used both to post a new announcement and to edit an existing one.
 */
export default function AnnouncementModal({
    state,
    classroomId,
    classroomSubjectId,
    announcement,
}: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="md" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {announcement ? 'Editar aviso' : 'Novo aviso'}
                            </Modal.Heading>
                        </Modal.Header>
                        <AnnouncementForm
                            classroomId={classroomId}
                            classroomSubjectId={classroomSubjectId}
                            announcement={announcement}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
