import { Button, Chip, EmptyState, Table } from '@heroui/react';
import { Link } from '@inertiajs/react';
import { Mail, Pencil } from 'lucide-react';
import type { AddressValue } from '@/components/address-fields';
import { formatCpfCnpj } from '@/lib/format';
import user from '@/routes/user';

export type Person = {
    id: number;
    name: string;
    cpf_cnpj: string | null;
    birth_date: string | null;
    user: {
        id: number;
        email: string;
        email_verified_at: string | null;
        roles: { id: number; name: string }[];
    };
    address?: AddressValue | null;
    phones?: { id: number; number: string }[];
    emails?: { id: number; address: string }[];
};

type Props = {
    people: Person[];
    roles: Record<string, string>;
    onEdit?: (person: Person) => void;
};

export default function UserTable({ people, roles, onEdit }: Props) {
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
                                <Table.Cell>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {item.user.email}
                                        {!item.user.email_verified_at && (
                                            <Chip
                                                size="sm"
                                                color="warning"
                                                variant="soft"
                                            >
                                                Convite pendente
                                            </Chip>
                                        )}
                                    </div>
                                </Table.Cell>
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
                                                      roles[role.name] ??
                                                      role.name,
                                              )
                                              .join(', ')
                                        : '—'}
                                </Table.Cell>
                                {onEdit ? (
                                    <Table.Cell>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                aria-label={`Editar ${item.name}`}
                                                onPress={() => onEdit(item)}
                                            >
                                                <Pencil className="size-4" />
                                                Editar
                                            </Button>
                                            {!item.user.email_verified_at && (
                                                <Link
                                                    href={user.invitation(
                                                        item.id,
                                                    )}
                                                    method="post"
                                                    as="button"
                                                    preserveScroll
                                                    aria-label={`Reenviar convite para ${item.name}`}
                                                    className="inline-flex items-center gap-1 text-sm text-foreground hover:underline"
                                                >
                                                    <Mail className="size-4" />
                                                    Reenviar convite
                                                </Link>
                                            )}
                                        </div>
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
