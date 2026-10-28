import { Button, Label, Modal, Spinner, TextArea } from '@heroui/react';
import { Form } from '@inertiajs/react';
import { useState } from 'react';
import type { Activity } from '@/components/activity/list';
import AttachmentList from '@/components/attachment-list';
import AttachmentUpload from '@/components/attachment-upload';
import InputError from '@/components/input-error';
import classroom from '@/routes/classroom';

/**
 * The most files a submission can have, mirroring Submission::MAX_ATTACHMENTS.
 */
const MAX_ATTACHMENTS = 10;

type Props = {
    classroomId: number;
    classroomSubjectId: number;
    activity: Activity;
    onSuccess: () => void;
    onCancel: () => void;
};

/**
 * Body and footer of the submission modal. It starts from the student's current answer: the files kept from it are
 * copied into the new version, which the server stores beside the earlier ones.
 */
export default function SubmissionForm({
    classroomId,
    classroomSubjectId,
    activity,
    onSuccess,
    onCancel,
}: Props) {
    const current = activity.submissions?.[0];
    const [kept, setKept] = useState(current?.attachments ?? []);
    const [uploadedIds, setUploadedIds] = useState<number[]>([]);
    const [hasPendingUploads, setHasPendingUploads] = useState(false);

    const attachmentIds = [...kept.map(({ id }) => id), ...uploadedIds];

    return (
        <Form
            action={classroom.subjects.activities.submissions.store([
                classroomId,
                classroomSubjectId,
                activity.id,
            ])}
            onSuccess={onSuccess}
            className="flex flex-col"
        >
            {({ processing, errors }) => (
                <>
                    <Modal.Body className="grid gap-4">
                        <div className="grid min-w-0 gap-2">
                            <Label htmlFor="content">Resposta</Label>
                            <TextArea
                                variant="secondary"
                                fullWidth
                                id="content"
                                name="content"
                                rows={6}
                                maxLength={10000}
                                placeholder="Escreva sua resposta ou anexe os arquivos abaixo."
                                defaultValue={current?.content ?? ''}
                            />
                            <InputError message={errors.content} />
                        </div>

                        <div className="grid min-w-0 gap-2">
                            <Label>Arquivos</Label>
                            <AttachmentList
                                files={kept}
                                onRemove={(file) =>
                                    setKept((files) =>
                                        files.filter(
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

                        {activity.due_at && (
                            <p className="text-sm text-muted-foreground">
                                Você pode alterar a entrega até o prazo; depois
                                dele, ela não muda mais.
                            </p>
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
                            {current ? 'Enviar nova versão' : 'Entregar'}
                        </Button>
                    </Modal.Footer>
                </>
            )}
        </Form>
    );
}
