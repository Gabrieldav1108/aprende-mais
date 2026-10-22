import { EmptyState, Table } from '@heroui/react';
import { Head, Link } from '@inertiajs/react';
import type { ClassroomSubject } from '@/components/classroom/subjects-table';
import type { Classroom } from '@/components/classroom/table';
import { dashboard, enrolledSubject } from '@/routes';
import classroom from '@/routes/classroom';

type Props = {
    classroomSubjects: (ClassroomSubject & {
        classroom_id: number;
        classroom: Classroom;
    })[];
};

/**
 * The subjects of the classrooms the student is enrolled in, each linking to its announcements.
 */
export default function EnrolledSubjectIndex({ classroomSubjects }: Props) {
    return (
        <>
            <Head title="Minhas matérias" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Table>
                    <Table.ScrollContainer>
                        <Table.Content aria-label="Minhas matérias">
                            <Table.Header>
                                <Table.Column isRowHeader>Matéria</Table.Column>
                                <Table.Column>Turma</Table.Column>
                                <Table.Column>Período letivo</Table.Column>
                                <Table.Column>Professor</Table.Column>
                            </Table.Header>
                            <Table.Body
                                items={classroomSubjects}
                                renderEmptyState={() => (
                                    <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                        <span className="text-sm text-muted-foreground">
                                            Você não está matriculado em nenhuma
                                            turma
                                        </span>
                                    </EmptyState>
                                )}
                            >
                                {(item) => (
                                    <Table.Row>
                                        <Table.Cell>
                                            <Link
                                                href={classroom.subjects.show([
                                                    item.classroom_id,
                                                    item.id,
                                                ])}
                                                className="font-medium hover:underline"
                                            >
                                                {item.subject.name}
                                            </Link>
                                        </Table.Cell>
                                        <Table.Cell>
                                            {item.classroom.name}
                                        </Table.Cell>
                                        <Table.Cell>
                                            {item.classroom.academic_term
                                                ?.name ?? '—'}
                                        </Table.Cell>
                                        <Table.Cell>
                                            {item.teacher.person.name}
                                        </Table.Cell>
                                    </Table.Row>
                                )}
                            </Table.Body>
                        </Table.Content>
                    </Table.ScrollContainer>
                </Table>
            </div>
        </>
    );
}

EnrolledSubjectIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Minhas matérias', href: enrolledSubject() },
    ],
};
