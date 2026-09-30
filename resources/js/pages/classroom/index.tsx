import { Head } from '@inertiajs/react';
import ClassroomForm from '@/components/classroom/form';
import type { CourseOption, TermOption } from '@/components/classroom/form';
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

    return (
        <>
            <Head title="Turmas" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {canCreateClassrooms && (
                    <ClassroomForm
                        courses={courses}
                        terms={terms}
                        teachers={teachers}
                        shifts={shifts}
                        statuses={statuses}
                    />
                )}
                <ClassroomTable
                    classrooms={classrooms}
                    shifts={shifts}
                    statuses={statuses}
                />
            </div>
        </>
    );
}

ClassroomIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Turmas', href: classroomIndex() },
    ],
};
