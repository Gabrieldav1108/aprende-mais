import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import AcademicTermModal from '@/components/academic-term/modal';
import AcademicTermTable from '@/components/academic-term/table';
import type { AcademicTerm } from '@/components/academic-term/table';
import { useCan } from '@/hooks/use-can';
import { academicTerm as academicTermIndex, dashboard } from '@/routes';

type Props = {
    terms: AcademicTerm[];
    statuses: Record<string, string>;
};

export default function AcademicTermIndex({ terms, statuses }: Props) {
    const canCreateTerms = useCan('terms.create');
    const canUpdateTerms = useCan('terms.update');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<AcademicTerm | null>(null);

    const edit = (term: AcademicTerm) => {
        setEditing(term);
        editState.open();
    };

    return (
        <>
            <Head title="Períodos letivos" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canCreateTerms && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Novo período letivo
                        </Button>
                    </div>
                )}
                <AcademicTermTable
                    terms={terms}
                    statuses={statuses}
                    onEdit={canUpdateTerms ? edit : undefined}
                />
            </div>

            {canCreateTerms && (
                <AcademicTermModal state={createState} statuses={statuses} />
            )}
            {canUpdateTerms && editing && (
                <AcademicTermModal
                    key={editing.id}
                    state={editState}
                    term={editing}
                    statuses={statuses}
                />
            )}
        </>
    );
}

AcademicTermIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Períodos letivos', href: academicTermIndex() },
    ],
};
