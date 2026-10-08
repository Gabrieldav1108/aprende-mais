import { Button, Input, Label, Modal, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { AcademicTerm } from '@/components/academic-term/table';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import academicTerm from '@/routes/academic-term';

type Props = {
    term?: AcademicTerm;
    statuses: Record<string, string>;
    onSuccess: () => void;
    onCancel: () => void;
};

const semesterOptions = [
    { id: 1, label: '1º semestre' },
    { id: 2, label: '2º semestre' },
];

/**
 * Body and footer of the academic term modal; the form closes the modal once saved.
 */
export default function AcademicTermForm({
    term: existing,
    statuses,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);

    return (
        <Form
            action={
                existing
                    ? academicTerm.update(existing.id)
                    : academicTerm.store()
            }
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4 sm:grid-cols-2 sm:items-start">
                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="year">Ano</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="year"
                                name="year"
                                type="number"
                                min={2000}
                                max={2999}
                                placeholder="2026"
                                defaultValue={existing?.year}
                                required
                            />
                            <InputError message={errors.year} />
                        </div>

                        <FormSelect
                            name="number"
                            label="Semestre"
                            isRequired
                            variant="secondary"
                            fullWidth
                            options={semesterOptions}
                            defaultValue={existing?.number}
                            error={errors.number}
                        />

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="starts_at">Início</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="starts_at"
                                name="starts_at"
                                type="date"
                                defaultValue={existing?.starts_at.slice(0, 10)}
                                required
                            />
                            <InputError message={errors.starts_at} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="ends_at">Fim</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="ends_at"
                                name="ends_at"
                                type="date"
                                defaultValue={existing?.ends_at.slice(0, 10)}
                                required
                            />
                            <InputError message={errors.ends_at} />
                        </div>

                        <div className="grid min-w-0 sm:col-span-2">
                            <FormSelect
                                name="status"
                                label="Status"
                                isRequired
                                variant="secondary"
                                fullWidth
                                options={optionsFromMap(statuses)}
                                defaultValue={existing?.status ?? 'planned'}
                                error={errors.status}
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
                            {isEditing ? 'Salvar alterações' : 'Criar período'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
