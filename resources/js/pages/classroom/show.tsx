import { Head, setLayoutProps } from '@inertiajs/react';
import ClassroomForm from '@/components/classroom/form';
import type { CourseOption, TermOption } from '@/components/classroom/form';
import ClassroomSubjectForm from '@/components/classroom/subject-form';
import type { CourseSubject } from '@/components/classroom/subject-form';
import ClassroomSubjectsTable from '@/components/classroom/subjects-table';
import type { ClassroomSubject } from '@/components/classroom/subjects-table';
import type { Classroom, Teacher } from '@/components/classroom/table';
import { useCan } from '@/hooks/use-can';
import { classroom as classroomIndex, dashboard } from '@/routes';
import classroom from '@/routes/classroom';

type Props = {
    classroom: Classroom;
    classroomSubjects: ClassroomSubject[];
    courseSubjects: CourseSubject[];
    courses: CourseOption[];
    terms: TermOption[];
    teachers: Teacher[];
    shifts: Record<string, string>;
    statuses: Record<string, string>;
};

export default function ClassroomShow({
    classroom: room,
    classroomSubjects,
    courseSubjects,
    courses,
    terms,
    teachers,
    shifts,
    statuses,
}: Props) {
    const canUpdateClassroom = useCan('classrooms.update');
    const canCreateSubjects = useCan('subjects.create');
    const canDeleteSubjects = useCan('subjects.delete');

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
                <div className="text-sm text-muted-foreground">
                    {room.course?.name} · {room.academic_term?.name} ·{' '}
                    {room.term_number}º semestre ·{' '}
                    {room.classroom_students_count ?? 0}/{room.capacity} vagas
                </div>
                {canUpdateClassroom && (
                    <ClassroomForm
                        classroom={room}
                        courses={courses}
                        terms={terms}
                        teachers={teachers}
                        shifts={shifts}
                        statuses={statuses}
                    />
                )}
                {canCreateSubjects && (
                    <ClassroomSubjectForm
                        classroomId={room.id}
                        courseSubjects={courseSubjects}
                        teachers={teachers}
                    />
                )}
                <ClassroomSubjectsTable
                    classroomId={room.id}
                    classroomSubjects={classroomSubjects}
                    canRemove={canDeleteSubjects}
                />
            </div>
        </>
    );
}
