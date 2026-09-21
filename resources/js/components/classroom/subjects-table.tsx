import { Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import classroom from '@/routes/classroom';

export type Teacher = {
    id: number;
    user: { name: string };
};

export type ClassroomSubject = {
    id: number;
    subject: {
        id: number;
        name: string;
        code: string;
        workload_hours: string;
    };
    teacher: Teacher;
};

type Props = {
    classroomId: number;
    classroomSubjects: ClassroomSubject[];
    canRemove: boolean;
};

export default function ClassroomSubjectsTable({
    classroomId,
    classroomSubjects,
    canRemove,
}: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Matérias da turma">
                    <Table.Header>
                        <Table.Column isRowHeader>Matéria</Table.Column>
                        <Table.Column>Código</Table.Column>
                        <Table.Column>Carga horária</Table.Column>
                        <Table.Column>Professor</Table.Column>
                        <Table.Column>{null}</Table.Column>
                    </Table.Header>
                    <Table.Body
                        items={classroomSubjects}
                        renderEmptyState={() =>
                            'Nenhuma matéria adicionada ainda.'
                        }
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>{item.subject.name}</Table.Cell>
                                <Table.Cell>{item.subject.code}</Table.Cell>
                                <Table.Cell>
                                    {item.subject.workload_hours}h
                                </Table.Cell>
                                <Table.Cell>
                                    {item.teacher.user.name}
                                </Table.Cell>
                                <Table.Cell>
                                    {canRemove && (
                                        <Link
                                            href={classroom.subjects.destroy([
                                                classroomId,
                                                item.id,
                                            ])}
                                            method="delete"
                                            as="button"
                                            className="text-sm text-destructive hover:underline"
                                        >
                                            Remover
                                        </Link>
                                    )}
                                </Table.Cell>
                            </Table.Row>
                        )}
                    </Table.Body>
                </Table.Content>
            </Table.ScrollContainer>
        </Table>
    );
}
