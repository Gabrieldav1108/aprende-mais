import { Button, ProgressBar } from '@heroui/react';
import { Paperclip, RotateCw, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import {
    ATTACHMENT_MIME_TYPES,
    attachmentRejection,
    useAttachmentUpload,
} from '@/hooks/use-attachment-upload';
import type { UploadedAttachment } from '@/hooks/use-attachment-upload';
import { formatFileSize } from '@/lib/format';

type Props = {
    onChange: (attachmentIds: number[]) => void;
    onPendingChange?: (hasPending: boolean) => void;
    maxFiles?: number;
    isDisabled?: boolean;
};

type QueuedFile = {
    key: string;
    file: File;
};

/**
 * Pick files and upload each one straight to the bucket, reporting the ids of the confirmed uploads
 * so the parent form can send them as attachment_ids[], and whether some picked file is still uploading
 * or failed, so the form can wait for it.
 */
export default function AttachmentUpload({
    onChange,
    onPendingChange,
    maxFiles = 5,
    isDisabled = false,
}: Props) {
    const input = useRef<HTMLInputElement>(null);
    const [queue, setQueue] = useState<QueuedFile[]>([]);
    const [uploaded, setUploaded] = useState<Record<string, number>>({});
    const [rejections, setRejections] = useState<string[]>([]);

    useEffect(() => {
        onChange(
            queue
                .map(({ key }) => uploaded[key])
                .filter((id) => id !== undefined),
        );
    }, [queue, uploaded, onChange]);

    useEffect(() => {
        onPendingChange?.(queue.some(({ key }) => uploaded[key] === undefined));
    }, [queue, uploaded, onPendingChange]);

    const pick = (files: FileList | null) => {
        const picked = Array.from(files ?? []);
        const room = maxFiles - queue.length;
        const messages: string[] = [];
        const accepted: QueuedFile[] = [];

        picked.forEach((file, index) => {
            const rejection = attachmentRejection(file);

            if (rejection) {
                messages.push(`${file.name}: ${rejection}`);
            } else if (accepted.length >= room) {
                messages.push(
                    `${file.name}: envie no máximo ${maxFiles} arquivos.`,
                );
            } else {
                accepted.push({
                    key: `${Date.now()}-${index}-${file.name}`,
                    file,
                });
            }
        });

        setRejections(messages);
        setQueue((current) => [...current, ...accepted]);

        if (input.current) {
            input.current.value = '';
        }
    };

    const remove = (key: string) => {
        setQueue((current) => current.filter((item) => item.key !== key));
        setUploaded((current) =>
            Object.fromEntries(
                Object.entries(current).filter(([itemKey]) => itemKey !== key),
            ),
        );
    };

    return (
        <div className="grid gap-3">
            <input
                ref={input}
                type="file"
                multiple
                hidden
                accept={ATTACHMENT_MIME_TYPES.join(',')}
                onChange={(event) => pick(event.target.files)}
            />

            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="secondary"
                    isDisabled={isDisabled || queue.length >= maxFiles}
                    onPress={() => input.current?.click()}
                >
                    <Paperclip />
                    Anexar arquivos
                </Button>
                <span className="text-sm text-muted-foreground">
                    PDF, Office, TXT, ZIP ou imagens, até 20 MB cada.
                </span>
            </div>

            {rejections.map((message) => (
                <InputError key={message} message={message} />
            ))}

            {queue.length > 0 && (
                <ul className="grid gap-2">
                    {queue.map(({ key, file }) => (
                        <AttachmentUploadItem
                            key={key}
                            file={file}
                            onUploaded={(attachment) =>
                                setUploaded((current) => ({
                                    ...current,
                                    [key]: attachment.id,
                                }))
                            }
                            onRemove={() => remove(key)}
                        />
                    ))}
                </ul>
            )}
        </div>
    );
}

type ItemProps = {
    file: File;
    onUploaded: (attachment: UploadedAttachment) => void;
    onRemove: () => void;
};

function AttachmentUploadItem({ file, onUploaded, onRemove }: ItemProps) {
    const { status, progress, error, attachment, retry } =
        useAttachmentUpload(file);
    const reported = useRef(false);

    useEffect(() => {
        if (attachment && !reported.current) {
            reported.current = true;
            onUploaded(attachment);
        }
    }, [attachment, onUploaded]);

    return (
        <li className="grid gap-2 rounded-lg border px-3 py-2">
            <div className="flex items-center gap-2">
                <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">{file.name}</p>
                    <p className="text-xs text-muted-foreground">
                        {formatFileSize(file.size)}
                        {status === 'uploading' && ` · enviando ${progress}%`}
                        {status === 'done' && ' · enviado'}
                    </p>
                </div>

                {status === 'error' && (
                    <Button
                        isIconOnly
                        size="sm"
                        variant="ghost"
                        aria-label={`Tentar enviar ${file.name} de novo`}
                        onPress={retry}
                    >
                        <RotateCw />
                    </Button>
                )}
                <Button
                    isIconOnly
                    size="sm"
                    variant="ghost"
                    aria-label={`Remover ${file.name}`}
                    onPress={onRemove}
                >
                    <X />
                </Button>
            </div>

            {status === 'uploading' && (
                <ProgressBar
                    aria-label={`Enviando ${file.name}`}
                    value={progress}
                    size="sm"
                >
                    <ProgressBar.Track>
                        <ProgressBar.Fill />
                    </ProgressBar.Track>
                </ProgressBar>
            )}

            <InputError message={error ?? undefined} />
        </li>
    );
}
