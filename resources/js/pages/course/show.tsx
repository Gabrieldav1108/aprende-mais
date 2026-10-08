import { Button, useOverlayState } from '@heroui/react';
import { Head, setLayoutProps } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import CourseModal from '@/components/course/modal';
import CourseSubjectModal from '@/components/course/subject-modal';
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
    const editState = useOverlayState();
    const subjectState = useOverlayState();

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
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold">
                            {current.name}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {current.code} · {levels[current.level]} ·{' '}
                            {current.duration_terms} semestres ·{' '}
                            {statuses[current.status]}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {canUpdateCourse && (
                            <Button variant="outline" onPress={editState.open}>
                                <Pencil className="size-4" />
                                Editar curso
                            </Button>
                        )}
                        {canCreateSubjects && (
                            <Button onPress={subjectState.open}>
                                <Plus className="size-4" />
                                Nova matéria
                            </Button>
                        )}
                    </div>
                </div>
                <CourseSubjectsTable subjects={subjects} />
            </div>

            {canUpdateCourse && (
                <CourseModal
                    state={editState}
                    course={current}
                    levels={levels}
                    statuses={statuses}
                />
            )}
            {canCreateSubjects && (
                <CourseSubjectModal
                    state={subjectState}
                    courseId={current.id}
                    durationTerms={current.duration_terms}
                />
            )}
        </>
    );
}
