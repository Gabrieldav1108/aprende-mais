import { Button, Dropdown, EmptyState, Table } from '@heroui/react';
import { Link, router } from '@inertiajs/react';
import { ChevronDown, Pencil } from 'lucide-react';
import classroom from '@/routes/classroom';

type SubjectOffering = {
    id: number;
    classroom_id: number;
    classroom: { id: number; name: string };
};

export type Subject = {
    id: number;
    name: string;
    code: string;
    workload_hours: number;
    teacher_id: number | null;
    knowledge_area_id: number | null;
    teacher: { person: { name: string } } | null;
    knowledge_area: { name: string } | null;
    classroom_subjects: SubjectOffering[];
};

type Props = {
    subjects: Subject[];
    onEdit?: (subject: Subject) => void;
};

const offeringHref = (offering: SubjectOffering) =>
    classroom.subjects.show([offering.classroom_id, offering.id]);

function SubjectName({ subject }: { subject: Subject }) {
    const offerings = subject.classroom_subjects;

    if (offerings.length === 0) {
        return <span className="font-medium">{subject.name}</span>;
    }

    if (offerings.length === 1) {
        return (
            <Link
                href={offeringHref(offerings[0])}
                className="font-medium hover:underline"
            >
                {subject.name}
            </Link>
        );
    }

    return (
        <Dropdown>
            <Button
                variant="ghost"
                size="sm"
                className="-ml-3 font-medium"
                aria-label={`Escolher a turma de ${subject.name}`}
            >
                {subject.name}
                <ChevronDown className="size-4 text-muted-foreground" />
            </Button>
            <Dropdown.Popover placement="bottom start">
                <Dropdown.Menu
                    aria-label={`Turmas de ${subject.name}`}
                    onAction={(key) => {
                        const offering = offerings.find(
                            (item) => item.id === Number(key),
                        );

                        if (offering) {
                            router.visit(offeringHref(offering));
                        }
                    }}
                >
                    {offerings.map((offering) => (
                        <Dropdown.Item
                            key={offering.id}
                            id={offering.id}
                            textValue={offering.classroom.name}
                        >
                            {offering.classroom.name}
                        </Dropdown.Item>
                    ))}
                </Dropdown.Menu>
            </Dropdown.Popover>
        </Dropdown>
    );
}

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
                                    <SubjectName subject={item} />
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
