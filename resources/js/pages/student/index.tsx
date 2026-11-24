import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import StudentFilterBar from '@/components/student/filters';
import type { StudentFilters } from '@/components/student/filters';
import StudentModal from '@/components/student/modal';
import StudentPagination from '@/components/student/pagination';
import type { Paginated } from '@/components/student/pagination';
import StudentTable from '@/components/student/table';
import type { Student } from '@/components/student/table';
import { useCan } from '@/hooks/use-can';
import { dashboard, student as studentIndex } from '@/routes';

type Props = {
    students: Paginated<Student>;
    filters: StudentFilters;
    people: { id: number; name: string }[];
    courses: { id: number; name: string }[];
    statuses: Record<string, string>;
};

export default function StudentIndex({
    students,
    filters,
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
                <div className="flex items-end justify-between gap-4">
                    <StudentFilterBar
                        filters={filters}
                        courses={courses}
                        statuses={statuses}
                    />

                    {canCreateStudents && (
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Novo aluno
                        </Button>
                    )}
                </div>

                <StudentTable
                    students={students.data}
                    statuses={statuses}
                    onEdit={canUpdateStudents ? edit : undefined}
                />

                <StudentPagination paginator={students} />
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