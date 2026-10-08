import { Button, EmptyState, Table } from '@heroui/react';
import { Pencil } from 'lucide-react';

export type Teacher = {
    id: number;
    registration_number: string;
    qualification: string | null;
    person: {
        id: number;
        name: string;
        user: { id: number; email: string };
    };
};

type Props = {
    teachers: Teacher[];
    onEdit?: (teacher: Teacher) => void;
};

export default function TeacherTable({ teachers, onEdit }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Professores">
                    <Table.Header>
                        <Table.Column isRowHeader>Nome</Table.Column>
                        <Table.Column>Registro</Table.Column>
                        <Table.Column>E-mail</Table.Column>
                        <Table.Column>Titulação</Table.Column>
                        {onEdit ? <Table.Column>Ações</Table.Column> : null}
                    </Table.Header>
                    <Table.Body
                        items={teachers}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhum professor encontrado
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
                                    {item.registration_number}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.person.user.email}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.qualification ?? '—'}
                                </Table.Cell>
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
