import { Head } from '@inertiajs/react';
import ClassroomForm from '@/components/classroom/form';
import ClassroomTable from '@/components/classroom/table';
import type { Classroom } from '@/components/classroom/table';
import { useCan } from '@/hooks/use-can';
import { classroom as classroomIndex, dashboard } from '@/routes';

export default function ClassroomIndex({
    classrooms,
}: {
    classrooms: Classroom[];
}) {
    const canCreateClassrooms = useCan('classrooms.create');

    return (
        <>
            <Head title="Turmas" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {canCreateClassrooms && <ClassroomForm />}
                <ClassroomTable classrooms={classrooms} />
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
