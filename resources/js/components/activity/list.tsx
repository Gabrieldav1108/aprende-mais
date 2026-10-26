import { Button, Card, Chip, Dropdown, EmptyState } from '@heroui/react';
import { router } from '@inertiajs/react';
import { CalendarClock, Mail, MoreHorizontal } from 'lucide-react';
import { useState } from 'react';
import AttachmentList from '@/components/attachment-list';
import type { AttachmentFile } from '@/components/attachment-list';
import { formatShortDateTime } from '@/lib/format';
import classroom from '@/routes/classroom';

export type Activity = {
    id: number;
    title: string;
    description: string;
    due_at: string | null;
    sent_by_email: boolean;
    created_at: string;
    author: { id: number; name: string } | null;
    attachments: AttachmentFile[];
};

type Props = {
    classroomId: number;
    classroomSubjectId: number;
    activities: Activity[];
    onEdit?: (activity: Activity) => void;
};

/**
 * The due date of the activity, in red once it has passed.
 */
function DueChip({ dueAt, now }: { dueAt: string; now: number }) {
    const isClosed = new Date(dueAt).getTime() < now;

    return (
        <Chip size="sm" variant="soft" color={isClosed ? 'danger' : 'default'}>
            <CalendarClock className="size-3" />
            {isClosed ? 'Encerrada em' : 'Entrega até'}{' '}
            {formatShortDateTime(dueAt)}
        </Chip>
    );
}

/**
 * The classroom subject's activities, newest first, each with its due date and supporting files;
 * the edit and delete menu only shows for whoever manages them.
 */
export default function ActivityList({
    classroomId,
    classroomSubjectId,
    activities,
    onEdit,
}: Props) {
    const [now] = useState(() => Date.now());

    if (activities.length === 0) {
        return (
            <EmptyState className="flex w-full flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-10 text-center">
                <span className="text-sm text-muted-foreground">
                    Nenhuma atividade publicada nesta matéria
                </span>
            </EmptyState>
        );
    }

    const destroy = (activity: Activity) => {
        router.delete(
            classroom.subjects.activities.destroy.url([
                classroomId,
                classroomSubjectId,
                activity.id,
            ]),
            { preserveScroll: true },
        );
    };

    return (
        <Card className="gap-0 overflow-hidden p-0">
            <ul className="divide-y divide-border">
                {activities.map((activity) => (
                    <li key={activity.id} className="flex gap-4 px-5 py-4">
                        <div className="flex min-w-0 flex-1 flex-col gap-2">
                            <div className="flex flex-wrap items-center gap-2">
                                <h3 className="font-semibold text-foreground">
                                    {activity.title}
                                </h3>
                                {activity.due_at && (
                                    <DueChip
                                        dueAt={activity.due_at}
                                        now={now}
                                    />
                                )}
                            </div>
                            <p className="text-sm whitespace-pre-line text-foreground/80">
                                {activity.description}
                            </p>
                            <AttachmentList files={activity.attachments} />
                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                {activity.author?.name ?? 'Professor'} ·{' '}
                                {formatShortDateTime(activity.created_at)}
                                {activity.sent_by_email && (
                                    <Mail
                                        className="ml-1 size-3"
                                        aria-label="Enviado por e-mail"
                                    />
                                )}
                            </p>
                        </div>

                        {onEdit && (
                            <Dropdown>
                                <Button
                                    isIconOnly
                                    size="sm"
                                    variant="ghost"
                                    aria-label={`Ações da atividade ${activity.title}`}
                                >
                                    <MoreHorizontal className="size-4" />
                                </Button>
                                <Dropdown.Popover placement="bottom end">
                                    <Dropdown.Menu
                                        onAction={(key) =>
                                            key === 'edit'
                                                ? onEdit(activity)
                                                : destroy(activity)
                                        }
                                    >
                                        <Dropdown.Item
                                            id="edit"
                                            textValue="Editar"
                                        >
                                            Editar
                                        </Dropdown.Item>
                                        <Dropdown.Item
                                            id="delete"
                                            textValue="Excluir"
                                            variant="danger"
                                        >
                                            Excluir
                                        </Dropdown.Item>
                                    </Dropdown.Menu>
                                </Dropdown.Popover>
                            </Dropdown>
                        )}
                    </li>
                ))}
            </ul>
        </Card>
    );
}
