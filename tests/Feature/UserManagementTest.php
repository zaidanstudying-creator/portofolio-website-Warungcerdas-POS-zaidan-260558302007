<?php

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('guests cannot access user management', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
    $this->get(route('users.create'))->assertRedirect(route('login'));
    $this->get(route('account.password.edit'))->assertRedirect(route('login'));
    $this->post(route('users.store'), [])->assertRedirect(route('login'));
});

test('kasir cannot view, create, or update users', function () {
    $kasir = User::factory()->kasir()->create();
    $target = User::factory()->kasir()->create();
    $this->actingAs($kasir);

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('users.create'))->assertForbidden();
    $this->get(route('users.edit', $target))->assertForbidden();
    $this->post(route('users.store'), [
        'name' => 'Baru',
        'email' => 'baru@example.test',
        'role' => UserRole::Kasir->value,
    ])->assertForbidden();
    $this->put(route('users.update', $target), [
        'name' => 'Ubah',
        'email' => $target->email,
        'role' => UserRole::Kasir->value,
    ])->assertForbidden();
});

test('admin can view the user list', function () {
    $admin = User::factory()->admin()->create();
    $kasir = User::factory()->kasir()->create(['name' => 'Kasir Satu']);
    $this->actingAs($admin);

    $this->get(route('users.index'))
        ->assertOk()
        ->assertSee('Kasir Satu')
        ->assertSee($kasir->email);
});

test('admin can create kasir and admin accounts with directly assigned hashed passwords', function () {
    Notification::fake();
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('users.store'), [
        'name' => 'Kasir Baru',
        'email' => 'Kasir.Baru@Example.test',
        'role' => UserRole::Kasir->value,
        'password' => 'Kasir12A',
        'password_confirmation' => 'Kasir12A',
    ])->assertRedirect(route('users.index'));

    $this->post(route('users.store'), [
        'name' => 'Admin Baru',
        'email' => 'admin.baru@example.test',
        'role' => UserRole::Admin->value,
        'password' => 'Admin12A',
        'password_confirmation' => 'Admin12A',
    ])->assertRedirect(route('users.index'));

    $kasir = User::where('email', 'kasir.baru@example.test')->firstOrFail();
    $admin = User::where('email', 'admin.baru@example.test')->firstOrFail();

    expect($kasir->role)->toBe(UserRole::Kasir)
        ->and($kasir->isActive())->toBeTrue()
        ->and($admin->role)->toBe(UserRole::Admin);

    expect(Hash::check('Kasir12A', $kasir->password))->toBeTrue()
        ->and(Hash::check('Admin12A', $admin->password))->toBeTrue()
        ->and(ActivityLog::where('event', 'created')->count())->toBe(2)
        ->and(ActivityLog::where('event', 'password_set_by_admin')->count())->toBe(2);

    Notification::assertNothingSent();
});

test('duplicate email is rejected', function () {
    User::factory()->create(['email' => 'ada@example.test']);
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('users.store'), [
        'name' => 'Duplikat',
        'email' => 'ada@example.test',
        'role' => UserRole::Kasir->value,
        'password' => 'PasswordAwal12345',
        'password_confirmation' => 'PasswordAwal12345',
    ])->assertSessionHasErrors('email');

    expect(User::where('email', 'ada@example.test')->count())->toBe(1);
});

test('admin assigned passwords must satisfy the password policy', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('users.store'), [
        'name' => 'Password Tujuh Karakter',
        'email' => 'tujuh@example.test',
        'role' => UserRole::Kasir->value,
        'password' => 'Aa12345',
        'password_confirmation' => 'Aa12345',
    ])->assertSessionHasErrors('password');

    $this->post(route('users.store'), [
        'name' => 'Password Lemah',
        'email' => 'lemah@example.test',
        'role' => UserRole::Kasir->value,
        'password' => 'pendek1',
        'password_confirmation' => 'pendek1',
    ])->assertSessionHasErrors('password');

    $this->post(route('users.store'), [
        'name' => 'Password Tanpa Angka',
        'email' => 'tanpaangka@example.test',
        'role' => UserRole::Kasir->value,
        'password' => 'hanyahurufaja',
        'password_confirmation' => 'hanyahurufaja',
    ])->assertSessionHasErrors('password');

    expect(User::whereIn('email', ['lemah@example.test', 'tanpaangka@example.test'])->exists())->toBeFalse();
});

test('account management rejects attempts to create or promote a super admin', function () {
    Notification::fake();
    $this->actingAs(User::factory()->admin()->create());

    $this->post(route('users.store'), [
        'name' => 'Akun istimewa palsu',
        'email' => 'palsu@example.test',
        'role' => UserRole::AdministratorUtama->value,
    ])->assertSessionHasErrors('role');

    $target = User::factory()->kasir()->create();
    $this->put(route('users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => UserRole::AdministratorUtama->value,
    ])->assertSessionHasErrors('role');

    expect(User::whereNotNull('super_admin_key')->count())->toBe(0)
        ->and($target->refresh()->role)->toBe(UserRole::Kasir);
});

