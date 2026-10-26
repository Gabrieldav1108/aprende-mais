<?php

namespace App\Concerns;

use App\Enums\AttachmentStatus;
use App\Models\Activity;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

trait ActivityValidationRules
{
    /**
     * Validation rules shared by activity creation and update. An attachment is accepted when the user uploaded it and it is
     * not linked to any record yet, or when it already belongs to the activity being edited.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function activityRules(?Activity $activity = null): array
    {
        $uploaderId = $this->user()->id;

        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:10000'],
            'due_at' => ['nullable', 'date'],
            'attachment_ids' => ['array', 'max:'.Activity::MAX_ATTACHMENTS],
            'attachment_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('attachments', 'id')->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->whereNull('attachable_type')
                        ->where('uploader_id', $uploaderId)
                        ->where('status', AttachmentStatus::Uploaded->value))
                    ->when($activity !== null, fn (Builder $query) => $query->orWhere(fn (Builder $query) => $query
                        ->where('attachable_type', $activity->getMorphClass())
                        ->where('attachable_id', $activity->id)))),
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
            'attachment_ids.max' => __('Anexe no máximo :max arquivos.'),
            'attachment_ids.*.exists' => __('Um dos arquivos anexados não é válido. Envie-o de novo.'),
        ];
    }
}
