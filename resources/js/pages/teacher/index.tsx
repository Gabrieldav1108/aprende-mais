import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import TeacherModal from '@/components/teacher/modal';
import TeacherTable from '@/components/teacher/table';
import type { Teacher } from '@/components/teacher/table';
import { useCan } from '@/hooks/use-can';
import { dashboard, teacher as teacherIndex } from '@/routes';

type Props = {
    teachers: Teacher[];
};

export default function TeacherIndex({ teachers }: Props) {
    const canCreateTeachers = useCan('teachers.create');
    const canUpdateTeachers = useCan('teachers.update');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Teacher | null>(null);

    const edit = (teacher: Teacher) => {
        setEditing(teacher);
        editState.open();
    };

    return (
        <>
            <Head title="Professores" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canCreateTeachers && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Novo professor
                        </Button>
                    </div>
                )}
                <TeacherTable
                    teachers={teachers}
                    onEdit={canUpdateTeachers ? edit : undefined}
                />
            </div>

            {canCreateTeachers && <TeacherModal state={createState} />}
            {canUpdateTeachers && editing && (
                <TeacherModal
                    key={editing.id}
                    state={editState}
                    teacher={editing}
                />
            )}
        </>
    );
}

TeacherIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Professores', href: teacherIndex() },
    ],
};
