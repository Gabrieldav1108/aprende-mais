import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import type { SubjectOptions } from '@/components/subject/form';
import SubjectModal from '@/components/subject/modal';
import SubjectTable from '@/components/subject/table';
import type { Subject } from '@/components/subject/table';
import { useCan } from '@/hooks/use-can';
import { dashboard, subject as subjectIndex } from '@/routes';

type Props = SubjectOptions & {
    subjects: Subject[];
};

export default function SubjectIndex({ subjects, ...options }: Props) {
    const canCreateSubjects = useCan('subjects.create');
    const canUpdateSubjects = useCan('subjects.update');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Subject | null>(null);

    const edit = (subject: Subject) => {
        setEditing(subject);
        editState.open();
    };

    return (
        <>
            <Head title="Matérias" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canCreateSubjects && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Nova matéria
                        </Button>
                    </div>
                )}
                <SubjectTable
                    subjects={subjects}
                    onEdit={canUpdateSubjects ? edit : undefined}
                />
            </div>

            {canCreateSubjects && (
                <SubjectModal state={createState} {...options} />
            )}
            {canUpdateSubjects && editing && (
                <SubjectModal
                    key={editing.id}
                    state={editState}
                    subject={editing}
                    {...options}
                />
            )}
        </>
    );
}

SubjectIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Matérias', href: subjectIndex() },
    ],
};
