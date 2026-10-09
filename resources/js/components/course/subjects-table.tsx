import { EmptyState, Table } from '@heroui/react';

export type CatalogSubject = {
    id: number;
    name: string;
    code: string;
    workload_hours: number;
    suggested_term_number: number | null;
    teacher: { person: { name: string } } | null;
};

type Props = {
    subjects: CatalogSubject[];
};

export default function CourseSubjectsTable({ subjects }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Matérias do curso">
                    <Table.Header>
                        <Table.Column isRowHeader>Matéria</Table.Column>
                        <Table.Column>Código</Table.Column>
                        <Table.Column>Professor</Table.Column>
                        <Table.Column>Carga horária</Table.Column>
                        <Table.Column>Semestre sugerido</Table.Column>
                    </Table.Header>
                    <Table.Body
                        items={subjects}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhuma matéria cadastrada neste curso
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>{item.name}</Table.Cell>
                                <Table.Cell>{item.code}</Table.Cell>
                                <Table.Cell>
                                    {item.teacher?.person.name ?? '—'}
                                </Table.Cell>
                                <Table.Cell>{item.workload_hours}h</Table.Cell>
                                <Table.Cell>
                                    {item.suggested_term_number
                                        ? `${item.suggested_term_number}º`
                                        : '—'}
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table.Content>
            </Table.ScrollContainer>
        </Table>
    );
}
