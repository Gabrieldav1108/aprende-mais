import { useHttp } from '@inertiajs/react';
import { useCallback, useEffect, useRef, useState } from 'react';
import attachments from '@/routes/attachments';

/**
 * The accepted MIME types, mirroring Attachment::MIME_TYPES on the server.
 */
export const ATTACHMENT_MIME_TYPES = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'application/zip',
    'image/png',
    'image/jpeg',
    'image/webp',
];

/**
 * The largest file accepted, in bytes, mirroring Attachment::MAX_SIZE (20 MB).
 */
export const ATTACHMENT_MAX_SIZE = 20 * 1024 * 1024;

export type UploadedAttachment = {
    id: number;
    name: string;
    mime_type: string;
    size: number;
};

export type AttachmentUploadStatus = 'uploading' | 'done' | 'error';

type UploadForm = {
    name: string;
    mime_type: string;
    size: number;
};

type PresignResponse = {
    attachment: { id: number };
    upload: { url: string; headers: Record<string, string> };
};

type CompleteResponse = {
    attachment: UploadedAttachment;
};

export type UseAttachmentUploadReturn = {
    status: AttachmentUploadStatus;
    progress: number;
    error: string | null;
    attachment: UploadedAttachment | null;
    retry: () => void;
};

/**
 * Why the file can't be uploaded, checked before asking the server for an upload URL.
 */
export function attachmentRejection(file: File): string | null {
    if (!ATTACHMENT_MIME_TYPES.includes(file.type)) {
        return 'Tipo de arquivo não permitido.';
    }

    if (file.size === 0) {
        return 'O arquivo está vazio.';
    }

    if (file.size > ATTACHMENT_MAX_SIZE) {
        return 'O arquivo deve ter no máximo 20 MB.';
    }

    return null;
}

/**
 * The message of a failed JSON request, falling back to a generic one.
 */
function failureMessage(exception: unknown, fallback: string): string {
    const data = (exception as { response?: { data?: unknown } }).response
        ?.data;

    if (data && typeof data === 'object' && 'message' in data) {
        return String(data.message);
    }

    if (typeof data === 'string') {
        try {
            const parsed = JSON.parse(data) as { message?: string };

            return parsed.message ?? fallback;
        } catch {
            return fallback;
        }
    }

    return fallback;
}

/**
 * PUT the file straight to the bucket through the presigned URL, reporting the upload progress (0-100).
 * Uses XHR instead of fetch because fetch can't report upload progress.
 */
function putToBucket(
    url: string,
    headers: Record<string, string>,
    file: File,
    onProgress: (percentage: number) => void,
): Promise<void> {
    return new Promise((resolve, reject) => {
        const request = new XMLHttpRequest();

        request.open('PUT', url);
        Object.entries(headers).forEach(([name, value]) =>
            request.setRequestHeader(name, value),
        );

        request.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        };
        request.onload = () =>
            request.status >= 200 && request.status < 300
                ? resolve()
                : reject(
                      new Error(`Upload failed with status ${request.status}`),
                  );
        request.onerror = () => reject(new Error('Upload failed'));

        request.send(file);
    });
}

/**
 * Upload one file straight to the bucket: ask the server for a presigned URL, PUT the file to it, then confirm the upload.
 * The upload starts as soon as the hook mounts and runs once per file.
 */
export function useAttachmentUpload(file: File): UseAttachmentUploadReturn {
    const presign = useHttp<UploadForm, PresignResponse>({
        name: file.name,
        mime_type: file.type,
        size: file.size,
    });
    const confirm = useHttp<Record<string, never>, CompleteResponse>();
    const [status, setStatus] = useState<AttachmentUploadStatus>('uploading');
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState<string | null>(null);
    const [attachment, setAttachment] = useState<UploadedAttachment | null>(
        null,
    );
    const started = useRef(false);

    const start = useCallback(async () => {
        setStatus('uploading');
        setProgress(0);
        setError(null);

        let pending: PresignResponse;

        try {
            pending = await presign.submit(attachments.store());
        } catch (exception) {
            setError(
                failureMessage(
                    exception,
                    'Não foi possível iniciar o envio do arquivo.',
                ),
            );
            setStatus('error');

            return;
        }

        try {
            await putToBucket(
                pending.upload.url,
                pending.upload.headers,
                file,
                setProgress,
            );
        } catch {
            setError('Não foi possível enviar o arquivo.');
            setStatus('error');

            return;
        }

        try {
            const confirmed = await confirm.submit(
                attachments.complete(pending.attachment.id),
            );

            setAttachment(confirmed.attachment);
            setStatus('done');
        } catch (exception) {
            setError(
                failureMessage(
                    exception,
                    'Não foi possível confirmar o envio do arquivo.',
                ),
            );
            setStatus('error');
        }
    }, [presign, confirm, file]);

    useEffect(() => {
        if (started.current) {
            return;
        }

        started.current = true;
        void start();
    }, [start]);

    return { status, progress, error, attachment, retry: start };
}
