<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Tells a student that a new announcement was posted to one of their classroom subjects,
 * always in the app and, when the teacher asked for it, by e-mail.
 */
class AnnouncementPublishedNotification extends QueuedMailNotification
{
    public function __construct(public Announcement $announcement)
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
        return $this->announcement->sent_by_email ? ['database', 'mail'] : ['database'];
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
     * Build the e-mail; it runs on the queue worker, not on the request.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $classroomSubject = $this->announcement->classroomSubject;

        return (new MailMessage)
            ->subject(__('Novo aviso em :subject', ['subject' => $classroomSubject->subject->name]))
            ->greeting(__('Olá, :name!', ['name' => $notifiable->name]))
            ->line(__('Um novo aviso foi publicado em :subject (:classroom):', [
                'subject' => $classroomSubject->subject->name,
                'classroom' => $classroomSubject->classroom->name,
            ]))
            ->line('**'.$this->announcement->title.'**')
            ->line(Str::limit($this->announcement->body, 300))
            ->action(__('Ver aviso'), $this->url())
            ->salutation(__('Atenciosamente, equipe :app', ['app' => config('app.name')]));
    }

    /**
     * Get the data stored for the in-app notification.
     *
     * @return array{announcement_id: int, classroom_subject_id: int, title: string, subject: string, classroom: string, url: string}
     */
    public function toArray(object $notifiable): array
    {
        $classroomSubject = $this->announcement->classroomSubject;

        return [
            'announcement_id' => $this->announcement->id,
            'classroom_subject_id' => $classroomSubject->id,
            'title' => $this->announcement->title,
            'subject' => $classroomSubject->subject->name,
            'classroom' => $classroomSubject->classroom->name,
            'url' => $this->url(),
        ];
    }

    /**
     * The classroom subject page where the announcement is shown.
     */
    private function url(): string
    {
        $classroomSubject = $this->announcement->classroomSubject;

        return route('classroom.subjects.show', [$classroomSubject->classroom_id, $classroomSubject->id]);
    }
}
