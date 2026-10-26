import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import ActivityForm from '@/components/activity/form';
import type { Activity } from '@/components/activity/list';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    classroomId: number;
    classroomSubjectId: number;
    activity?: Activity;
};

/**
 * Modal used both to post a new activity and to edit an existing one.
 */
export default function ActivityModal({
    state,
    classroomId,
    classroomSubjectId,
    activity,
}: Props) {
    return (
        <Modal state={state}>
            <Modal.Backdrop>
                <Modal.Container size="lg" scroll="outside">
                    <Modal.Dialog>
                        <Modal.CloseTrigger />
                        <Modal.Header>
                            <Modal.Heading>
                                {activity
                                    ? 'Editar atividade'
                                    : 'Nova atividade'}
                            </Modal.Heading>
                        </Modal.Header>
                        <ActivityForm
                            classroomId={classroomId}
                            classroomSubjectId={classroomSubjectId}
                            activity={activity}
                            onSuccess={state.close}
                            onCancel={state.close}
                        />
                    </Modal.Dialog>
                </Modal.Container>
            </Modal.Backdrop>
        </Modal>
    );
}
