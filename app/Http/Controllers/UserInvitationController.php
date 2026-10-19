<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Sends the invitation e-mail again to a user who has not defined their password yet.
 */
class UserInvitationController extends Controller
{
    /**
     * Queue a new invitation, which invalidates the previous link.
     */
    public function store(Person $person): RedirectResponse
    {
        $this->authorize('update', $person);

        $user = $person->user;

        if ($user->hasVerifiedEmail()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Este usuário já aceitou o convite.')]);

            return to_route('user');
        }

        $user->notify(new UserInvitationNotification);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Convite reenviado para :email.', ['email' => $user->email])]);

        return to_route('user');
    }
}
