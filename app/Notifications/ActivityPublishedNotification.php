<?php

namespace App\Notifications;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Tells a student that a new activity was posted to one of their classroom subjects,
 * always in the app and, when the teacher asked for it, by e-mail.
 */
class ActivityPublishedNotification extends QueuedMailNotification
{
    public function __construct(public Activity $activity)
    {
        parent::__construct();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->activity->sent_by_email ? ['database', 'mail'] : ['database'];
    }

    /**
     * Determine which queue each channel is sent on.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return ['database' => 'default', 'mail' => 'mail'];
    }

    /**
     * Skip the e-mail to a user who has not accepted the invitation yet; the in-app notification is still stored.
     */
    public function shouldSend(User $notifiable, string $channel): bool
    {
        return $channel !== 'mail' || $notifiable->hasVerifiedEmail();
    }

    /**
     * Build the e-mail, with the due date in the display timezone; it runs on the queue worker, not on the request.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $classroomSubject = $this->activity->classroomSubject;
        $dueAt = $this->activity->due_at;

        return (new MailMessage)
            ->subject(__('Nova atividade em :subject', ['subject' => $classroomSubject->subject->name]))
            ->greeting(__('Olá, :name!', ['name' => $notifiable->name]))
            ->line(__('Uma nova atividade foi publicada em :subject (:classroom):', [
                'subject' => $classroomSubject->subject->name,
                'classroom' => $classroomSubject->classroom->name,
            ]))
            ->line('**'.$this->activity->title.'**')
            ->line(Str::limit($this->activity->description, 300))
            ->when($dueAt !== null, fn (MailMessage $mail) => $mail->line(__('Prazo de entrega: :due', [
                'due' => $dueAt->clone()->setTimezone(config('app.display_timezone'))->format('d/m/Y H:i'),
            ])))
            ->action(__('Ver atividade'), $this->url())
            ->salutation(__('Atenciosamente, equipe :app', ['app' => config('app.name')]));
    }

    /**
     * Get the data stored for the in-app notification.
     *
     * @return array{activity_id: int, classroom_subject_id: int, kind: string, title: string, subject: string, classroom: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $classroomSubject = $this->activity->classroomSubject;

        return [
            'activity_id' => $this->activity->id,
            'classroom_subject_id' => $classroomSubject->id,
            'kind' => 'activity',
            'title' => $this->activity->title,
            'subject' => $classroomSubject->subject->name,
            'classroom' => $classroomSubject->classroom->name,
            'url' => $this->url(),
        ];
    }

    /**
     * The activities tab of the classroom subject page where the activity is shown.
     */
    private function url(): string
    {
        $classroomSubject = $this->activity->classroomSubject;

        return route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject->id, 'tab' => 'activities']);
    }
}
