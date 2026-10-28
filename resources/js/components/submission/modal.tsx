import { Modal } from '@heroui/react';
import type { useOverlayState } from '@heroui/react';
import type { Activity } from '@/components/activity/list';
import SubmissionForm from '@/components/submission/form';

type Props = {
    state: ReturnType<typeof useOverlayState>;
    classroomId: number;
    classroomSubjectId: number;
    activity: Activity;
};

/**
 * Modal used both to send the first answer to an activity and to change it.
 */
export default function SubmissionModal({
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
                                {activity.submissions?.length
                                    ? 'Alterar entrega'
                                    : 'Entregar atividade'}
                            </Modal.Heading>
                            <p className="text-sm text-muted-foreground">
                                {activity.title}
                            </p>
                        </Modal.Header>
                        <SubmissionForm
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
