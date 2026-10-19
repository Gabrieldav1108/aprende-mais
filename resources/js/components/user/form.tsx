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
import { useRef, useState } from 'react';
import AddressFields from '@/components/address-fields';
import type { AddressValue } from '@/components/address-fields';
import CpfCnpjField from '@/components/cpf-cnpj-field';
import InputError from '@/components/input-error';
import MultiInput from '@/components/multi-input';
import type { Person } from '@/components/user/table';
import type { CnpjCompany } from '@/hooks/use-cnpj-lookup';
import { formatPhone } from '@/lib/format';
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
    const nameRef = useRef<HTMLInputElement>(null);
    // Remounting the address fields with the company's address fills them.
    const [address, setAddress] = useState<AddressValue | null | undefined>(
        person?.address,
    );
    const [addressVersion, setAddressVersion] = useState(0);
    // Same for the contacts: the lists are remounted with the company's phones and e-mail added.
    const [phones, setPhones] = useState(
        person?.phones?.map((phone) => formatPhone(phone.number)) ?? [],
    );
    const [emails, setEmails] = useState(
        person?.emails?.map((email) => email.address) ?? [],
    );
    const [contactsVersion, setContactsVersion] = useState(0);

    /**
     * Values currently typed in a repeatable field, read from the form itself.
     */
    const typedValues = (field: string): string[] =>
        Array.from(
            nameRef.current?.form?.querySelectorAll<HTMLInputElement>(
                `input[name="${field}[]"]`,
            ) ?? [],
        )
            .map((input) => input.value.trim())
            .filter((value) => value !== '');

    /**
     * Append the found values to the typed ones, skipping those already present.
     */
    const merge = (
        typed: string[],
        found: string[],
        normalize: (value: string) => string,
    ): string[] => {
        const known = new Set(typed.map(normalize));

        return [
            ...typed,
            ...found.filter((value) => !known.has(normalize(value))),
        ];
    };

    const fillFromCompany = (company: CnpjCompany) => {
        if (nameRef.current && nameRef.current.value.trim() === '') {
            nameRef.current.value = company.legal_name;
        }

        setAddress(company.address);
        setAddressVersion((version) => version + 1);

        setPhones(
            merge(
                typedValues('phones'),
                company.phones.map((phone) => formatPhone(phone)),
                (phone) => phone.replace(/\D/g, ''),
            ),
        );
        setEmails(
            merge(
                typedValues('emails'),
                company.email ? [company.email] : [],
                (email) => email.toLowerCase(),
            ),
        );
        setContactsVersion((version) => version + 1);
    };

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
                                ref={nameRef}
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
                            {!isEditing && (
                                <span className="text-xs text-muted-foreground">
                                    Um convite para definir a senha será enviado
                                    para este e-mail.
                                </span>
                            )}
                        </div>

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

                        <CpfCnpjField
                            defaultValue={person?.cpf_cnpj}
                            error={errors.cpf_cnpj}
                            onCompanyFound={fillFromCompany}
                        />

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

                        <AddressFields
                            key={addressVersion}
                            address={address}
                            errors={errors}
                        />

                        <div className="grid min-w-0 gap-4 sm:col-span-2">
                            <MultiInput
                                key={`phones-${contactsVersion}`}
                                name="phones"
                                label="Telefones"
                                variant="secondary"
                                addLabel="Adicionar telefone"
                                placeholder="(11) 99999-0000"
                                initialValues={phones}
                                errors={errors}
                            />
                        </div>

                        <div className="grid min-w-0 gap-4 sm:col-span-2">
                            <MultiInput
                                key={`emails-${contactsVersion}`}
                                name="emails"
                                label="E-mails de contato"
                                variant="secondary"
                                addLabel="Adicionar e-mail"
                                type="email"
                                placeholder="contato@exemplo.com"
                                initialValues={emails}
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
