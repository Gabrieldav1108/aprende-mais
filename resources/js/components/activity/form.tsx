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
import { useState } from 'react';
import type { Activity } from '@/components/activity/list';
import AttachmentList from '@/components/attachment-list';
import AttachmentUpload from '@/components/attachment-upload';
import InputError from '@/components/input-error';
import { toDateTimeLocal } from '@/lib/format';
import classroom from '@/routes/classroom';

/**
 * The most supporting files an activity can have, mirroring Activity::MAX_ATTACHMENTS.
 */
const MAX_ATTACHMENTS = 10;

type Props = {
    classroomId: number;
    classroomSubjectId: number;
    activity?: Activity;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the activity modal; the form closes the modal once saved. The due date is typed in local time
 * and sent in UTC, and the files are uploaded to the bucket before the form is sent with their ids.
 */
export default function ActivityForm({
    classroomId,
    classroomSubjectId,
    activity: existing,
    onSuccess,
    onCancel,
}: Props) {
    const activities = classroom.subjects.activities;
    const [dueAt, setDueAt] = useState(toDateTimeLocal(existing?.due_at));
    const [kept, setKept] = useState(existing?.attachments ?? []);
    const [uploadedIds, setUploadedIds] = useState<number[]>([]);
    const [hasPendingUploads, setHasPendingUploads] = useState(false);

    const attachmentIds = [...kept.map(({ id }) => id), ...uploadedIds];

    return (
        <Form
            action={
                existing
                    ? activities.update([
                          classroomId,
                          classroomSubjectId,
                          existing.id,
                      ])
                    : activities.store([classroomId, classroomSubjectId])
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
                                placeholder="Lista de exercícios 1"
                                defaultValue={existing?.title ?? ''}
                                required
                            />
                            <InputError message={errors.title} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="description">Descrição</Label>
                            <TextArea
                                variant="secondary"
                                fullWidth
                                id="description"
                                name="description"
                                rows={6}
                                maxLength={10000}
                                defaultValue={existing?.description ?? ''}
                                required
                            />
                            <InputError message={errors.description} />
                        </div>

                        <div className="grid min-w-0 gap-2 sm:max-w-xs">
                            <Label htmlFor="due_at_local">
                                Prazo de entrega (opcional)
                            </Label>
                            <Input
                                variant="secondary"
                                fullWidth
                                id="due_at_local"
                                type="datetime-local"
                                value={dueAt}
                                onChange={(event) =>
                                    setDueAt(event.target.value)
                                }
                            />
                            <input
                                type="hidden"
                                name="due_at"
                                value={
                                    dueAt ? new Date(dueAt).toISOString() : ''
                                }
                            />
                            <InputError message={errors.due_at} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label>Material complementar</Label>
                            <AttachmentList
                                files={kept}
                                onRemove={(file) =>
                                    setKept((current) =>
                                        current.filter(
                                            ({ id }) => id !== file.id,
                                        ),
                                    )
                                }
                            />
                            <AttachmentUpload
                                onChange={setUploadedIds}
                                onPendingChange={setHasPendingUploads}
                                maxFiles={MAX_ATTACHMENTS - kept.length}
                                isDisabled={processing}
                            />
                            {attachmentIds.map((id) => (
                                <input
                                    key={id}
                                    type="hidden"
                                    name="attachment_ids[]"
                                    value={id}
                                />
                            ))}
                            <InputError
                                message={
                                    errors.attachment_ids ??
                                    Object.entries(errors).find(([field]) =>
                                        field.startsWith('attachment_ids.'),
                                    )?.[1]
                                }
                            />
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
                        <Button
                            type="submit"
                            isDisabled={processing || hasPendingUploads}
                        >
                            {processing && <Spinner />}
                            {existing
                                ? 'Salvar alterações'
                                : 'Publicar atividade'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
