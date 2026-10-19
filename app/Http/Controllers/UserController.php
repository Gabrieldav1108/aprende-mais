<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Exceptions\RepositoryException;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Person;
use App\Notifications\UserInvitationNotification;
use App\Repositories\PersonRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Manages access accounts together with the person that owns each one.
 */
class UserController extends Controller
{
    public function __construct(private readonly PersonRepository $people) {}

    /**
     * Display a listing of the people with their access accounts.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Person::class);

        return Inertia::render('user/index', [
            'people' => $this->people->all(),
            'roles' => UserRole::options(),
        ]);
    }

    /**
     * Register a user together with their person and e-mail them the invitation to define their password.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        try {
            $person = $this->people->createWithUser($request->validated());
            $person->user->notify(new UserInvitationNotification);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuário cadastrado. Convite enviado para :email.', ['email' => $person->user->email])]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível cadastrar o usuário.')]);
        }

        return to_route('user');
    }

    /**
     * Update the given person and their access account.
     */
    public function update(UpdateUserRequest $request, Person $person): RedirectResponse
    {
        try {
            $this->people->update($person, $request->validated());

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Usuário atualizado.')]);
        } catch (RepositoryException $e) {
            report($e);

            Inertia::flash('toast', ['type' => 'error', 'message' => __('Não foi possível atualizar o usuário.')]);
        }

        return to_route('user');
    }
}
