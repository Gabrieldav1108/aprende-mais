import { Button, Card, EmptyState } from '@heroui/react';
import { Link } from '@inertiajs/react';
import { Mail, Pencil } from 'lucide-react';
import { formatDateTime } from '@/lib/format';
import classroom from '@/routes/classroom';

export type Announcement = {
    id: number;
    title: string;
    body: string;
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
 * The classroom subject's announcements, newest first; edit and delete only show for whoever manages them.
 */
export default function AnnouncementList({
    classroomId,
    classroomSubjectId,
    announcements,
    onEdit,
}: Props) {
    if (announcements.length === 0) {
        return (
            <EmptyState className="flex w-full flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-8 text-center">
                <span className="text-sm text-muted-foreground">
                    Nenhum aviso publicado nesta matéria
                </span>
            </EmptyState>
        );
    }

    return (
        <div className="flex flex-col gap-3">
            {announcements.map((announcement) => (
                <Card key={announcement.id}>
                    <Card.Header className="flex flex-row flex-wrap items-start justify-between gap-2">
                        <div className="min-w-0">
                            <Card.Title>{announcement.title}</Card.Title>
                            <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                {announcement.author?.name ?? 'Professor'} ·{' '}
                                {formatDateTime(announcement.created_at)}
                                {announcement.sent_by_email && (
                                    <Mail
                                        className="ml-1 size-3"
                                        aria-label="Enviado por e-mail"
                                    />
                                )}
                            </p>
                        </div>
                        {onEdit && (
                            <div className="flex items-center gap-3">
                                <Button
                                    size="sm"
                                    variant="ghost"
                                    onPress={() => onEdit(announcement)}
                                >
                                    <Pencil className="size-4" />
                                    Editar
                                </Button>
                                <Link
                                    href={classroom.subjects.announcements.destroy(
                                        [
                                            classroomId,
                                            classroomSubjectId,
                                            announcement.id,
                                        ],
                                    )}
                                    method="delete"
                                    as="button"
                                    className="text-sm text-destructive hover:underline"
                                >
                                    Excluir
                                </Link>
                            </div>
                        )}
                    </Card.Header>
                    <Card.Content>
                        <p className="text-sm whitespace-pre-line text-foreground">
                            {announcement.body}
                        </p>
                    </Card.Content>
                </Card>
            ))}
        </div>
    );
}
