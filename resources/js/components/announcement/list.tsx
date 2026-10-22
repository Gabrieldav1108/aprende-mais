import { Button, Card, Chip, Dropdown, EmptyState } from '@heroui/react';
import { router } from '@inertiajs/react';
import { Mail, MoreHorizontal } from 'lucide-react';
import { formatShortDateTime, formatShortMonth } from '@/lib/format';
import classroom from '@/routes/classroom';

export type Announcement = {
    id: number;
    title: string;
    body: string;
    is_pinned: boolean;
    sent_by_email: boolean;
    created_at: string;
    author: { id: number; name: string } | null;
};

type Props = {
    classroomId: number;
    classroomSubjectId: number;
    announcements: Announcement[];
    onEdit?: (announcement: Announcement) => void;
};

/**
 * The classroom subject's announcements, pinned first and then newest first, each with its posting date
 * beside it; the edit and delete menu only shows for whoever manages them.
 */
export default function AnnouncementList({
    classroomId,
    classroomSubjectId,
    announcements,
    onEdit,
}: Props) {
    if (announcements.length === 0) {
        return (
            <EmptyState className="flex w-full flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-10 text-center">
                <span className="text-sm text-muted-foreground">
                    Nenhum aviso publicado nesta matéria
                </span>
            </EmptyState>
        );
    }

    const destroy = (announcement: Announcement) => {
        router.delete(
            classroom.subjects.announcements.destroy.url([
                classroomId,
                classroomSubjectId,
                announcement.id,
            ]),
            { preserveScroll: true },
        );
    };

    return (
        <Card className="gap-0 overflow-hidden p-0">
            <ul className="divide-y divide-border">
                {announcements.map((announcement) => (
                    <li
                        key={announcement.id}
                        className="flex gap-4 px-5 py-4 sm:gap-8"
                    >
                        <div className="flex w-10 shrink-0 flex-col items-center gap-1 pt-0.5">
                            <span className="text-xl leading-none font-semibold text-foreground">
                                {new Date(announcement.created_at).getDate()}
                            </span>
                            <span className="text-[11px] tracking-wide text-muted-foreground uppercase">
                                {formatShortMonth(announcement.created_at)}
                            </span>
                        </div>

                        <div className="flex min-w-0 flex-1 flex-col gap-1">
                            <div className="flex flex-wrap items-center gap-2">
                                <h3 className="font-semibold text-foreground">
                                    {announcement.title}
                                </h3>
                                {announcement.is_pinned && (
                                    <Chip
                                        size="sm"
                                        color="warning"
                                        variant="soft"
                                    >
                                        Fixado
                                    </Chip>
                                )}
                            </div>
                            <p className="text-sm whitespace-pre-line text-foreground/80">
                                {announcement.body}
                            </p>
                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                {announcement.author?.name ?? 'Professor'} ·{' '}
                                {formatShortDateTime(announcement.created_at)}
                                {announcement.sent_by_email && (
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
                                    aria-label={`Ações do aviso ${announcement.title}`}
                                >
                                    <MoreHorizontal className="size-4" />
                                </Button>
                                <Dropdown.Popover placement="bottom end">
                                    <Dropdown.Menu
                                        onAction={(key) =>
                                            key === 'edit'
                                                ? onEdit(announcement)
                                                : destroy(announcement)
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
