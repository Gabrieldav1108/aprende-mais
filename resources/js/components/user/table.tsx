import { Button, EmptyState, Table } from '@heroui/react';
import { Pencil } from 'lucide-react';
import { formatCpfCnpj } from '@/lib/format';

export type PersonAddress = {
    zip_code: string | null;
    street: string | null;
    neighborhood: string | null;
    city: string | null;
    state: string | null;
    number: string | null;
    complement: string | null;
};

export type Person = {
    id: number;
    name: string;
    cpf_cnpj: string | null;
    birth_date: string | null;
    user: {
        id: number;
        email: string;
        roles: { id: number; name: string }[];
    };
    address?: PersonAddress | null;
    phones?: { id: number; number: string }[];
    emails?: { id: number; address: string }[];
};

const roleLabels: Record<string, string> = {
    admin: 'Administrador',
    teacher: 'Professor',
    student: 'Aluno',
};

type Props = {
    people: Person[];
    onEdit?: (person: Person) => void;
};

export default function UserTable({ people, onEdit }: Props) {
    return (
        <Table>
            <Table.ScrollContainer>
                <Table.Content aria-label="Usuários">
                    <Table.Header>
                        <Table.Column isRowHeader>Nome</Table.Column>
                        <Table.Column>E-mail de acesso</Table.Column>
                        <Table.Column>CPF / CNPJ</Table.Column>
                        <Table.Column>Papéis</Table.Column>
                        {onEdit ? <Table.Column>Ações</Table.Column> : null}
                    </Table.Header>
                    <Table.Body
                        items={people}
                        renderEmptyState={() => (
                            <EmptyState className="flex h-full w-full flex-col items-center justify-center gap-4 py-8 text-center">
                                <span className="text-sm text-muted-foreground">
                                    Nenhum usuário encontrado
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
                                <Table.Cell>{item.user.email}</Table.Cell>
                                <Table.Cell>
                                    {item.cpf_cnpj
                                        ? formatCpfCnpj(item.cpf_cnpj)
                                        : '—'}
                                </Table.Cell>
                                <Table.Cell>
                                    {item.user.roles.length > 0
                                        ? item.user.roles
                                              .map(
                                                  (role) =>
                                                      roleLabels[role.name] ??
                                                      role.name,
                                              )
                                              .join(', ')
                                        : '—'}
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
