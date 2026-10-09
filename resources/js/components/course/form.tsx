import {
    Button,
    ListBox,
    Input,
    Label,
    Modal,
    Select,
    Spinner,
} from '@heroui/react';
import type { Key } from '@heroui/react';
import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { Course } from '@/components/course/table';
import FormSelect, { optionsFromMap } from '@/components/form-select';
import InputError from '@/components/input-error';
import course from '@/routes/course';

export type SubjectOption = {
    id: number;
    name: string;
    code: string;
};

type Props = {
    course?: Course;
    subjectOptions: SubjectOption[];
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
    subjectOptions,
    levels,
    statuses,
    onSuccess,
    onCancel,
}: Props) {
    const isEditing = Boolean(existing);
    const [terms, setTerms] = useState<Record<string, string>>(() =>
        Object.fromEntries(
            (existing?.subject_terms ?? []).map((link) => [
                String(link.subject_id),
                String(link.term ?? 1),
            ]),
        ),
    );

    const selectSubjects = (keys: Key[]) =>
        setTerms((current) =>
            Object.fromEntries(
                keys.map((key) => [String(key), current[String(key)] ?? '']),
            ),
        );

    const setTerm = (subjectId: number, term: string) =>
        setTerms((current) => ({ ...current, [subjectId]: term }));

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
                        <div className="grid min-w-0 gap-3 sm:col-span-2">
                            <Select
                                aria-label="Matérias"
                                selectionMode="multiple"
                                variant="secondary"
                                fullWidth
                                placeholder="Selecione as matérias"
                                value={Object.keys(terms)}
                                onChange={(keys) =>
                                    selectSubjects(keys as Key[])
                                }
                            >
                                <Label>Matérias</Label>
                                <Select.Trigger>
                                    <Select.Value />
                                    <Select.Indicator />
                                </Select.Trigger>
                                <Select.Popover>
                                    <ListBox items={subjectOptions}>
                                        {(subject) => (
                                            <ListBox.Item
                                                id={String(subject.id)}
                                                textValue={`${subject.code} ${subject.name}`}
                                            >
                                                {subject.code} · {subject.name}
                                            </ListBox.Item>
                                        )}
                                    </ListBox>
                                </Select.Popover>
                            </Select>

                            {subjectOptions
                                .filter(
                                    (subject) => String(subject.id) in terms,
                                )
                                .map((subject) => {
                                    const index = Object.keys(terms).indexOf(
                                        String(subject.id),
                                    );

                                    return (
                                        <div
                                            key={subject.id}
                                            className="grid gap-1"
                                        >
                                            <div className="flex items-center gap-3">
                                                <span className="min-w-0 flex-1 truncate text-sm">
                                                    {subject.code} ·{' '}
                                                    {subject.name}
                                                </span>
                                                <input
                                                    type="hidden"
                                                    name={`subjects[${index}][id]`}
                                                    value={subject.id}
                                                />
                                                <Input
                                                    aria-label={`Semestre de ${subject.name}`}
                                                    variant="secondary"
                                                    className="w-28"
                                                    name={`subjects[${index}][term]`}
                                                    type="number"
                                                    min={1}
                                                    placeholder="Semestre"
                                                    value={terms[subject.id]}
                                                    onChange={(event) =>
                                                        setTerm(
                                                            subject.id,
                                                            event.target.value,
                                                        )
                                                    }
                                                    required
                                                />
                                            </div>
                                            <InputError
                                                message={
                                                    errors[
                                                        `subjects.${index}.term`
                                                    ]
                                                }
                                            />
                                        </div>
                                    );
                                })}
                            <InputError message={errors.subjects} />
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
                            {isEditing ? 'Salvar alterações' : 'Criar curso'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
