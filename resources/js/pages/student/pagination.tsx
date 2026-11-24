import { Button } from '@heroui/react';
import { router } from '@inertiajs/react';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export default function StudentPagination({
    paginator,
}: {
    paginator: Paginated<unknown>;
}) {
    const go = (url: string | null) => {
        if (url) {
            router.get(url, {}, { preserveState: true, preserveScroll: true });
        }
    };

    return (
        <div className="flex items-center justify-between text-sm">
            <span>
                {paginator.from ?? 0}–{paginator.to ?? 0} de {paginator.total}
            </span>

            <div className="flex items-center gap-3">
                <Button
                    size="sm"
                    variant="secondary"
                    isDisabled={!paginator.prev_page_url}
                    onPress={() => go(paginator.prev_page_url)}
                >
                    Anterior
                </Button>

                <span>
                    Página {paginator.current_page} de {paginator.last_page}
                </span>

                <Button
                    size="sm"
                    variant="secondary"
                    isDisabled={!paginator.next_page_url}
                    onPress={() => go(paginator.next_page_url)}
                >
                    Próxima
                </Button>
            </div>
        </div>
    );
}