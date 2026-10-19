<?php

use App\Models\Person;
use App\Models\School;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function invitationAdmin(): User
{
    return User::factory()->admin()->create(['school_id' => School::factory()->create()->id]);
}

function invitedPerson(User $admin): Person
{
    return Person::factory()->inSchool($admin->school_id)->create([
        'user_id' => User::factory()->unverified()->create(['school_id' => $admin->school_id])->id,
    ]);
}

/**
 * Build the invitation e-mail and return the token of its link.
 */
function invitationToken(User $user): string
{
    $url = (new UserInvitationNotification)->toMail($user)->actionUrl;

    return basename(parse_url($url, PHP_URL_PATH));
}

test('registering a user e-mails them the invitation', function () {
    Notification::fake();

    $this->actingAs(invitationAdmin())->post(route('user.store'), [
        'name' => 'Ana Prado',
        'email' => 'ana@example.com',
        'roles' => ['student'],
    ])->assertRedirect(route('user'));

    $user = User::where('email', 'ana@example.com')->sole();

    expect($user->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, UserInvitationNotification::class);
});

test('the invitation is queued on the mail queue after the transaction commits', function () {
    Queue::fake();
    $user = User::factory()->unverified()->create();

    $user->notify(new UserInvitationNotification);

    Queue::assertPushedOn('mail', SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof UserInvitationNotification
        && $job->notification->afterCommit === true
        && $job->tries === 5);
});

test('the invitation token is created when the e-mail is built and is never stored in the queue payload', function () {
    $user = User::factory()->unverified()->create();
    $notification = new UserInvitationNotification;

    expect(serialize($notification))->not->toContain('token');

    $mail = $notification->toMail($user);

    expect($mail->actionUrl)->toStartWith(url('convite').'/')
        ->and($mail->actionUrl)->toContain('email='.urlencode($user->email))
        ->and(implode(' ', $mail->outroLines))->toContain('7 dias');
    $this->assertDatabaseHas('user_invitation_tokens', ['email' => $user->email]);
});

test('the invitation is not sent to a user who already accepted one', function () {
    $user = User::factory()->create();

    expect((new UserInvitationNotification)->shouldSend($user, 'mail'))->toBeFalse()
        ->and((new UserInvitationNotification)->shouldSend(User::factory()->unverified()->create(), 'mail'))->toBeTrue();
});

test('a new invitation invalidates the previous link', function () {
    $user = User::factory()->unverified()->create();

    $first = invitationToken($user);
    $second = invitationToken($user);

    $broker = Password::broker('invitations');
    expect($broker->tokenExists($user, $first))->toBeFalse()
        ->and($broker->tokenExists($user, $second))->toBeTrue();
});

test('the invitation page shows the form for a valid link and the expired state otherwise', function () {
    $user = User::factory()->unverified()->create();
    $token = invitationToken($user);

    $this->get(route('invitation.show', ['token' => $token, 'email' => $user->email]))
        ->assertInertia(fn ($page) => $page->component('auth/accept-invitation')->where('isValid', true)->where('email', $user->email));

    $this->get(route('invitation.show', ['token' => 'wrong', 'email' => $user->email]))
        ->assertInertia(fn ($page) => $page->where('isValid', false));

    $this->get(route('invitation.show', ['token' => $token, 'email' => 'other@example.com']))
        ->assertInertia(fn ($page) => $page->where('isValid', false));
});

test('accepting the invitation defines the password, verifies the e-mail and logs the user in', function () {
    $user = User::factory()->unverified()->create();
    $token = invitationToken($user);

    $this->post(route('invitation.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('dashboard'));

    $user->refresh();

    $this->assertAuthenticatedAs($user);
    expect($user->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('new-password-123', $user->password))->toBeTrue();
    $this->assertDatabaseMissing('user_invitation_tokens', ['email' => $user->email]);
});

test('an invitation link cannot be used twice', function () {
    $user = User::factory()->unverified()->create();
    $payload = [
        'token' => invitationToken($user),
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ];

    $this->post(route('invitation.store'), $payload);
    auth()->logout();

    $this->post(route('invitation.store'), [...$payload, 'password' => 'other-pass-456', 'password_confirmation' => 'other-pass-456'])
        ->assertSessionHasErrors('email');

    expect(Hash::check('new-password-123', $user->fresh()->password))->toBeTrue();
});

test('an invalid or expired invitation is rejected', function () {
    $user = User::factory()->unverified()->create();
    $token = invitationToken($user);
    $payload = ['email' => $user->email, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];

    $this->post(route('invitation.store'), [...$payload, 'token' => 'wrong'])->assertSessionHasErrors('email');

    $this->travel(8)->days();

    $this->post(route('invitation.store'), [...$payload, 'token' => $token])->assertSessionHasErrors('email');

    $this->assertGuest();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('accepting the invitation validates the password', function () {
    $user = User::factory()->unverified()->create();

    $this->post(route('invitation.store'), [
        'token' => invitationToken($user),
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
});

test('a logged in user cannot open the invitation page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('invitation.show', ['token' => 'any', 'email' => 'any@example.com']))
        ->assertRedirect(route('dashboard'));
});

test('a user with a pending invitation cannot reach the app', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('an admin can resend a pending invitation', function () {
    Notification::fake();
    $admin = invitationAdmin();
    $person = invitedPerson($admin);

    $this->actingAs($admin)->post(route('user.invitation', $person))->assertRedirect(route('user'));

    Notification::assertSentTo($person->user, UserInvitationNotification::class);
});

test('an invitation is not resent to a user who already accepted it', function () {
    Notification::fake();
    $admin = invitationAdmin();
    $person = Person::factory()->inSchool($admin->school_id)->create();

    $this->actingAs($admin)->post(route('user.invitation', $person))->assertRedirect(route('user'));

    Notification::assertNothingSent();
});

test('resending an invitation is throttled per person', function () {
    Notification::fake();
    $admin = invitationAdmin();
    $person = invitedPerson($admin);

    $this->actingAs($admin)->post(route('user.invitation', $person))->assertRedirect(route('user'));
    $this->post(route('user.invitation', $person))->assertTooManyRequests();
    $this->post(route('user.invitation', invitedPerson($admin)))->assertRedirect(route('user'));

    Notification::assertSentTimes(UserInvitationNotification::class, 2);
});

test('users without the permission cannot resend invitations', function () {
    $admin = invitationAdmin();
    $person = invitedPerson($admin);

    $this->actingAs(User::factory()->withRole('teacher')->create(['school_id' => $admin->school_id]))
        ->post(route('user.invitation', $person))
        ->assertForbidden();
});

test('an admin cannot resend invitations to people from another school', function () {
    $person = invitedPerson(invitationAdmin());

    $this->actingAs(invitationAdmin())->post(route('user.invitation', $person))->assertNotFound();
});
