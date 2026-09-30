import { EmptyState, Table } from '@heroui/react';

export type AcademicTerm = {
    id: number;
    name: string;
    year: number;
    number: number;
    starts_at: string;
    ends_at: string;
    status: string;
    classrooms_count?: number;
};

type Props = {
    terms: AcademicTerm[];
    statuses: Record<string, string>;
};

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('pt-BR', { timeZone: 'UTC' });

export default function AcademicTermTable({ terms, statuses }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Períodos letivos">
                    <Table.Header>
                        <Table.Column isRowHeader>Período</Table.Column>
                        <Table.Column>Início</Table.Column>
                        <Table.Column>Fim</Table.Column>
                        <Table.Column>Status</Table.Column>
                        <Table.Column>Turmas</Table.Column>
                    </Table.Header>
                    <Table.Body
                        items={terms}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhum período encontrado
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>{item.name}</Table.Cell>
                                <Table.Cell>
                                    {formatDate(item.starts_at)}
                                </Table.Cell>
                                <Table.Cell>
                                    {formatDate(item.ends_at)}
                                </Table.Cell>
                                <Table.Cell>{statuses[item.status]}</Table.Cell>
                                <Table.Cell>
                                    {item.classrooms_count ?? 0}
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table.Content>
            </Table.ScrollContainer>
        </Table>
    );
}
