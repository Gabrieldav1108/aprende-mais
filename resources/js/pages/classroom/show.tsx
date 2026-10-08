import { Button, useOverlayState } from '@heroui/react';
import { Head, setLayoutProps } from '@inertiajs/react';
import { Pencil, Plus } from 'lucide-react';
import type { CourseOption, TermOption } from '@/components/classroom/form';
import ClassroomModal from '@/components/classroom/modal';
import type { CourseSubject } from '@/components/classroom/subject-form';
import ClassroomSubjectModal from '@/components/classroom/subject-modal';
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
    const editState = useOverlayState();
    const subjectState = useOverlayState();

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
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-semibold">{room.name}</h1>
                        <p className="text-sm text-muted-foreground">
                            {room.course?.name} · {room.academic_term?.name} ·{' '}
                            {room.term_number}º semestre ·{' '}
                            {room.classroom_students_count ?? 0}/{room.capacity}{' '}
                            vagas
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {canUpdateClassroom && (
                            <Button variant="outline" onPress={editState.open}>
                                <Pencil className="size-4" />
                                Editar turma
                            </Button>
                        )}
                        {canCreateSubjects && (
                            <Button onPress={subjectState.open}>
                                <Plus className="size-4" />
                                Ofertar matéria
                            </Button>
                        )}
                    </div>
                </div>
                <ClassroomSubjectsTable
                    classroomId={room.id}
                    classroomSubjects={classroomSubjects}
                    canRemove={canDeleteSubjects}
                />
            </div>

            {canUpdateClassroom && (
                <ClassroomModal
                    state={editState}
                    classroom={room}
                    courses={courses}
                    terms={terms}
                    teachers={teachers}
                    shifts={shifts}
                    statuses={statuses}
                />
            )}
            {canCreateSubjects && (
                <ClassroomSubjectModal
                    state={subjectState}
                    classroomId={room.id}
                    courseSubjects={courseSubjects}
                    teachers={teachers}
                />
            )}
        </>
    );
}
