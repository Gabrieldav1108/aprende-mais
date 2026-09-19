import { Button, Input, Label, ListBox, Select, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Teacher } from '@/components/classroom/subjects-table';
import InputError from '@/components/input-error';
import classroom from '@/routes/classroom';

type Props = {
    classroomId: number;
    teachers: Teacher[];
};

export default function ClassroomSubjectForm({ classroomId, teachers }: Props) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">Adicionar matéria</h2>
            <Form
                action={classroom.subjects.store(classroomId)}
                resetOnSuccess
                className="grid gap-4 sm:grid-cols-5 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="name">Nome</Label>
                            <Input
                                id="name"
                                name="name"
                                placeholder="Matemática"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code">Código</Label>
                            <Input
                                id="code"
                                name="code"
                                placeholder="MAT-101"
                                required
                            />
                            <InputError message={errors.code} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="workload_hours">
                                Carga horária
                            </Label>
                            <Input
                                id="workload_hours"
                                name="workload_hours"
                                type="number"
                                min={1}
                                placeholder="80"
                                required
                            />
                            <InputError message={errors.workload_hours} />
                        </div>

                        <div className="grid gap-2">
                            <Select
                                name="teacher_id"
                                isRequired
                                placeholder="Selecione"
                            >
                                <Label>Professor</Label>
                                <Select.Trigger>
                                    <Select.Value />
                                    <Select.Indicator />
                                </Select.Trigger>
                                <Select.Popover>
                                    <ListBox items={teachers}>
                                        {(teacher) => (
                                            <ListBox.Item
                                                id={teacher.id}
                                                textValue={teacher.user.name}
                                            >
                                                {teacher.user.name}
                                            </ListBox.Item>
                                        )}
                                    </ListBox>
                                </Select.Popover>
                            </Select>
                            <InputError message={errors.teacher_id} />
                        </div>

                        <Button
                            type="submit"
                            className="sm:col-span-5 sm:w-fit"
                        >
                            {processing && <Spinner />}
                            Adicionar matéria
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
