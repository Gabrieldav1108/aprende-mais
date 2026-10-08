import { EmptyState, Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import type { Teacher } from '@/components/classroom/table';
import classroom from '@/routes/classroom';

export type ClassroomSubject = {
    id: number;
    subject: {
        id: number;
        name: string;
        code: string;
        workload_hours: number;
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
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhuma matéria ofertada nesta turma
                                </span>
                            </EmptyState>
                        )}
                    >
                        {(item) => (
                            <Table.Row>
                                <Table.Cell>{item.subject.name}</Table.Cell>
                                <Table.Cell>{item.subject.code}</Table.Cell>
                                <Table.Cell>
                                    {item.subject.workload_hours}h
                                </Table.Cell>
                                <Table.Cell>
                                    {item.teacher.person.name}
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
