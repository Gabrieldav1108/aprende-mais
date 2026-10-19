<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInvitationRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets a user invited by an admin verify their e-mail and define their password through the e-mailed link.
 */
class AcceptInvitationController extends Controller
{
    /**
     * Show the form to define the password, or the expired link state when the token is no longer valid.
     */
    public function show(Request $request, string $token): Response
    {
        $email = (string) $request->query('email');
        $user = User::query()->where('email', $email)->first();

        return Inertia::render('auth/accept-invitation', [
            'token' => $token,
            'email' => $email,
            'isValid' => $user !== null && $this->broker()->tokenExists($user, $token),
            'passwordRules' => PasswordRule::defaults()->toPasswordRulesString(),
        ]);
    }

    /**
     * Define the password, mark the e-mail as verified and log the user in.
     */
    public function store(AcceptInvitationRequest $request): RedirectResponse
    {
        $acceptedUser = null;

        $status = $this->broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use (&$acceptedUser): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
                $user->markEmailAsVerified();

                event(new PasswordReset($user));

                $acceptedUser = $user;
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __('Este convite é inválido ou expirou. Peça um novo convite ao administrador.')]);
        }

        Auth::login($acceptedUser);
        $request->session()->regenerate();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Senha definida. Bem-vindo!')]);

        return redirect()->intended(route('dashboard'));
    }

    private function broker(): PasswordBroker
    {
        return Password::broker('invitations');
    }
}
