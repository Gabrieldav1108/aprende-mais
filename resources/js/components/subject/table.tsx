import { Button, EmptyState, Table } from '@heroui/react';
import { Pencil } from 'lucide-react';

export type Subject = {
    id: number;
    name: string;
    code: string;
    workload_hours: number;
    teacher_id: number | null;
    knowledge_area_id: number | null;
    teacher: { person: { name: string } } | null;
    knowledge_area: { name: string } | null;
};

type Props = {
    subjects: Subject[];
    onEdit?: (subject: Subject) => void;
};

export default function SubjectTable({ subjects, onEdit }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Matérias">
                    <Table.Header>
                        <Table.Column isRowHeader>Matéria</Table.Column>
                        <Table.Column>Código</Table.Column>
                        <Table.Column>Professor</Table.Column>
                        <Table.Column>Área</Table.Column>
                        <Table.Column>Carga horária</Table.Column>
                        {onEdit ? <Table.Column>Ações</Table.Column> : null}
                    </Table.Header>
                    <Table.Body
                        items={subjects}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhuma matéria cadastrada
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>
                                    <span className="font-medium">
                                        {item.name}
                                    </span>
                                </Table.Cell>
                                <Table.Cell>{item.code}</Table.Cell>
                                <Table.Cell>
                                    {item.teacher?.person.name ?? '—'}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.knowledge_area?.name ?? '—'}
                                </Table.Cell>
                                <Table.Cell>{item.workload_hours}h</Table.Cell>
                                {onEdit ? (
                                    <Table.Cell>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            aria-label={`Editar ${item.name}`}
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
