import {
    Button,
    Checkbox,
    CheckboxGroup,
    Input,
    Label,
    Modal,
    Spinner,
} from '@heroui/react';
import { Form } from '@inertiajs/react';
import AddressFields from '@/components/address-fields';
import InputError from '@/components/input-error';
import MultiInput from '@/components/multi-input';
import type { Person } from '@/components/user/table';
import { formatCpfCnpj, formatPhone } from '@/lib/format';
import user from '@/routes/user';

type Props = {
    person?: Person;
    roles: Record<string, string>;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the user modal; the form closes the modal once saved.
 */
export default function UserForm({
    person: existing,
    roles,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);
    const person = existing;
    const address = person?.address;

    return (
        <Form
            action={existing ? user.update(existing.id) : user.store()}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-4 sm:items-start">
                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="name"
                                name="name"
                                placeholder="Ana Prado"
                                defaultValue={person?.name}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            <Label htmlFor="email">E-mail de acesso</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="email"
                                name="email"
                                type="email"
                                placeholder="ana@exemplo.com"
                                defaultValue={person?.user.email}
                                required
                            />
                            <InputError message={errors.email} />
                        </div>

                        {!isEditing && (
                            <>
                                <div className="grid min-w-0 gap-2 sm:col-span-2">
                                    <Label htmlFor="password">
                                        Senha inicial
                                    </Label>
                                    <Input
                                        variant="secondary"
                                        fullWidth
                                        id="password"
                                        name="password"
                                        type="password"
                                        autoComplete="new-password"
                                        required
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid min-w-0 gap-2 sm:col-span-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar senha
                                    </Label>
                                    <Input
                                        variant="secondary"
                                        fullWidth
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        type="password"
                                        autoComplete="new-password"
                                        required
                                    />
                                </div>
                            </>
                        )}

                        <div className="grid min-w-0 gap-2 sm:col-span-4">
                            <CheckboxGroup
                                name="roles[]"
                                variant="secondary"
                                defaultValue={
                                    person?.user.roles.map(
                                        (role) => role.name,
                                    ) ?? []
                                }
                                className="grid gap-2"
                            >
                                <Label>Papéis</Label>
                                <div className="flex flex-wrap gap-x-6 gap-y-2">
                                    {Object.entries(roles).map(
                                        ([value, label]) => (
                                            <Checkbox key={value} value={value}>
                                                <Checkbox.Control>
                                                    <Checkbox.Indicator />
                                                </Checkbox.Control>
                                                <Checkbox.Content>
                                                    <Label>{label}</Label>
                                                </Checkbox.Content>
                                            </Checkbox>
                                        ),
                                    )}
                                </div>
                            </CheckboxGroup>
                            <InputError message={errors.roles} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="cpf_cnpj">CPF / CNPJ</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="cpf_cnpj"
                                name="cpf_cnpj"
                                placeholder="000.000.000-00"
                                defaultValue={formatCpfCnpj(person?.cpf_cnpj)}
                            />
                            <InputError message={errors.cpf_cnpj} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="birth_date">Nascimento</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="birth_date"
                                name="birth_date"
                                type="date"
                                defaultValue={person?.birth_date ?? ''}
                            />
                            <InputError message={errors.birth_date} />
                        </div>

                        <h3 className="text-sm font-medium text-foreground sm:col-span-4">
                            Endereço
                        </h3>

                        <AddressFields address={address} errors={errors} />

                        <div className="grid min-w-0 gap-4 sm:col-span-2">
                            <MultiInput
                                name="phones"
                                label="Telefones"
                                variant="secondary"
                                addLabel="Adicionar telefone"
                                placeholder="(11) 99999-0000"
                                initialValues={person?.phones?.map((phone) =>
                                    formatPhone(phone.number),
                                )}
                                errors={errors}
                            />
                        </div>

                        <div className="grid min-w-0 gap-4 sm:col-span-2">
                            <MultiInput
                                name="emails"
                                label="E-mails de contato"
                                variant="secondary"
                                addLabel="Adicionar e-mail"
                                type="email"
                                placeholder="contato@exemplo.com"
                                initialValues={person?.emails?.map(
                                    (email) => email.address,
                                )}
                                errors={errors}
                            />
                        </div>
                    </Modal.Body>

                    <Modal.Footer>
                        <Button
                            type="button"
                            variant="secondary"
                            onPress={onCancel}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" isDisabled={processing}>
                            {processing && <Spinner />}
                            {isEditing
                                ? 'Salvar alterações'
                                : 'Cadastrar usuário'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
