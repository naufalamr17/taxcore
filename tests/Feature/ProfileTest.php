<?php

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

test('guests cannot read or change a profile or password', function (): void {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
    $this->put(route('profile.update'), ['name' => 'Other', 'email' => 'other@example.test'])->assertRedirect(route('login'));
    $this->put(route('profile.password'), ['current_password' => 'password', 'password' => '12345678', 'password_confirmation' => '12345678'])->assertRedirect(route('login'));
});

test('every active role can view their own profile without company assignment', function (string $role): void {
    $user = User::factory()->create(['role' => $role, 'name' => '<script>unsafe</script>']);

    $this->actingAs($user)->get(route('profile.edit'))->assertOk()
        ->assertViewHas('user', fn (User $profile): bool => $profile->is($user))
        ->assertSee('Password saat ini')->assertSee($user->email)
        ->assertDontSee('<script>unsafe</script>', false);
})->with(['SUPER_ADMIN', 'TAX_MANAGER', 'PAYROLL_STAFF', 'AUDITOR']);

test('profile changes only the authenticated users name and email', function (): void {
    $user = User::factory()->create(['role' => 'PAYROLL_STAFF']);
    $other = User::factory()->create(['name' => 'Other account']);
    $originalUsername = $user->username;
    $originalPassword = $user->password;

    $this->actingAs($user)->put(route('profile.update'), [
        'name' => 'Nama Baru', 'email' => 'new@example.test', 'id' => $other->id,
        'username' => 'taken-over', 'role' => 'SUPER_ADMIN', 'is_active' => false,
        'company_ids' => [999], 'password' => 'unwanted-password',
    ])->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'Profil berhasil diperbarui.');

    expect($user->fresh())->name->toBe('Nama Baru')->email->toBe('new@example.test')
        ->username->toBe($originalUsername)->role->toBe('PAYROLL_STAFF')->is_active->toBeTrue()
        ->password->toBe($originalPassword)->email_verified_at->toBeNull();
    expect($other->fresh()->name)->toBe('Other account');
    expect($user->companies()->count())->toBe(0);
    $this->assertDatabaseHas('audit_events', ['user_id' => $user->id, 'action' => 'user.profile.updated', 'subject' => 'user:'.$user->id]);
});

test('profile can retain its own email without losing verification', function (): void {
    $user = User::factory()->create();
    $verifiedAt = $user->email_verified_at;

    $this->actingAs($user)->put(route('profile.update'), ['name' => 'Updated name', 'email' => $user->email])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email_verified_at->equalTo($verifiedAt))->toBeTrue();
});

test('profile rejects an email already used by another account without partial changes', function (): void {
    $user = User::factory()->create(['name' => 'Original']);
    $other = User::factory()->create();

    $this->actingAs($user)->put(route('profile.update'), ['name' => 'Not saved', 'email' => $other->email])->assertSessionHasErrors('email');

    expect($user->fresh()->name)->toBe('Original');
    expect(AuditEvent::where('action', 'user.profile.updated')->count())->toBe(0);
});

test('password update requires the current password and leaves the account unchanged on failure', function (): void {
    $user = User::factory()->create();
    $originalToken = $user->remember_token;

    $this->actingAs($user)->put(route('profile.password'), ['current_password' => 'incorrect', 'password' => '12345678', 'password_confirmation' => '12345678'])
        ->assertSessionHasErrors(['current_password' => 'Password saat ini tidak sesuai.']);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    expect($user->fresh()->remember_token)->toBe($originalToken);
    expect(AuditEvent::where('action', 'user.password.updated')->count())->toBe(0);
});

test('password update rejects a short password or mismatched confirmation', function (string $password, string $confirmation, string $message): void {
    $user = User::factory()->create();

    $this->actingAs($user)->put(route('profile.password'), ['current_password' => 'password', 'password' => $password, 'password_confirmation' => $confirmation])
        ->assertSessionHasErrors(['password' => $message]);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    expect(AuditEvent::where('action', 'user.password.updated')->count())->toBe(0);
})->with([
    'seven characters' => ['1234567', '1234567', 'Password baru minimal 8 karakter.'],
    'different confirmation' => ['12345678', '87654321', 'Konfirmasi password baru tidak sesuai.'],
]);

test('password update hashes eight characters rotates the remember token and keeps the current session signed in', function (): void {
    $user = User::factory()->create();
    $originalToken = $user->remember_token;

    $this->actingAs($user)->put(route('profile.password'), ['current_password' => 'password', 'password' => '12345678', 'password_confirmation' => '12345678'])
        ->assertRedirect(route('profile.edit'))->assertSessionHas('status', 'Password berhasil diperbarui.');

    expect(Hash::check('12345678', $user->fresh()->password))->toBeTrue();
    expect(Hash::check('password', $user->fresh()->password))->toBeFalse();
    expect($user->fresh()->remember_token)->not->toBe($originalToken);
    expect(AuditEvent::where('action', 'user.password.updated')->firstOrFail()->details)->toBe([]);
    $this->assertAuthenticatedAs($user);
    $this->get(route('profile.edit'))->assertOk();
});

test('inactive users cannot update their profile', function (): void {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)->put(route('profile.update'), ['name' => 'Not saved', 'email' => 'new@example.test'])->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->email)->not->toBe('new@example.test');
});

test('repeated failed password changes are rate limited', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);
    $data = ['current_password' => 'incorrect', 'password' => '12345678', 'password_confirmation' => '12345678'];

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->put(route('profile.password'), $data)->assertSessionHasErrors('current_password');
    }

    $this->put(route('profile.password'), $data)->assertTooManyRequests();
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});
