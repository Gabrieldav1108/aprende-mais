import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Course } from '@/components/course/table';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import course from '@/routes/course';

type Props = {
    course?: Course;
    levels: Record<string, string>;
    statuses: Record<string, string>;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the course modal; the form closes the modal once saved.
 */
export default function CourseForm({
    course: existing,
    levels,
    statuses,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);

    return (
        <Form
            action={existing ? course.update(existing.id) : course.store()}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-2 sm:items-start">
                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="name"
                                name="name"
                                placeholder="Engenharia de Software"
                                defaultValue={existing?.name}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="code">Código</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="code"
                                name="code"
                                placeholder="ENG-SW"
                                defaultValue={existing?.code}
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="duration_terms">
                                Duração (semestres)
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="duration_terms"
                                name="duration_terms"
                                type="number"
                                min={1}
                                max={20}
                                placeholder="8"
                                defaultValue={existing?.duration_terms}
                                required
                            />
                            <InputError message={errors.duration_terms} />
                        </div>

                        <FormSelect
                            name="level"
                            label="Nível"
                            isRequired
                            variant="secondary"
                            fullWidth
                            options={optionsFromMap(levels)}
                            defaultValue={existing?.level}
                            error={errors.level}
                        />

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
                            {isEditing ? 'Salvar alterações' : 'Criar curso'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
