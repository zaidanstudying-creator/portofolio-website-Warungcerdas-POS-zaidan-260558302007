<?php

use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('guests can open the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Masuk')
        ->assertSee('Email')
        ->assertSee('Kata sandi');
});

test('guests can open the password recovery page', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Lupa kata sandi?')
        ->assertSee('Kirim tautan reset')
        ->assertSee('name="email"', false)
        ->assertSee('name="_token"', false);
});

test('a user can request a password reset link', function () {
    $user = User::factory()->create(['email' => 'kasir@example.test']);
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'Jika email terdaftar, tautan reset password telah dikirim.');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('a password reset request for an unknown email uses the same response', function () {
    Notification::fake();

    $this->from(route('password.request'))
        ->post(route('password.email'), ['email' => 'tidak-ada@example.test'])
        ->assertRedirect(route('password.request'))
        ->assertSessionHas('status', 'Jika email terdaftar, tautan reset password telah dikirim.');

    Notification::assertNothingSent();
});

test('a user can reset their password with the emailed token', function () {
    $user = User::factory()->create([
        'email' => 'kasir@example.test',
        'password' => 'rahasia-lama',
    ]);
    Notification::fake();

    $this->post(route('password.email'), ['email' => $user->email]);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return $token !== '';
    });

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Kata sandi baru')
        ->assertSee('Konfirmasi kata sandi')
        ->assertSee('name="email"', false)
        ->assertSee('value="'.$token.'"', false);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'rahasia-baru-123',
        'password_confirmation' => 'rahasia-baru-123',
    ])
        ->assertRedirect(route('login'))
        ->assertSessionHas('success', 'Kata sandi berhasil direset. Silakan login.');

    expect(Hash::check('rahasia-baru-123', $user->refresh()->password))->toBeTrue();

    $log = ActivityLog::where('event', 'password_reset_completed')->firstOrFail();
    expect($log->properties)->toBe(['email' => $user->email]);
});

test('an invalid password reset token does not change the password', function () {
    $user = User::factory()->create([
        'email' => 'kasir@example.test',
        'password' => 'rahasia-lama',
    ]);

    $this->from(route('password.reset', ['token' => 'token-tidak-valid', 'email' => $user->email]))
        ->post(route('password.update'), [
            'token' => 'token-tidak-valid',
            'email' => $user->email,
            'password' => 'rahasia-baru-123',
            'password_confirmation' => 'rahasia-baru-123',
        ])
        ->assertRedirect(route('password.reset', ['token' => 'token-tidak-valid', 'email' => $user->email]))
        ->assertSessionHasErrors('email');

    expect(Hash::check('rahasia-lama', $user->refresh()->password))->toBeTrue();
});

test('a user can log in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'kasir@example.test',
        'password' => 'rahasia123',
    ]);

    $response = $this->post(route('login.store'), [
        'email' => 'kasir@example.test',
        'password' => 'rahasia123',
    ]);

    $response->assertRedirect(route('welcome'));

    $this->assertAuthenticatedAs($user);
});

test('super admin login lands directly on account management', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'email' => 'utama@example.test',
        'password' => 'passwordUtama123',
    ]);

    $this->post(route('login.store'), [
        'email' => 'utama@example.test',
        'password' => 'passwordUtama123',
    ])->assertRedirect(route('users.index'));

    $this->assertAuthenticatedAs($superAdmin);
});

