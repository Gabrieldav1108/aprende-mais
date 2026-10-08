import { Button, EmptyState, Table } from '@heroui/react';
import { Pencil } from 'lucide-react';

export type Student = {
    id: number;
    course_id: number | null;
    enrollment_number: string | null;
    status: string;
    person: {
        id: number;
        name: string;
        user: { id: number; email: string };
    };
    course: { id: number; name: string } | null;
};

type Props = {
    students: Student[];
    statuses: Record<string, string>;
    onEdit?: (student: Student) => void;
};

export default function StudentTable({ students, statuses, onEdit }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Alunos">
                    <Table.Header>
                        <Table.Column isRowHeader>Nome</Table.Column>
                        <Table.Column>Matrícula</Table.Column>
                        <Table.Column>E-mail</Table.Column>
                        <Table.Column>Curso</Table.Column>
                        <Table.Column>Status</Table.Column>
                        {onEdit ? <Table.Column>Ações</Table.Column> : null}
                    </Table.Header>
                    <Table.Body
                        items={students}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhum aluno encontrado
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>
                                    <span className="font-medium">
                                        {item.person.name}
                                    </span>
                                </Table.Cell>
                                <Table.Cell>
                                    {item.enrollment_number ?? '—'}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.person.user.email}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.course?.name ?? '—'}
                                </Table.Cell>
                                <Table.Cell>{statuses[item.status]}</Table.Cell>
                                {onEdit ? (
                                    <Table.Cell>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            aria-label={`Editar ${item.person.name}`}
                                            onPress={() => onEdit(item)}
                                        >
                                            <Pencil className="size-4" />
                                            Editar
                                        </Button>
                                    </Table.Cell>
                                ) : null}
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table.Content>
            </Table.ScrollContainer>
        </Table>
    );
}
