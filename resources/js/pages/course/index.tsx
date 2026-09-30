import { Head } from '@inertiajs/react';
import CourseForm from '@/components/course/form';
import CourseTable from '@/components/course/table';
import type { Course } from '@/components/course/table';
import { useCan } from '@/hooks/use-can';
import { course as courseIndex, dashboard } from '@/routes';

type Props = {
    courses: Course[];
    levels: Record<string, string>;
    statuses: Record<string, string>;
};

export default function CourseIndex({ courses, levels, statuses }: Props) {
    const canCreateCourses = useCan('courses.create');

    return (
        <>
            <Head title="Cursos" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {canCreateCourses && (
                    <CourseForm levels={levels} statuses={statuses} />
                )}
                <CourseTable
                    courses={courses}
                    levels={levels}
                    statuses={statuses}
                />
            </div>
        </>
    );
}

CourseIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Cursos', href: courseIndex() },
    ],
};
