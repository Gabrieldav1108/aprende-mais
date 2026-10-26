import { Button } from '@heroui/react';
import { FileArchive, FileImage, FileText, X } from 'lucide-react';
import { formatFileSize } from '@/lib/format';
import attachments from '@/routes/attachments';

export type AttachmentFile = {
    id: number;
    original_name: string;
    mime_type: string;
    size: number;
};

type Props = {
    files: AttachmentFile[];
    onRemove?: (file: AttachmentFile) => void;
};

/**
 * The icon that hints the kind of file.
 */
function FileIcon({ mimeType }: { mimeType: string }) {
    const className = 'size-4 shrink-0 text-muted-foreground';

    if (mimeType.startsWith('image/')) {
        return <FileImage className={className} />;
    }

    if (mimeType === 'application/zip') {
        return <FileArchive className={className} />;
    }

    return <FileText className={className} />;
}

/**
 * Files already stored, each one downloading through the server, which redirects to a short-lived URL of the bucket;
 * with onRemove each file gets a button to take it out instead of the download link.
 */
export default function AttachmentList({ files, onRemove }: Props) {
    if (files.length === 0) {
        return null;
    }

    return (
        <ul className="flex flex-wrap gap-2">
            {files.map((file) => (
                <li
                    key={file.id}
                    className="flex max-w-full items-center gap-2 rounded-lg border px-3 py-1.5"
                >
                    <FileIcon mimeType={file.mime_type} />
                    {onRemove ? (
                        <span className="truncate text-sm text-foreground">
                            {file.original_name}
                        </span>
                    ) : (
                        <a
                            href={attachments.download.url(file.id)}
                            className="truncate text-sm text-foreground hover:underline"
                        >
                            {file.original_name}
                        </a>
                    )}
                    <span className="shrink-0 text-xs text-muted-foreground">
                        {formatFileSize(file.size)}
                    </span>
                    {onRemove && (
                        <Button
                            isIconOnly
                            size="sm"
                            variant="ghost"
                            aria-label={`Remover ${file.original_name}`}
                            onPress={() => onRemove(file)}
                        >
                            <X />
                        </Button>
                    )}
                </li>
            ))}
        </ul>
    );
}
