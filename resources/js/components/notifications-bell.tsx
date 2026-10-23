import { Badge, Button, Popover } from '@heroui/react';
import { router, usePage } from '@inertiajs/react';
import { Bell, CircleAlert } from 'lucide-react';
import { useState } from 'react';
import { formatDateTime } from '@/lib/format';
import notification from '@/routes/notification';
import type { AppNotification } from '@/types';

/**
 * Header bell with the unread count; opening a notification marks it as read and visits its page.
 */
export function NotificationsBell() {
    const { notifications } = usePage().props;
    const [isOpen, setIsOpen] = useState(false);

    if (!notifications) {
        return null;
    }

    const { unread_count: unreadCount, latest } = notifications;

    const open = (item: AppNotification) => {
        setIsOpen(false);
        router.post(notification.read.url(item.id));
    };

    const readAll = () => {
        router.post(notification.readAll.url(), {}, { preserveScroll: true });
    };

    return (
        <Popover isOpen={isOpen} onOpenChange={setIsOpen}>
            <Badge.Anchor>
                <Button
                    isIconOnly
                    variant="ghost"
                    size="sm"
                    aria-label={
                        unreadCount > 0
                            ? `Notificações (${unreadCount} não lidas)`
                            : 'Notificações'
                    }
                >
                    <Bell className="size-5" />
                </Button>
                {unreadCount > 0 && (
                    <Badge color="danger" size="sm">
                        {unreadCount > 9 ? '9+' : unreadCount}
                    </Badge>
                )}
            </Badge.Anchor>
            <Popover.Content placement="bottom end" className="w-80">
                <Popover.Dialog aria-label="Notificações" className="p-0">
                    <div className="flex items-center justify-between gap-2 border-b px-4 py-3">
                        <span className="text-sm font-semibold text-foreground">
                            Notificações
                        </span>
                        {unreadCount > 0 && (
                            <button
                                type="button"
                                onClick={readAll}
                                className="text-xs text-muted-foreground hover:text-foreground hover:underline"
                            >
                                Marcar todas como lidas
                            </button>
                        )}
                    </div>
                    {latest.length === 0 ? (
                        <p className="px-4 py-6 text-center text-sm text-muted-foreground">
                            Nenhuma notificação
                        </p>
                    ) : (
                        <ul className="max-h-96 overflow-y-auto">
                            {latest.map((item) => (
                                <li key={item.id}>
                                    <button
                                        type="button"
                                        onClick={() => open(item)}
                                        className="flex w-full items-start gap-2 px-4 py-3 text-left hover:bg-muted"
                                    >
                                        {item.read_at ? (
                                            <span className="size-4 shrink-0" />
                                        ) : (
                                            <CircleAlert
                                                className="mt-0.5 size-4 shrink-0 text-amber-500 dark:text-amber-400"
                                                aria-label="Novo aviso"
                                            />
                                        )}
                                        <span className="min-w-0">
                                            <span className="block truncate text-sm font-medium text-foreground">
                                                {item.data.title}
                                            </span>
                                            {item.data.subject && (
                                                <span className="block truncate text-xs text-muted-foreground">
                                                    Novo aviso em{' '}
                                                    {item.data.subject}
                                                    {item.data.classroom &&
                                                        ` · ${item.data.classroom}`}
                                                </span>
                                            )}
                                            <span className="block text-xs text-muted-foreground">
                                                {formatDateTime(
                                                    item.created_at,
                                                )}
                                            </span>
                                        </span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    )}
                </Popover.Dialog>
            </Popover.Content>
        </Popover>
    );
}
