import { Head, setLayoutProps } from '@inertiajs/react';
import ClassroomForm from '@/components/classroom/form';
import ClassroomSubjectForm from '@/components/classroom/subject-form';
import ClassroomSubjectsTable from '@/components/classroom/subjects-table';
import type {
    ClassroomSubject,
    Teacher,
} from '@/components/classroom/subjects-table';
import type { Classroom } from '@/components/classroom/table';
import { classroom as classroomIndex, dashboard } from '@/routes';
import classroom from '@/routes/classroom';

type Props = {
    classroom: Pick<Classroom, 'id' | 'name' | 'school_year' | 'shift'>;
    classroomSubjects: ClassroomSubject[];
    teachers: Teacher[];
};

export default function ClassroomShow({
    classroom: room,
    classroomSubjects,
    teachers,
}: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Turmas', href: classroomIndex() },
            { title: room.name, href: classroom.show(room.id) },
        ],
    });

    return (
        <>
            <Head title={room.name} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <ClassroomForm classroom={room} />
                <ClassroomSubjectForm
                    classroomId={room.id}
                    teachers={teachers}
                />
                <ClassroomSubjectsTable
                    classroomId={room.id}
                    classroomSubjects={classroomSubjects}
                />
            </div>
        </>
    );
}
