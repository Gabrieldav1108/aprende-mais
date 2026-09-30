import { Head } from '@inertiajs/react';
import AcademicTermForm from '@/components/academic-term/form';
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

    return (
        <>
            <Head title="Períodos letivos" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {canCreateTerms && <AcademicTermForm statuses={statuses} />}
                <AcademicTermTable terms={terms} statuses={statuses} />
            </div>
        </>
    );
}

AcademicTermIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Períodos letivos', href: academicTermIndex() },
    ],
};
