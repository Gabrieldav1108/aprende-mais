import {
    Button,
    Checkbox,
    Input,
    Label,
    Modal,
    Spinner,
    TextArea,
} from '@heroui/react';
import { Form } from '@inertiajs/react';
import type { Announcement } from '@/components/announcement/list';
import InputError from '@/components/input-error';
import classroom from '@/routes/classroom';

type Props = {
    classroomId: number;
    classroomSubjectId: number;
    announcement?: Announcement;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the announcement modal; the form closes the modal once saved.
 */
export default function AnnouncementForm({
    classroomId,
    classroomSubjectId,
    announcement: existing,
    onSuccess,
    onCancel,
}: Props) {
    const announcements = classroom.subjects.announcements;

    return (
        <Form
            action={
                existing
                    ? announcements.update([
                          classroomId,
                          classroomSubjectId,
                          existing.id,
                      ])
                    : announcements.store([classroomId, classroomSubjectId])
            }
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4">
                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="title">Título</Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="title"
                                name="title"
                                maxLength={150}
                                placeholder="Prova remarcada"
                                defaultValue={existing?.title ?? ''}
                                required
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="body">Mensagem</Label>
                            <TextArea
                                variant="secondary"
                                fullWidth
                                id="body"
                                name="body"
                                rows={6}
                                maxLength={5000}
                                defaultValue={existing?.body ?? ''}
                                required
                            />
                            <InputError message={errors.body} />
                        </div>

                        {!existing && (
                            <Checkbox
                                name="send_email"
                                value="1"
                                defaultSelected
                            >
                                <Checkbox.Control>
                                    <Checkbox.Indicator />
                                </Checkbox.Control>
                                <Checkbox.Content>
                                    Enviar também por e-mail aos alunos
                                </Checkbox.Content>
                            </Checkbox>
                        )}
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
                            {existing ? 'Salvar alterações' : 'Publicar aviso'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
