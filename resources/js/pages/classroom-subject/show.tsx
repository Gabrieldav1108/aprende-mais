import { Head, setLayoutProps } from '@inertiajs/react';
import type { ClassroomSubject } from '@/components/classroom/subjects-table';
import type { Classroom } from '@/components/classroom/table';
import ClassroomSubjectStudentsTable from '@/components/classroom-subject/students-table';
import type { ClassroomSubjectStudent } from '@/components/classroom-subject/students-table';
import { classroom as classroomIndex, dashboard } from '@/routes';
import classroom from '@/routes/classroom';

type Props = {
    classroomSubject: ClassroomSubject & { classroom: Classroom };
    students: ClassroomSubjectStudent[];
    statuses: Record<string, string>;
};

export default function ClassroomSubjectShow({
    classroomSubject,
    students,
    statuses,
}: Props) {
    const { classroom: room, subject, teacher } = classroomSubject;

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Turmas', href: classroomIndex() },
            { title: room.name, href: classroom.show(room.id) },
            {
                title: subject.name,
                href: classroom.subjects.show([room.id, classroomSubject.id]),
            },
        ],
    });

    return (
        <>
            <Head title={`${subject.name} - ${room.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div>
                    <h1 className="text-xl font-semibold">{subject.name}</h1>
                    <p className="text-sm text-muted-foreground">
                        {subject.code} · {subject.workload_hours}h · {room.name}{' '}
                        · {room.academic_term?.name}
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Professor: {teacher.person.name}
                    </p>
                </div>
                <ClassroomSubjectStudentsTable
                    students={students}
                    statuses={statuses}
                />
            </div>
        </>
    );
}
