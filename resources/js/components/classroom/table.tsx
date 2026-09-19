import { Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import classroom from '@/routes/classroom';

export type Classroom = {
    id: number;
    name: string;
    school_year: string;
    shift: 'morning' | 'afternoon' | 'evening';
    classroom_subjects_count: number;
};

export const shiftLabels: Record<Classroom['shift'], string> = {
    morning: 'Manhã',
    afternoon: 'Tarde',
    evening: 'Noite',
};

export default function ClassroomTable({
    classrooms,
}: {
    classrooms: Classroom[];
}) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Turmas">
                    <Table.Header>
                        <Table.Column isRowHeader>Nome</Table.Column>
                        <Table.Column>Ano letivo</Table.Column>
                        <Table.Column>Turno</Table.Column>
                        <Table.Column>Matérias</Table.Column>
                    </Table.Header>
                    <Table.Body
                        items={classrooms}
                        renderEmptyState={() =>
                            'Nenhuma turma cadastrada ainda.'
                        }
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
                                </Table.Cell>
                                <Table.Cell>{item.school_year}</Table.Cell>
                                <Table.Cell>
                                    {shiftLabels[item.shift]}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.classroom_subjects_count}
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table.Content>
            </Table.ScrollContainer>
        </Table>
    );
}
