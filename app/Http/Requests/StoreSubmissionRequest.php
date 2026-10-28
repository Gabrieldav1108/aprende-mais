<?php

namespace App\Http\Requests;

use App\Enums\AttachmentStatus;
use App\Models\Submission;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request; only a student answers, even an admin cannot.
     */
    public function authorize(): Response
    {
        if ($this->user()->student === null) {
            return Response::deny();
        }

        return Gate::inspect('create', [Submission::class, $this->route('activity')]);
    }

    /**
     * Get the validation rules that apply to the request. An answer needs a text or a file. A file is accepted when the
     * user uploaded it and it is not linked to any record yet, or when it belongs to their current answer, to be kept
     * in the new version.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $uploaderId = $this->user()->id;
        $currentSubmission = $this->route('activity')->submissions()
            ->where('student_id', $this->user()->student->id)
            ->latest('id')
            ->first();

        return [
            'content' => ['nullable', 'string', 'max:10000', 'required_without:attachment_ids'],
            'attachment_ids' => ['array', 'max:'.Submission::MAX_ATTACHMENTS],
            'attachment_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('attachments', 'id')->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->whereNull('attachable_type')
                        ->where('uploader_id', $uploaderId)
                        ->where('status', AttachmentStatus::Uploaded->value))
                    ->when($currentSubmission !== null, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                        ->where('attachable_type', $currentSubmission->getMorphClass())
                        ->where('attachable_id', $currentSubmission->id)))),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required_without' => __('Escreva uma resposta ou anexe ao menos um arquivo.'),
            'attachment_ids.max' => __('Anexe no máximo :max arquivos.'),
            'attachment_ids.*.exists' => __('Um dos arquivos anexados não é válido. Envie-o de novo.'),
        ];
    }
}
