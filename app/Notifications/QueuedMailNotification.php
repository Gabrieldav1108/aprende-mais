<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Throwable;

/**
 * Base for every e-mail of the application: it is sent by the queue worker on the "mail" queue,
 * only after the surrounding transaction commits, retrying with a growing delay.
 */
abstract class QueuedMailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the notification may be attempted.
     */
    public int $tries = 5;

    /**
     * The number of seconds the notification can run before timing out.
     */
    public int $timeout = 30;

    public function __construct()
    {
        $this->afterCommit();
    }

    /**
     * Build the e-mail; it runs on the queue worker, not on the request.
     */
    abstract public function toMail(object $notifiable): MailMessage;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Determine which queue each channel is sent on.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return ['mail' => 'mail'];
    }

    /**
     * The number of seconds to wait before retrying the notification.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 3600];
    }

    /**
     * Report the e-mail that could not be sent after every attempt.
     */
    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
