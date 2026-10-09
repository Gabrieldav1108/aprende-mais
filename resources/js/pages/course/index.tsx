import { Button, useOverlayState } from '@heroui/react';
import { Head } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import type { SubjectOption } from '@/components/course/form';
import CourseModal from '@/components/course/modal';
import CourseTable from '@/components/course/table';
import type { Course } from '@/components/course/table';
import { useCan } from '@/hooks/use-can';
import { course as courseIndex, dashboard } from '@/routes';

type Props = {
    courses: Course[];
    subjectOptions: SubjectOption[];
    levels: Record<string, string>;
    statuses: Record<string, string>;
};

export default function CourseIndex({
    courses,
    subjectOptions,
    levels,
    statuses,
}: Props) {
    const canCreateCourses = useCan('courses.create');
    const canUpdateCourses = useCan('courses.update');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Course | null>(null);

    const edit = (course: Course) => {
        setEditing(course);
        editState.open();
    };

    return (
        <>
            <Head title="Cursos" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {canCreateCourses && (
                    <div className="flex justify-end">
                        <Button onPress={createState.open}>
                            <Plus className="size-4" />
                            Novo curso
                        </Button>
                    </div>
                )}
                <CourseTable
                    courses={courses}
                    levels={levels}
                    statuses={statuses}
                    onEdit={canUpdateCourses ? edit : undefined}
                />
            </div>

            {canCreateCourses && (
                <CourseModal
                    state={createState}
                    subjectOptions={subjectOptions}
                    levels={levels}
                    statuses={statuses}
                />
            )}
            {canUpdateCourses && editing && (
                <CourseModal
                    key={editing.id}
                    state={editState}
                    course={editing}
                    subjectOptions={subjectOptions}
                    levels={levels}
                    statuses={statuses}
                />
            )}
        </>
    );
}

CourseIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Cursos', href: courseIndex() },
    ],
};
