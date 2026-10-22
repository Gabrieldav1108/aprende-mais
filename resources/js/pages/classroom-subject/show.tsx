import { Button, Chip, Tabs, useOverlayState } from '@heroui/react';
import { Head, setLayoutProps } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import type { ReactNode } from 'react';
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
    classroomSubject: ClassroomSubject & {
        classroom: Classroom & {
            course?: { id: number; name: string; code: string };
        };
    };
    announcements: Announcement[];
    canManageAnnouncements: boolean;
    shifts: Record<string, string>;
    students?: ClassroomSubjectStudent[];
    statuses?: Record<string, string>;
};

/**
 * Title and description of a tab panel, with its main action on the right.
 */
function PanelHeader({
    title,
    description,
    action,
}: {
    title: string;
    description: string;
    action?: ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-end justify-between gap-4">
            <div className="flex flex-col gap-0.5">
                <h2 className="text-lg font-semibold text-foreground">
                    {title}
                </h2>
                <p className="text-sm text-muted-foreground">{description}</p>
            </div>
            {action}
        </div>
    );
}

/**
 * The number of items shown beside a tab label.
 */
function TabCount({ children }: { children: ReactNode }) {
    return (
        <span className="rounded-full bg-muted px-1.5 text-xs font-medium text-muted-foreground tabular-nums">
            {children}
        </span>
    );
}

export default function ClassroomSubjectShow({
    classroomSubject,
    announcements,
    canManageAnnouncements,
    shifts,
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
    const classroomLabel = [
        room.course?.code,
        `${room.term_number}º período`,
        shifts[room.shift] ?? room.shift,
    ]
        .filter(Boolean)
        .join(' · ');

    setLayoutProps({
        breadcrumbs: canViewClassrooms
            ? [
                  { title: 'Dashboard', href: dashboard() },
                  { title: 'Turmas', href: classroomIndex() },
                  { title: room.name, href: classroom.show(room.id) },
                  { title: subject.code, href: subjectHref },
              ]
            : [
                  { title: 'Dashboard', href: dashboard() },
                  { title: 'Minhas matérias', href: enrolledSubject() },
                  { title: subject.code, href: subjectHref },
              ],
    });

    return (
        <>
            <Head title={`${subject.name} - ${room.name}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-6 md:px-8">
                <header className="flex flex-col gap-4">
                    <h1 className="text-3xl font-bold tracking-tight text-foreground">
                        {subject.name}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2">
                        <Chip className="font-mono">{subject.code}</Chip>
                        <Chip>{subject.workload_hours}h</Chip>
                        <Chip>{classroomLabel}</Chip>
                        {room.academic_term && (
                            <Chip>{room.academic_term.name}</Chip>
                        )}
                        <span className="ml-1 text-sm text-muted-foreground">
                            Professor(a){' '}
                            <span className="font-semibold text-foreground">
                                {teacher.person.name}
                            </span>
                        </span>
                    </div>
                </header>

                <Tabs variant="secondary" disabledKeys={['activities']}>
                    <Tabs.ListContainer>
                        <Tabs.List
                            aria-label="Seções da matéria"
                            className="min-w-0 gap-2"
                        >
                            <Tabs.Tab
                                id="announcements"
                                className="h-11 w-auto gap-2 px-3"
                            >
                                Avisos
                                <TabCount>{announcements.length}</TabCount>
                                <Tabs.Indicator />
                            </Tabs.Tab>
                            {students && (
                                <Tabs.Tab
                                    id="students"
                                    className="h-11 w-auto gap-2 px-3"
                                >
                                    Alunos
                                    <TabCount>{students.length}</TabCount>
                                    <Tabs.Indicator />
                                </Tabs.Tab>
                            )}
                            <Tabs.Tab
                                id="activities"
                                className="h-11 w-auto gap-2 px-3"
                            >
                                Atividades
                                <TabCount>Em breve</TabCount>
                                <Tabs.Indicator />
                            </Tabs.Tab>
                        </Tabs.List>
                    </Tabs.ListContainer>

                    <Tabs.Panel
                        id="announcements"
                        className="flex flex-col gap-4 pt-6"
                    >
                        <PanelHeader
                            title="Avisos"
                            description="Comunicados para todos os alunos da turma."
                            action={
                                canManageAnnouncements && (
                                    <Button onPress={createState.open}>
                                        <Plus className="size-4" />
                                        Novo aviso
                                    </Button>
                                )
                            }
                        />
                        <AnnouncementList
                            classroomId={room.id}
                            classroomSubjectId={classroomSubject.id}
                            announcements={announcements}
                            onEdit={canManageAnnouncements ? edit : undefined}
                        />
                    </Tabs.Panel>

                    {students && statuses && (
                        <Tabs.Panel
                            id="students"
                            className="flex flex-col gap-4 pt-6"
                        >
                            <PanelHeader
                                title="Alunos"
                                description="Alunos matriculados na turma."
                            />
                            <ClassroomSubjectStudentsTable
                                students={students}
                                statuses={statuses}
                            />
                        </Tabs.Panel>
                    )}
                </Tabs>
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