test('admin can change a user role', function () {
    $this->actingAs(User::factory()->admin()->create());
    $target = User::factory()->kasir()->create();

    $this->put(route('users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => UserRole::Admin->value,
    ])->assertRedirect(route('users.index'));

    expect($target->refresh()->role)->toBe(UserRole::Admin);
});

test('an inactive account cannot log in', function () {
    User::factory()->inactive()->create([
        'email' => 'nonaktif@example.test',
        'password' => 'rahasia123456',
    ]);

    $this->post(route('login.store'), [
        'email' => 'nonaktif@example.test',
        'password' => 'rahasia123456',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a deactivated account cannot keep using its old session', function () {
    $user = User::factory()->kasir()->create();
    $this->actingAs($user);

    $this->get(route('pos.index'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->get(route('pos.index'))->assertForbidden();
    $this->assertGuest();
});

test('a user can be reactivated', function () {
    $this->actingAs(User::factory()->admin()->create());
    $target = User::factory()->inactive()->create();

    $this->put(route('users.update', $target), [
        'name' => $target->name,
        'email' => $target->email,
        'role' => UserRole::Kasir->value,
        'is_active' => '1',
    ])->assertRedirect(route('users.index'));

    expect($target->refresh()->isActive())->toBeTrue();
});

test('the last active admin cannot be deactivated or demoted', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $other = User::factory()->admin()->create();

    expect(User::where('role', UserRole::Admin)->where('is_active', true)->count())->toBe(2);

    $this->put(route('users.update', $other), [
        'name' => $other->name,
        'email' => $other->email,
        'role' => UserRole::Kasir->value,
    ])->assertRedirect(route('users.index'));

    expect($other->refresh()->role)->toBe(UserRole::Kasir)
        ->and(User::where('role', UserRole::Admin)->where('is_active', true)->count())->toBe(1);

    $lastAdmin = $admin->refresh();

    $this->put(route('users.update', $lastAdmin), [
        'name' => $lastAdmin->name,
        'email' => $lastAdmin->email,
        'role' => UserRole::Kasir->value,
    ])->assertSessionHasErrors('role');

    expect($lastAdmin->refresh()->role)->toBe(UserRole::Admin)
        ->and(User::where('role', UserRole::Admin)->where('is_active', true)->count())->toBe(1);
});

test('admin cannot deactivate or demote themselves', function () {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->put(route('users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::Admin->value,
        'is_active' => '0',
    ])->assertSessionHasErrors('is_active');

    $this->put(route('users.update', $admin), [
        'name' => $admin->name,
        'email' => $admin->email,
        'role' => UserRole::Kasir->value,
    ])->assertSessionHasErrors('role');

    expect($admin->refresh()->isActive())->toBeTrue()
        ->and($admin->role)->toBe(UserRole::Admin)
        ->and($other->refresh()->isActive())->toBeTrue();
});

test('role and is_active are not mass assignable', function () {
    $user = User::create([
        'name' => 'Test',
        'email' => 'mass@example.test',
        'password' => 'password',
        'role' => 'admin',
        'is_active' => false,
    ]);

    $fresh = $user->fresh();

    expect($fresh->role)->toBe(UserRole::Kasir)
        ->and($fresh->isActive())->toBeTrue();
});

test('a rejected last-admin change leaves no partial update', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $originalName = $admin->name;
    $originalEmail = $admin->email;

    $this->put(route('users.update', $admin), [
        'name' => 'Nama Baru',
        'email' => 'email.baru@example.test',
        'role' => UserRole::Kasir->value,
    ])->assertSessionHasErrors('role');

    $admin->refresh();

    expect($admin->name)->toBe($originalName)
        ->and($admin->email)->toBe($originalEmail)
        ->and($admin->role)->toBe(UserRole::Admin);
});

test('an admin can update an ordinary account without changing its password', function () {
    $this->actingAs(User::factory()->admin()->create());
    $target = User::factory()->kasir()->create(['password' => 'password-lama']);
    $oldHash = $target->password;

    $this->put(route('users.update', $target), [
        'name' => 'Nama Baru',
        'email' => 'baru@example.test',
        'role' => UserRole::Kasir->value,
    ])->assertRedirect(route('users.index'));

    $target->refresh();

    expect($target->name)->toBe('Nama Baru')
        ->and($target->email)->toBe('baru@example.test')
        ->and($target->password)->toBe($oldHash)
        ->and(Hash::check('password-lama', $target->password))->toBeTrue();
});

test('admin and kasir cannot update delete or reset the super admin through direct requests', function () {
    Notification::fake();
    $superAdmin = User::factory()->superAdmin()->create();

    foreach ([User::factory()->admin()->create(), User::factory()->kasir()->create()] as $actor) {
        $this->actingAs($actor);

        $this->put(route('users.update', $superAdmin), [
            'name' => 'Manipulated',
            'email' => $superAdmin->email,
            'role' => UserRole::Kasir->value,
            'password' => 'PasswordManipulasi123',
            'password_confirmation' => 'PasswordManipulasi123',
            'is_active' => '0',
        ])->assertForbidden();

        $this->delete(route('users.destroy', $superAdmin))->assertForbidden();
    }

    expect($superAdmin->refresh()->isSuperAdmin())->toBeTrue()
        ->and($superAdmin->isActive())->toBeTrue();

    Notification::assertNothingSent();
});

test('only one row can hold the super admin marker', function () {
    User::factory()->superAdmin()->create();

    expect(fn () => User::factory()->superAdmin()->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

test('the unique marker keeps protecting the account if its role is manually changed', function () {
    Notification::fake();
    $protectedUser = User::factory()->superAdmin()->create();
    $protectedUser->forceFill(['role' => UserRole::Admin])->save();
    $this->actingAs($protectedUser);

    $this->get(route('users.index'))->assertForbidden();
    $this->get(route('pos.index'))->assertForbidden();

    $this->actingAs(User::factory()->admin()->create());

    $this->put(route('users.update', $protectedUser), [
        'name' => $protectedUser->name,
        'email' => $protectedUser->email,
        'role' => UserRole::Kasir->value,
        'password' => 'PasswordManipulasi123',
        'password_confirmation' => 'PasswordManipulasi123',
    ])->assertForbidden();

    $this->delete(route('users.destroy', $protectedUser))->assertForbidden();

    Notification::assertNothingSent();
    expect($protectedUser->refresh()->hasSuperAdminMarker())->toBeTrue();
});

test('super admin can manage ordinary accounts but cannot reach operational modules', function () {
    Notification::fake();
    $superAdmin = User::factory()->superAdmin()->create();
    $target = User::factory()->kasir()->create();
    $this->actingAs($superAdmin);

    $this->get(route('users.index'))->assertOk();
    $this->get(route('users.edit', $target))->assertOk();
    $this->get(route('account.password.edit'))->assertOk();
    $this->get(route('welcome'))->assertForbidden();
    $this->get(route('pos.index'))->assertForbidden();
    $this->get(route('orders.index'))->assertForbidden();
    $this->get(route('reports.index'))->assertForbidden();
    $this->get(route('finance.index'))->assertForbidden();
    $this->get(route('settings.qris.edit'))->assertForbidden();

    $this->put(route('users.update', $target), [
        'name' => 'Nama Baru',
        'email' => $target->email,
        'role' => UserRole::Kasir->value,
        'is_active' => '0',
    ])->assertRedirect(route('users.index'));

    expect($target->refresh()->name)->toBe('Nama Baru')
        ->and($target->isActive())->toBeFalse();

    $this->put(route('users.update', $target), [
        'name' => 'Nama Baru',
        'email' => $target->email,
        'role' => UserRole::Kasir->value,
        'is_active' => '1',
    ])->assertRedirect(route('users.index'));

    expect($target->refresh()->isActive())->toBeTrue();
});

test('admins can directly reset ordinary account passwords without sending email', function () {
    Notification::fake();
    $actors = [
        User::factory()->admin()->create(),
        User::factory()->superAdmin()->create(),
    ];

    foreach ($actors as $index => $actor) {
        $target = User::factory()->kasir()->create([
            'password' => 'PasswordLama123',
        ]);
        $this->actingAs($actor);

        $this->get(route('users.edit', $target))
            ->assertOk()
            ->assertSee('Password baru (opsional)')
            ->assertDontSee('PasswordLama123');

        $this->put(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => UserRole::Kasir->value,
            'password' => "PasswordBaru{$index}12345",
            'password_confirmation' => "PasswordBaru{$index}12345",
        ])->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'Akun berhasil diperbarui dan password baru disimpan.');

        expect(Hash::check("PasswordBaru{$index}12345", $target->refresh()->password))->toBeTrue()
            ->and(Hash::check('PasswordLama123', $target->password))->toBeFalse();
    }

    $logs = ActivityLog::where('event', 'password_reset_by_admin')->get();
    expect($logs)->toHaveCount(2);

    foreach ($logs as $log) {
        expect($log->properties)->toBe(['method' => 'account_edit'])
            ->and(json_encode($log->properties))->not->toContain('password')
            ->and(json_encode($log->properties))->not->toContain('PasswordBaru');
    }

    Notification::assertNothingSent();
});
