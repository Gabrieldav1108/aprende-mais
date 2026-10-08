import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form, Link } from '@inertiajs/react';
import FormSelect from '@/components/form-select';
import InputError from '@/components/input-error';
import type { Teacher } from '@/components/teacher/table';
import { user as userIndex } from '@/routes';
import teacher from '@/routes/teacher';

type Props = {
    teacher?: Teacher;
    people: { id: number; name: string }[];
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the teacher modal; the form closes the modal once saved.
 */
export default function TeacherForm({
    teacher: existing,
    people,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);
    const hasNoPeople = !isEditing && people.length === 0;

    return (
        <Form
            action={existing ? teacher.update(existing.id) : teacher.store()}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-2 sm:items-start">
                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            {existing ? (
                                <>
                                    <Label>Pessoa</Label>
                                    <p className="text-sm text-foreground">
                                        {existing.person.name}
                                        <span className="ml-2 text-muted-foreground">
                                            {existing.person.user.email}
                                        </span>
                                    </p>
                                </>
                            ) : hasNoPeople ? (
                                <p className="text-sm text-muted-foreground">
                                    Nenhuma pessoa com o papel de professor
                                    disponível. Cadastre-a, ou marque o papel,
                                    em{' '}
                                    <Link
                                        href={userIndex()}
                                        className="font-medium text-foreground underline"
                                    >
                                        Usuários
                                    </Link>
                                    .
                                </p>
                            ) : (
                                <FormSelect
                                    name="person_id"
                                    label="Pessoa"
                                    isRequired
                                    variant="secondary"
                                    fullWidth
                                    options={people.map((person) => ({
                                        id: person.id,
                                        label: person.name,
                                    }))}
                                    error={errors.person_id}
                                />
                            )}
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="registration_number">
                                Registro
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="registration_number"
                                name="registration_number"
                                placeholder="T00001"
                                defaultValue={
                                    existing?.registration_number ?? ''
                                }
                                required
                            />
                            <InputError message={errors.registration_number} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="qualification">Titulação</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="qualification"
                                name="qualification"
                                placeholder="Mestrado em Matemática"
                                defaultValue={existing?.qualification ?? ''}
                            />
                            <InputError message={errors.qualification} />
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
                        <Button
                            type="submit"
                            isDisabled={processing || hasNoPeople}
                        >
                            {processing && <Spinner />}
                            {isEditing
                                ? 'Salvar alterações'
                                : 'Cadastrar professor'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
