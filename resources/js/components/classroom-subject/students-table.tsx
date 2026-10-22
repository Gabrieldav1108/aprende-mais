import { EmptyState, Table } from '@heroui/react';

export type ClassroomSubjectStudent = {
    id: number;
    status: string;
    student: {
        id: number;
        enrollment_number: string | null;
        person: { name: string };
    };
};

type Props = {
    students: ClassroomSubjectStudent[];
    statuses: Record<string, string>;
};

export default function ClassroomSubjectStudentsTable({
    students,
    statuses,
}: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Alunos da matéria">
                    <Table.Header>
                        <Table.Column isRowHeader>Aluno</Table.Column>
                        <Table.Column>Matrícula</Table.Column>
                        <Table.Column>Situação</Table.Column>
                    </Table.Header>
                    <Table.Body
                        items={students}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhum aluno matriculado nesta turma
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>
                                    {item.student.person.name}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.student.enrollment_number ?? '—'}
                                </Table.Cell>
                                <Table.Cell>
                                    {statuses[item.status] ?? item.status}
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table.Content>
            </Table.ScrollContainer>
        </Table>
    );
}
