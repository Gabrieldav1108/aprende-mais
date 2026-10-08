import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form, Link } from '@inertiajs/react';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import type { Student } from '@/components/student/table';
import { user as userIndex } from '@/routes';
import student from '@/routes/student';

type Props = {
    student?: Student;
    people: { id: number; name: string }[];
    courses: { id: number; name: string }[];
    statuses: Record<string, string>;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the student modal; the form closes the modal once saved.
 */
export default function StudentForm({
    student: existing,
    people,
    courses,
    statuses,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);
    const hasNoPeople = !isEditing && people.length === 0;

    return (
        <Form
            action={existing ? student.update(existing.id) : student.store()}
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
                                    Nenhuma pessoa com o papel de aluno
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
                            <Label htmlFor="enrollment_number">Matrícula</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="enrollment_number"
                                name="enrollment_number"
                                placeholder="2026001"
                                defaultValue={existing?.enrollment_number ?? ''}
                                required
                            />
                            <InputError message={errors.enrollment_number} />
                        </div>

                        <FormSelect
                            name="status"
                            label="Status"
                            isRequired
                            variant="secondary"
                            fullWidth
                            options={optionsFromMap(statuses)}
                            defaultValue={existing?.status ?? 'active'}
                            error={errors.status}
                        />

                        <div className="grid min-w-0 sm:col-span-2">
                            <FormSelect
                                name="course_id"
                                label="Curso"
                                variant="secondary"
                                fullWidth
                                options={courses.map((item) => ({
                                    id: item.id,
                                    label: item.name,
                                }))}
                                defaultValue={existing?.course_id}
                                error={errors.course_id}
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
                        <Button
                            type="submit"
                            isDisabled={processing || hasNoPeople}
                        >
                            {processing && <Spinner />}
                            {isEditing
                                ? 'Salvar alterações'
                                : 'Cadastrar aluno'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