test('a super admin can safely change their own password', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'password' => 'passwordLama123',
    ]);
    $this->actingAs($superAdmin);

    $this->get(route('account.password.edit'))
        ->assertOk()
        ->assertSee('name="_token"', false)
        ->assertSee('name="current_password"', false);

    $this->put(route('account.password.update'), [
        'current_password' => 'password-salah',
        'password' => 'PasswordBaru12345',
        'password_confirmation' => 'PasswordBaru12345',
    ])->assertSessionHasErrors('current_password');

    expect(Hash::check('passwordLama123', $superAdmin->refresh()->password))->toBeTrue();

    $this->put(route('account.password.update'), [
        'current_password' => 'passwordLama123',
        'password' => 'PasswordBaru12345',
        'password_confirmation' => 'PasswordBaru12345',
    ])->assertRedirect();

    expect(Hash::check('PasswordBaru12345', $superAdmin->refresh()->password))->toBeTrue()
        ->and(ActivityLog::where('event', 'own_password_changed')->count())->toBe(1);

    $log = ActivityLog::where('event', 'own_password_changed')->firstOrFail();
    expect($log->description)->not->toContain('PasswordBaru12345')
        ->and(json_encode($log->properties))->toBe('null');
});

test('self password change rejects requests without a CSRF token', function () {
    $superAdmin = User::factory()->superAdmin()->create([
        'password' => 'passwordLama123',
    ]);
    $this->actingAs($superAdmin);
    $this->app->instance('env', 'production');

    $this->put(route('account.password.update'), [
        'current_password' => 'passwordLama123',
        'password' => 'PasswordBaru12345',
        'password_confirmation' => 'PasswordBaru12345',
    ])->assertStatus(419);

    $this->app->instance('env', 'testing');

    expect(Hash::check('passwordLama123', $superAdmin->refresh()->password))->toBeTrue();
});

test('login always redirects to the welcome page instead of an intended asset URL', function () {
    User::factory()->create([
        'email' => 'kasir@example.test',
        'password' => 'rahasia123',
    ]);

    $this->from('/store-logo')->get('/store-logo')->assertRedirect(route('login'));

    $this->post(route('login.store'), [
        'email' => 'kasir@example.test',
        'password' => 'rahasia123',
    ])->assertRedirect(route('welcome'));

    $this->get('/')->assertRedirect(route('welcome'));
    $this->get(route('welcome'))->assertOk();
});

test('login fails with invalid credentials and does not reveal whether the email exists', function () {
    User::factory()->create(['email' => 'kasir@example.test']);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'kasir@example.test',
        'password' => 'salah-sekali',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe('Email atau kata sandi tidak sesuai.');

    $this->assertGuest();
});

test('login with an unknown email shows the same generic error', function () {
    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'tidak-ada@example.test',
        'password' => 'apa-saja',
    ]);

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toBe('Email atau kata sandi tidak sesuai.');

    $this->assertGuest();
});

test('login regenerates the session id', function () {
    User::factory()->create([
        'email' => 'kasir@example.test',
        'password' => 'rahasia123',
    ]);

    $this->get(route('login'));

    $sessionIdBefore = session()->getId();

    $this->post(route('login.store'), [
        'email' => 'kasir@example.test',
        'password' => 'rahasia123',
    ]);

    expect(session()->getId())->not->toBe($sessionIdBefore);
});

test('a logged in user can log out and the session is invalidated', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
    expect(Auth::check())->toBeFalse();
});

test('guests are redirected to login from the pos page', function () {
    $this->get(route('pos.index'))
        ->assertRedirect(route('login'));
});

test('guests are redirected to login from the order list', function () {
    $this->get(route('orders.index'))
        ->assertRedirect(route('login'));
});

test('guests cannot view an order detail or receipt', function () {
    $order = Order::factory()->create();

    $this->get(route('orders.show', $order))->assertRedirect(route('login'));
    $this->get(route('orders.receipt', $order))->assertRedirect(route('login'));
});

test('guests cannot create an order', function () {
    $this->postJson(route('orders.store'), ['type' => 'TA', 'items' => []])
        ->assertUnauthorized();
});

test('guests cannot manage the menu', function () {
    $this->get(route('categories.index'))->assertRedirect(route('login'));
    $this->get(route('products.index'))->assertRedirect(route('login'));
    $this->get(route('toppings.index'))->assertRedirect(route('login'));
});

test('there is no public registration route', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});

test('an authenticated user can still reach the cashier page', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('pos.index'))->assertOk();
});
