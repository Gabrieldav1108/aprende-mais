import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import StudentModal from '@/components/student/modal';
import StudentTable from '@/components/student/table';
import type { Student } from '@/components/student/table';
import { useCan } from '@/hooks/use-can';
import { dashboard, student as studentIndex } from '@/routes';

type Props = {
    students: Student[];
    people: { id: number; name: string }[];
    courses: { id: number; name: string }[];
    statuses: Record<string, string>;
};

export default function StudentIndex({
    students,
    people,
    courses,
    statuses,
}: Props) {
    const canCreateStudents = useCan('students.create');
    const canUpdateStudents = useCan('students.update');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Student | null>(null);

    const edit = (student: Student) => {
        setEditing(student);
        editState.open();
    };

    return (
        <>
            <Head title="Alunos" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canCreateStudents && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Novo aluno
                        </Button>
                    </div>
                )}
                <StudentTable
                    students={students}
                    statuses={statuses}
                    onEdit={canUpdateStudents ? edit : undefined}
                />
            </div>

            {canCreateStudents && (
                <StudentModal
                    state={createState}
                    people={people}
                    courses={courses}
                    statuses={statuses}
                />
            )}
            {canUpdateStudents && editing && (
                <StudentModal
                    key={editing.id}
                    state={editState}
                    student={editing}
                    people={people}
                    courses={courses}
                    statuses={statuses}
                />
            )}
        </>
    );
}

StudentIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Alunos', href: studentIndex() },
    ],
};
