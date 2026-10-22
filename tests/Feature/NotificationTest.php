<?php

use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\ClassroomSubject;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Testing\AssertableInertia;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * Notify the user about a new announcement of a new classroom subject and return the stored notification.
 */
function notifyAboutAnnouncement(User $user): DatabaseNotification
{
    $classroom = Classroom::factory()->create();
    $classroomSubject = ClassroomSubject::create([
        'classroom_id' => $classroom->id,
        'subject_id' => Subject::factory()->forCourse($classroom->course_id)->create()->id,
        'teacher_id' => Teacher::factory()->inSchool($classroom->school_id)->create()->id,
    ]);

    $user->notify(new AnnouncementPublishedNotification(Announcement::factory()->forClassroomSubject($classroomSubject)->create()));

    return $user->notifications()->latest()->first();
}

test('the unread count and latest notifications are shared with every page', function () {
    $user = User::factory()->create();
    notifyAboutAnnouncement($user);
    notifyAboutAnnouncement($user)->markAsRead();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('notifications.unread_count', 1)
            ->has('notifications.latest', 2)
            ->has('notifications.latest.0', fn (AssertableInertia $item) => $item
                ->hasAll(['id', 'data', 'read_at', 'created_at'])));
});

test('opening a notification marks it as read and visits its page', function () {
    $user = User::factory()->create();
    $notification = notifyAboutAnnouncement($user);

    $this->actingAs($user)
        ->post(route('notification.read', $notification->id))
        ->assertRedirect($notification->data['url']);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a notification of another user is not found', function () {
    $notification = notifyAboutAnnouncement(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->post(route('notification.read', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('every notification of the user can be marked as read at once', function () {
    $user = User::factory()->create();
    notifyAboutAnnouncement($user);
    notifyAboutAnnouncement($user);
    $other = User::factory()->create();
    notifyAboutAnnouncement($other);

    $this->actingAs($user)
        ->from(route('dashboard'))
        ->post(route('notification.read-all'))
        ->assertRedirect(route('dashboard'));

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($other->unreadNotifications()->count())->toBe(1);
});
