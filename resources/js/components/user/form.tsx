import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import MultiInput from '@/components/multi-input';
import type { Person } from '@/components/user/table';
import { formatCpfCnpj, formatPhone, formatZipCode } from '@/lib/format';
import user from '@/routes/user';

type Props = {
    person?: Person;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the user modal; the form closes the modal once saved.
 */
export default function UserForm({
    person: existing,
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

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="address_zip_code">CEP</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_zip_code"
                                name="address[zip_code]"
                                placeholder="00000-000"
                                defaultValue={formatZipCode(address?.zip_code)}
                            />
                            <InputError message={errors['address.zip_code']} />
                        </div>

                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            <Label htmlFor="address_street">Rua</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_street"
                                name="address[street]"
                                defaultValue={address?.street ?? ''}
                            />
                            <InputError message={errors['address.street']} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="address_number">Número</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_number"
                                name="address[number]"
                                defaultValue={address?.number ?? ''}
                            />
                            <InputError message={errors['address.number']} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="address_complement">
                                Complemento
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_complement"
                                name="address[complement]"
                                defaultValue={address?.complement ?? ''}
                            />
                            <InputError
                                message={errors['address.complement']}
                            />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="address_neighborhood">Bairro</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_neighborhood"
                                name="address[neighborhood]"
                                defaultValue={address?.neighborhood ?? ''}
                            />
                            <InputError
                                message={errors['address.neighborhood']}
                            />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="address_city">Cidade</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_city"
                                name="address[city]"
                                defaultValue={address?.city ?? ''}
                            />
                            <InputError message={errors['address.city']} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="address_state">UF</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="address_state"
                                name="address[state]"
                                maxLength={2}
                                placeholder="SP"
                                defaultValue={address?.state ?? ''}
                            />
                            <InputError message={errors['address.state']} />
                        </div>

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
