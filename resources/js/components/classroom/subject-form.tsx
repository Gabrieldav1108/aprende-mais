import { Button, Spinner } from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Teacher } from '@/components/classroom/table';
import FormSelect from '@/components/form-select';
import classroom from '@/routes/classroom';

export type CourseSubject = {
    id: number;
    name: string;
    code: string;
    workload_hours: number;
};

type Props = {
    classroomId: number;
    courseSubjects: CourseSubject[];
    teachers: Teacher[];
};

export default function ClassroomSubjectForm({
    classroomId,
    courseSubjects,
    teachers,
}: Props) {
    return (
        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <h2 className="mb-4 text-base font-medium">Ofertar matéria</h2>
            <Form
                action={classroom.subjects.store(classroomId)}
                resetOnSuccess
                className="grid gap-4 sm:grid-cols-3 sm:items-end"
            >
                {({ processing, errors }) => (
                    <>
                        <FormSelect
                            name="subject_id"
                            label="Matéria do curso"
                            isRequired
                            options={courseSubjects.map((subject) => ({
                                id: subject.id,
                                label: `${subject.code} · ${subject.name} (${subject.workload_hours}h)`,
                            }))}
                            error={errors.subject_id}
                        />

                        <FormSelect
                            name="teacher_id"
                            label="Professor"
                            isRequired
                            options={teachers.map((teacher) => ({
                                id: teacher.id,
                                label: teacher.person.name,
                            }))}
                            error={errors.teacher_id}
                        />

                        <Button type="submit" className="sm:w-fit">
                            {processing && <Spinner />}
                            Adicionar matéria
                        </Button>
                    </>
                )}
            </Form>
        </div>
    );
}
