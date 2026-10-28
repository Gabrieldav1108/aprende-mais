import { Accordion, Chip, EmptyState } from '@heroui/react';
import { Head, setLayoutProps } from '@inertiajs/react';
import { CalendarClock } from 'lucide-react';
import { useState } from 'react';
import type { Activity } from '@/components/activity/list';
import AttachmentList from '@/components/attachment-list';
import type { ClassroomSubject } from '@/components/classroom/subjects-table';
import type { Classroom } from '@/components/classroom/table';
import SubmissionStatusChip, {
    isLateSubmission,
} from '@/components/submission/status-chip';
import type { Submission } from '@/components/submission/status-chip';
import { useCan } from '@/hooks/use-can';
import { formatShortDateTime } from '@/lib/format';
import { classroom as classroomIndex, dashboard } from '@/routes';
import classroom from '@/routes/classroom';

type SubmittingStudent = {
    id: number;
    enrollment_number: string | null;
    person: { id: number; name: string };
    submissions: Submission[];
};

type Props = {
    classroomSubject: ClassroomSubject & { classroom: Classroom };
    activity: Activity;
    students: SubmittingStudent[];
};

/**
 * One version the student sent, with its text and files.
 */
function SubmissionVersion({
    submission,
    number,
    dueAt,
}: {
    submission: Submission;
    number: number;
    dueAt: string | null;
}) {
    return (
        <li className="flex flex-col gap-2 py-3 first:pt-0 last:pb-0">
            <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                <span className="font-medium text-foreground">
                    Versão {number}
                </span>
                · {formatShortDateTime(submission.created_at)}
                {isLateSubmission(submission, dueAt) && (
                    <Chip size="sm" variant="soft" color="warning">
                        Com atraso
                    </Chip>
                )}
            </div>
            {submission.content && (
                <p className="text-sm whitespace-pre-line text-foreground/80">
                    {submission.content}
                </p>
            )}
            <AttachmentList files={submission.attachments} />
        </li>
    );
}

/**
 * What every student sent to the activity: the current answer of each one and the earlier versions below it.
 */
export default function ActivitySubmissions({
    classroomSubject,
    activity,
    students,
}: Props) {
    const { classroom: room, subject } = classroomSubject;
    const canViewClassrooms = useCan('classrooms.view');
    const [now] = useState(() => Date.now());
    const submittedCount = students.filter(
        ({ submissions }) => submissions.length > 0,
    ).length;

    const subjectHref = classroom.subjects.show(
        [room.id, classroomSubject.id],
        {
            query: { tab: 'activities' },
        },
    );
    const submissionsHref = classroom.subjects.activities.submissions.index([
        room.id,
        classroomSubject.id,
        activity.id,
    ]);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            ...(canViewClassrooms
                ? [
                      { title: 'Turmas', href: classroomIndex() },
                      { title: room.name, href: classroom.show(room.id) },
                  ]
                : []),
            { title: subject.code, href: subjectHref },
            { title: 'Entregas', href: submissionsHref },
        ],
    });

    return (
        <>
            <Head title={`Entregas - ${activity.title}`} />
            <div className="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-6 px-4 py-6 md:px-8">
                <header className="flex flex-col gap-3">
                    <p className="text-sm text-muted-foreground">
                        {subject.name} · {room.name}
                    </p>
                    <h1 className="text-3xl font-bold tracking-tight text-foreground">
                        {activity.title}
                    </h1>
                    <div className="flex flex-wrap items-center gap-2">
                        {activity.due_at && (
                            <Chip>
                                <CalendarClock className="size-3.5" />
                                Prazo {formatShortDateTime(activity.due_at)}
                            </Chip>
                        )}
                        <span className="text-sm text-muted-foreground">
                            <span className="font-semibold text-foreground tabular-nums">
                                {submittedCount} de {students.length}
                            </span>{' '}
                            alunos entregaram
                        </span>
                    </div>
                </header>

                {students.length === 0 ? (
                    <EmptyState className="flex w-full flex-col items-center justify-center gap-4 rounded-xl border border-dashed py-10 text-center">
                        <span className="text-sm text-muted-foreground">
                            Nenhum aluno matriculado nesta turma
                        </span>
                    </EmptyState>
                ) : (
                    <Accordion variant="surface" allowsMultipleExpanded>
                        {students.map((student) => (
                            <Accordion.Item key={student.id} id={student.id}>
                                <Accordion.Heading>
                                    <Accordion.Trigger className="gap-3">
                                        <span className="flex min-w-0 flex-1 flex-wrap items-center gap-x-3 gap-y-1 text-left">
                                            <span className="font-medium text-foreground">
                                                {student.person.name}
                                            </span>
                                            {student.enrollment_number && (
                                                <span className="text-xs text-muted-foreground">
                                                    {student.enrollment_number}
                                                </span>
                                            )}
                                        </span>
                                        {student.submissions.length > 1 && (
                                            <span className="text-xs text-muted-foreground">
                                                {student.submissions.length}{' '}
                                                versões
                                            </span>
                                        )}
                                        <SubmissionStatusChip
                                            submission={student.submissions[0]}
                                            dueAt={activity.due_at}
                                            now={now}
                                        />
                                        <Accordion.Indicator />
                                    </Accordion.Trigger>
                                </Accordion.Heading>
                                <Accordion.Panel>
                                    <Accordion.Body>
                                        {student.submissions.length === 0 ? (
                                            <p className="text-sm text-muted-foreground">
                                                Nenhuma entrega até agora.
                                            </p>
                                        ) : (
                                            <ul className="divide-y divide-border">
                                                {student.submissions.map(
                                                    (submission, index) => (
                                                        <SubmissionVersion
                                                            key={submission.id}
                                                            submission={
                                                                submission
                                                            }
                                                            number={
                                                                student
                                                                    .submissions
                                                                    .length -
                                                                index
                                                            }
                                                            dueAt={
                                                                activity.due_at
                                                            }
                                                        />
                                                    ),
                                                )}
                                            </ul>
                                        )}
                                    </Accordion.Body>
                                </Accordion.Panel>
                            </Accordion.Item>
                        ))}
                    </Accordion>
                )}
            </div>
        </>
    );
}
