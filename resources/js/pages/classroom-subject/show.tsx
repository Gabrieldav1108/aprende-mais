import { Button, useOverlayState } from '@heroui/react';
import { Head, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useState } from 'react';
import AnnouncementList from '@/components/announcement/list';
import type { Announcement } from '@/components/announcement/list';
import AnnouncementModal from '@/components/announcement/modal';
import type { ClassroomSubject } from '@/components/classroom/subjects-table';
import type { Classroom } from '@/components/classroom/table';
import ClassroomSubjectStudentsTable from '@/components/classroom-subject/students-table';
import type { ClassroomSubjectStudent } from '@/components/classroom-subject/students-table';
import { useCan } from '@/hooks/use-can';
import {
    classroom as classroomIndex,
    dashboard,
    enrolledSubject,
} from '@/routes';
import classroom from '@/routes/classroom';

type Props = {
    classroomSubject: ClassroomSubject & { classroom: Classroom };
    announcements: Announcement[];
    canManageAnnouncements: boolean;
    students?: ClassroomSubjectStudent[];
    statuses?: Record<string, string>;
};

export default function ClassroomSubjectShow({
    classroomSubject,
    announcements,
    canManageAnnouncements,
    students,
    statuses,
}: Props) {
    const { classroom: room, subject, teacher } = classroomSubject;
    const canViewClassrooms = useCan('classrooms.view');
    const createState = useOverlayState();
    const editState = useOverlayState();
    const [editing, setEditing] = useState<Announcement | null>(null);

    const edit = (announcement: Announcement) => {
        setEditing(announcement);
        editState.open();
    };

    const subjectHref = classroom.subjects.show([room.id, classroomSubject.id]);

    setLayoutProps({
        breadcrumbs: canViewClassrooms
            ? [
                  { title: 'Dashboard', href: dashboard() },
                  { title: 'Turmas', href: classroomIndex() },
                  { title: room.name, href: classroom.show(room.id) },
                  { title: subject.name, href: subjectHref },
              ]
            : [
                  { title: 'Dashboard', href: dashboard() },
                  { title: 'Minhas matérias', href: enrolledSubject() },
                  { title: subject.name, href: subjectHref },
              ],
    });

    return (
        <>
            <Head title={`${subject.name} - ${room.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
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

                <section className="flex flex-col gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-2">
                        <h2 className="text-lg font-semibold">Avisos</h2>
                        {canManageAnnouncements && (
                            <Button onPress={createState.open}>
                                <Plus className="size-4" />
                                Novo aviso
                            </Button>
                        )}
                    </div>
                    <AnnouncementList
                        classroomId={room.id}
                        classroomSubjectId={classroomSubject.id}
                        announcements={announcements}
                        onEdit={canManageAnnouncements ? edit : undefined}
                    />
                </section>

                {students && statuses && (
                    <section className="flex flex-col gap-3">
                        <h2 className="text-lg font-semibold">Alunos</h2>
                        <ClassroomSubjectStudentsTable
                            students={students}
                            statuses={statuses}
                        />
                    </section>
                )}
            </div>

            {canManageAnnouncements && (
                <AnnouncementModal
                    state={createState}
                    classroomId={room.id}
                    classroomSubjectId={classroomSubject.id}
                />
            )}
            {canManageAnnouncements && editing && (
                <AnnouncementModal
                    key={editing.id}
                    state={editState}
                    classroomId={room.id}
                    classroomSubjectId={classroomSubject.id}
                    announcement={editing}
                />
            )}
        </>
    );
}
