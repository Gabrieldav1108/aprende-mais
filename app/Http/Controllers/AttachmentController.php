<?php

namespace App\Http\Controllers;

use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreAttachmentUploadRequest;
use App\Models\Attachment;
use App\Repositories\AttachmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderUtils;

class AttachmentController extends Controller
{
    /**
     * How long the browser has to start the upload, in minutes.
     */
    private const int UPLOAD_URL_TTL = 10;

    /**
     * How long a download link stays valid, in minutes.
     */
    private const int DOWNLOAD_URL_TTL = 5;

    public function __construct(private readonly AttachmentRepository $attachments) {}

    /**
     * Register the file and return a presigned URL the browser PUTs it to, straight to the bucket.
     */
    public function store(StoreAttachmentUploadRequest $request): JsonResponse
    {
        try {
            $attachment = $this->attachments->createPending($request->user(), $request->validated());
        } catch (RepositoryException $e) {
            report($e);

            return response()->json(['message' => __('Não foi possível iniciar o envio do arquivo.')], 500);
        }

        ['url' => $url] = Storage::disk($attachment->disk)->temporaryUploadUrl(
            $attachment->path,
            now()->plus(minutes: self::UPLOAD_URL_TTL),
            ['ContentType' => $attachment->mime_type],
        );

        return response()->json([
            'attachment' => ['id' => $attachment->id],
            'upload' => ['url' => $url, 'headers' => ['Content-Type' => $attachment->mime_type]],
        ], 201);
    }

    /**
     * Confirm the browser finished the upload. The presigned URL does not limit the size, so the size the bucket reports is checked here.
     */
    public function complete(Attachment $attachment): JsonResponse
    {
        $this->authorize('complete', $attachment);

        $disk = Storage::disk($attachment->disk);

        if (! $disk->exists($attachment->path)) {
            return response()->json(['message' => __('O arquivo ainda não chegou ao armazenamento.')], 422);
        }

        $size = $disk->size($attachment->path);

        try {
            if ($size > Attachment::MAX_SIZE || $size !== $attachment->size) {
                $this->attachments->discard($attachment);

                return response()->json(['message' => __('O arquivo enviado não corresponde ao informado.')], 422);
            }

            $this->attachments->markUploaded($attachment, $size);
        } catch (RepositoryException $e) {
            report($e);

            return response()->json(['message' => __('Não foi possível confirmar o envio do arquivo.')], 500);
        }

        return response()->json([
            'attachment' => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
            ],
        ]);
    }

    /**
     * Send the user to a short-lived presigned URL that downloads the file with its original name.
     */
    public function download(Attachment $attachment): RedirectResponse
    {
        $this->authorize('download', $attachment);

        $url = Storage::disk($attachment->disk)->temporaryUrl(
            $attachment->path,
            now()->plus(minutes: self::DOWNLOAD_URL_TTL),
            ['ResponseContentDisposition' => $this->attachmentDisposition($attachment->original_name)],
        );

        return redirect()->away($url);
    }

    /**
     * Build the Content-Disposition that saves the file under the given name, with an ASCII fallback for older clients.
     */
    private function attachmentDisposition(string $name): string
    {
        $name = str_replace(['/', '\\'], '-', $name);

        return HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $name,
            str_replace('%', '', Str::ascii($name)),
        );
    }
}
