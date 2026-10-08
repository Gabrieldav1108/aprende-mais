import { Button, EmptyState, Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import classroom from '@/routes/classroom';

export type Teacher = {
    id: number;
    person: { name: string };
};

export type Classroom = {
    id: number;
    name: string;
    code: string;
    term_number: number;
    shift: string;
    status: string;
    capacity: number;
    room: string | null;
    course_id: number;
    academic_term_id: number;
    homeroom_teacher_id: number | null;
    course?: { id: number; name: string };
    academic_term?: { id: number; name: string };
    homeroom_teacher?: Teacher | null;
    classroom_subjects_count?: number;
    classroom_students_count?: number;
};

type Props = {
    classrooms: Classroom[];
    shifts: Record<string, string>;
    statuses: Record<string, string>;
    onEdit?: (classroom: Classroom) => void;
};

export default function ClassroomTable({
    classrooms,
    shifts,
    statuses,
    onEdit,
}: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Turmas">
                    <Table.Header>
                        <Table.Column isRowHeader>Nome</Table.Column>
                        <Table.Column>Curso</Table.Column>
                        <Table.Column>Período</Table.Column>
                        <Table.Column>Turno</Table.Column>
                        <Table.Column>Status</Table.Column>
                        <Table.Column>Ocupação</Table.Column>
                        <Table.Column>Matérias</Table.Column>
                        {onEdit ? <Table.Column>Ações</Table.Column> : null}
                    </Table.Header>
                    <Table.Body
                        items={classrooms}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhuma turma encontrada
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>
                                    <Link
                                        href={classroom.show(item.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {item.name}
                                    </Link>
                                    <span className="ml-2 text-xs text-muted-foreground">
                                        {item.code}
                                    </span>
                                </Table.Cell>
                                <Table.Cell>{item.course?.name}</Table.Cell>
                                <Table.Cell>
                                    {item.academic_term?.name} ·{' '}
                                    {item.term_number}º
                                </Table.Cell>
                                <Table.Cell>{shifts[item.shift]}</Table.Cell>
                                <Table.Cell>{statuses[item.status]}</Table.Cell>
                                <Table.Cell>
                                    {item.classroom_students_count ?? 0}/
                                    {item.capacity}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.classroom_subjects_count ?? 0}
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
