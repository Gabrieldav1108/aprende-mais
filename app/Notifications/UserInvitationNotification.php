<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Password;

/**
 * Invites a user registered by an admin to verify their e-mail and define their password.
 */
class UserInvitationNotification extends QueuedMailNotification
{
    /**
     * Skip the e-mail when the user accepted an earlier invitation before this one was sent.
     */
    public function shouldSend(User $notifiable, string $channel): bool
    {
        return ! $notifiable->hasVerifiedEmail();
    }

    /**
     * Create the invitation token on the worker, so the plain token is never stored in the jobs table.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $token = Password::broker('invitations')->createToken($notifiable);
        $days = intdiv(config('auth.passwords.invitations.expire'), 60 * 24);

        return (new MailMessage)
            ->subject(__('Bem-vindo ao :app — defina sua senha', ['app' => config('app.name')]))
            ->greeting(__('Olá, :name!', ['name' => $notifiable->name]))
            ->line(__('Você foi cadastrado no :app. Para acessar, confirme seu e-mail e defina sua senha.', ['app' => config('app.name')]))
            ->action(__('Definir senha'), route('invitation.show', ['token' => $token, 'email' => $notifiable->email]))
            ->line(trans_choice('{1} Este link é válido por :count dia.|[2,*] Este link é válido por :count dias.', $days, ['count' => $days]))
            ->line(__('Se você não esperava este convite, ignore este e-mail.'))
            ->salutation(__('Atenciosamente, equipe :app', ['app' => config('app.name')]));
    }
}
