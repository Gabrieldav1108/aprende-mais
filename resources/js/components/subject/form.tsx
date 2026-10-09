import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Teacher } from '@/components/classroom/table';
import FormSelect from '@/components/form-select';
import InputError from '@/components/input-error';
import type { Subject } from '@/components/subject/table';
import subject from '@/routes/subject';

export type SubjectOptions = {
    teachers: Teacher[];
    knowledgeAreas: { id: number; name: string }[];
};

type Props = SubjectOptions & {
    subject?: Subject;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the subject modal; the form closes the modal once saved.
 */
export default function SubjectForm({
    subject: existing,
    teachers,
    knowledgeAreas,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);

    return (
        <Form
            action={existing ? subject.update(existing.id) : subject.store()}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-2 sm:items-start">
                        <div className="grid min-w-0 gap-2 sm:col-span-2">
                            <Label htmlFor="subject_name">Nome</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="subject_name"
                                name="name"
                                placeholder="Cálculo I"
                                defaultValue={existing?.name ?? ''}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="subject_code">Código</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="subject_code"
                                name="code"
                                placeholder="MAT-101"
                                defaultValue={existing?.code ?? ''}
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="workload_hours">
                                Carga horária
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="workload_hours"
                                name="workload_hours"
                                type="number"
                                min={1}
                                placeholder="80"
                                defaultValue={existing?.workload_hours ?? ''}
                                required
                            />
                            <InputError message={errors.workload_hours} />
                        </div>

                        <FormSelect
                            name="teacher_id"
                            label="Professor"
                            isRequired
                            variant="secondary"
                            fullWidth
                            defaultValue={existing?.teacher_id}
                            options={teachers.map((teacher) => ({
                                id: teacher.id,
                                label: teacher.person.name,
                            }))}
                            error={errors.teacher_id}
                        />

                        <FormSelect
                            name="knowledge_area_id"
                            label="Área do conhecimento"
                            variant="secondary"
                            fullWidth
                            defaultValue={existing?.knowledge_area_id}
                            options={knowledgeAreas.map((area) => ({
                                id: area.id,
                                label: area.name,
                            }))}
                            error={errors.knowledge_area_id}
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
                            {isEditing
                                ? 'Salvar alterações'
                                : 'Cadastrar matéria'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
