import { EmptyState, Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import course from '@/routes/course';

export type Course = {
    id: number;
    name: string;
    code: string;
    level: string;
    duration_terms: number;
    status: string;
    subjects_count?: number;
    classrooms_count?: number;
};

type Props = {
    courses: Course[];
    levels: Record<string, string>;
    statuses: Record<string, string>;
};

export default function CourseTable({ courses, levels, statuses }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Cursos">
                    <Table.Header>
                        <Table.Column isRowHeader>Nome</Table.Column>
                        <Table.Column>Nível</Table.Column>
                        <Table.Column>Duração</Table.Column>
                        <Table.Column>Status</Table.Column>
                        <Table.Column>Matérias</Table.Column>
                        <Table.Column>Turmas</Table.Column>
                    </Table.Header>
                    <Table.Body
                        items={courses}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhum curso encontrado
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>
                                    <Link
                                        href={course.show(item.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {item.name}
                                    </Link>
                                    <span className="ml-2 text-xs text-muted-foreground">
                                        {item.code}
                                    </span>
                                </Table.Cell>
                                <Table.Cell>{levels[item.level]}</Table.Cell>
                                <Table.Cell>
                                    {item.duration_terms} semestres
                                </Table.Cell>
                                <Table.Cell>{statuses[item.status]}</Table.Cell>
                                <Table.Cell>
                                    {item.subjects_count ?? 0}
                                </Table.Cell>
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
