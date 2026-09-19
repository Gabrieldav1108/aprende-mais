import { Button, Input, Label, ListBox, Select, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import { shiftLabels } from '@/components/classroom/table';
import type { Classroom } from '@/components/classroom/table';
import InputError from '@/components/input-error';
import classroom from '@/routes/classroom';

const shiftOptions = Object.entries(shiftLabels).map(([id, label]) => ({
    id,
    label,
}));

type Props = {
    classroom?: Pick<Classroom, 'id' | 'name' | 'school_year' | 'shift'>;
};

export default function ClassroomForm({ classroom: existing }: Props) {
    const isEditing = Boolean(existing);

    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">
                {isEditing ? 'Editar turma' : 'Nova turma'}
            </h2>
            <Form
                action={
                    existing ? classroom.update(existing.id) : classroom.store()
                }
                resetOnSuccess={!isEditing}
                className="grid gap-4 sm:grid-cols-4 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Turma A"
                                defaultValue={existing?.name}
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="school_year">Ano letivo</Label>
                            <Input
                                id="school_year"
                                name="school_year"
                                placeholder="2026"
                                defaultValue={existing?.school_year}
                                required
                            />
                            <InputError message={errors.school_year} />
                        </div>

                        <div className="grid gap-2">
                            <Select
                                name="shift"
                                isRequired
                                placeholder="Selecione"
                                defaultSelectedKey={existing?.shift}
                            >
                                <Label>Turno</Label>
                                <Select.Trigger>
                                    <Select.Value />
                                    <Select.Indicator />
                                </Select.Trigger>
                                <Select.Popover>
                                    <ListBox items={shiftOptions}>
                                        {(item) => (
                                            <ListBox.Item
                                                id={item.id}
                                                textValue={item.label}
                                            >
                                                {item.label}
                                            </ListBox.Item>
                                        )}
                                    </ListBox>
                                </Select.Popover>
                            </Select>
                            <InputError message={errors.shift} />
                        </div>

                        <Button
                            type="submit"
                            className="sm:col-span-4 sm:w-fit"
                        >
                            {processing && <Spinner />}
                            {isEditing ? 'Salvar alterações' : 'Criar turma'}
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
