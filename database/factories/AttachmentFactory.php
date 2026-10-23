<?php

namespace Database\Factories;

use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state: a PDF waiting for its upload to be confirmed.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uploader_id' => User::factory(),
            'disk' => Attachment::DISK,
            'path' => 'attachments/'.now()->format('Y/m').'/'.Str::lower((string) Str::ulid()).'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(1_000, 500_000),
            'status' => AttachmentStatus::Pending,
        ];
    }

    /**
     * The upload has not been confirmed yet.
     */
    public function pending(): static
    {
        return $this->state(fn () => ['status' => AttachmentStatus::Pending, 'uploaded_at' => null]);
    }

    /**
     * The file is confirmed in the bucket.
     */
    public function uploaded(): static
    {
        return $this->state(fn () => ['status' => AttachmentStatus::Uploaded, 'uploaded_at' => now()]);
    }
}
