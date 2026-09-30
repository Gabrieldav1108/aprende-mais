import { Head, setLayoutProps } from '@inertiajs/react';
import CourseForm from '@/components/course/form';
import CourseSubjectForm from '@/components/course/subject-form';
import CourseSubjectsTable from '@/components/course/subjects-table';
import type { CatalogSubject } from '@/components/course/subjects-table';
import type { Course } from '@/components/course/table';
import { useCan } from '@/hooks/use-can';
import { course as courseIndex, dashboard } from '@/routes';
import course from '@/routes/course';

type Props = {
    course: Course;
    subjects: CatalogSubject[];
    levels: Record<string, string>;
    statuses: Record<string, string>;
};

export default function CourseShow({
    course: current,
    subjects,
    levels,
    statuses,
}: Props) {
    const canUpdateCourse = useCan('courses.update');
    const canCreateSubjects = useCan('subjects.create');

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Cursos', href: courseIndex() },
            { title: current.name, href: course.show(current.id) },
        ],
    });

    return (
        <>
            <Head title={current.name} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                {canUpdateCourse && (
                    <CourseForm
                        course={current}
                        levels={levels}
                        statuses={statuses}
                    />
                )}
                {canCreateSubjects && (
                    <CourseSubjectForm
                        courseId={current.id}
                        durationTerms={current.duration_terms}
                    />
                )}
                <CourseSubjectsTable subjects={subjects} />
            </div>
        </>
    );
}
