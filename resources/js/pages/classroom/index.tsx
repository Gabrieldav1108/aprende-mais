import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import type { CourseOption, TermOption } from '@/components/classroom/form';
import ClassroomModal from '@/components/classroom/modal';
import ClassroomTable from '@/components/classroom/table';
import type { Classroom, Teacher } from '@/components/classroom/table';
import { useCan } from '@/hooks/use-can';
import { classroom as classroomIndex, dashboard } from '@/routes';

type Props = {
    classrooms: Classroom[];
    courses: CourseOption[];
    terms: TermOption[];
    teachers: Teacher[];
    shifts: Record<string, string>;
    statuses: Record<string, string>;
};

export default function ClassroomIndex({
    classrooms,
    courses,
    terms,
    teachers,
    shifts,
    statuses,
}: Props) {
    const canCreateClassrooms = useCan('classrooms.create');
    const canUpdateClassrooms = useCan('classrooms.update');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Classroom | null>(null);

    const edit = (classroom: Classroom) => {
        setEditing(classroom);
        editState.open();
    };

    return (
        <>
            <Head title="Turmas" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canCreateClassrooms && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Nova turma
                        </Button>
                    </div>
                )}
                <ClassroomTable
                    classrooms={classrooms}
                    shifts={shifts}
                    statuses={statuses}
                    onEdit={canUpdateClassrooms ? edit : undefined}
                />
            </div>

            {canCreateClassrooms && (
                <ClassroomModal
                    state={createState}
                    courses={courses}
                    terms={terms}
                    teachers={teachers}
                    shifts={shifts}
                    statuses={statuses}
                />
            )}
            {canUpdateClassrooms && editing && (
                <ClassroomModal
                    key={editing.id}
                    state={editState}
                    classroom={editing}
                    courses={courses}
                    terms={terms}
                    teachers={teachers}
                    shifts={shifts}
                    statuses={statuses}
                />
            )}
        </>
    );
}

ClassroomIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Turmas', href: classroomIndex() },
    ],
};
