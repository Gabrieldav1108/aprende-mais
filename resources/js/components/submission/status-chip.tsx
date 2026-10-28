import { Chip } from '@heroui/react';
import type { AttachmentFile } from '@/components/attachment-list';

export type Submission = {
    id: number;
    content: string | null;
    created_at: string;
    attachments: AttachmentFile[];
};

/**
 * Whether the version was sent after the due date of the activity.
 */
export function isLateSubmission(
    submission: Submission,
    dueAt: string | null,
): boolean {
    return (
        dueAt !== null &&
        new Date(submission.created_at).getTime() > new Date(dueAt).getTime()
    );
}

type Props = {
    submission: Submission | undefined;
    dueAt: string | null;
    now: number;
};

/**
 * Where the student stands on the activity, from their current answer: sent, sent late, pending or missed.
 */
export default function SubmissionStatusChip({
    submission,
    dueAt,
    now,
}: Props) {
    if (submission) {
        const isLate = isLateSubmission(submission, dueAt);

        return (
            <Chip
                size="sm"
                variant="soft"
                color={isLate ? 'warning' : 'success'}
            >
                {isLate ? 'Entregue com atraso' : 'Entregue'}
            </Chip>
        );
    }

    const isMissed = dueAt !== null && new Date(dueAt).getTime() < now;

    return (
        <Chip size="sm" variant="soft" color={isMissed ? 'danger' : 'default'}>
            {isMissed ? 'Não entregue' : 'Pendente'}
        </Chip>
    );
}
