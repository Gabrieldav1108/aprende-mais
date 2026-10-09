import { Button, EmptyState, Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
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
    subject_terms?: { subject_id: number; term: number | null }[];
};

type Props = {
    courses: Course[];
    levels: Record<string, string>;
    statuses: Record<string, string>;
    onEdit?: (course: Course) => void;
};

export default function CourseTable({
    courses,
    levels,
    statuses,
    onEdit,
}: Props) {
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
                        {onEdit ? <Table.Column>Ações</Table.Column> : null}
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
