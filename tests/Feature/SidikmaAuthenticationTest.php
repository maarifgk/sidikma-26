<?php

namespace Tests\Feature;

use App\Filament\Auth\Pages\IntegratedLogin;
use App\Filament\Auth\Pages\RequestPasswordReset;
use App\Filament\Auth\Pages\ResetPassword;
use App\Models\User;
use App\Notifications\SidikmaResetPassword;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidikmaAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));

        foreach ([User::ROLE_ADMIN_INDUK, User::ROLE_ADMIN_SEKOLAH_MADRASAH, User::ROLE_GURU_PEGAWAI] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_authentication_pages_use_sidikma_layout_and_email_login(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Sistem Data dan Informasi Kelembagaan')
            ->assertSee('Masuk ke akun Anda')
            ->assertSee('Lupa password?');

        $this->get('/admin/password-reset/request')
            ->assertOk()
            ->assertSee('Lupa Password')
            ->assertSee('Kirim Link Reset Password');

        Livewire::test(IntegratedLogin::class)
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('password')
            ->assertFormFieldExists('remember');
    }

    public function test_login_rejects_wrong_password_with_generic_message(): void
    {
        User::factory()->create([
            'email' => 'pengguna@example.test',
            'password' => Hash::make('password-benar'),
        ])->assignRole(User::ROLE_ADMIN_INDUK);

        Livewire::test(IntegratedLogin::class)
            ->set('data.email', 'pengguna@example.test')
            ->set('data.password', 'password-salah')
            ->call('authenticate')
            ->assertHasErrors(['data.email'])
            ->assertSee('Email atau password yang Anda masukkan tidak sesuai.');

        $this->assertGuest();
    }

    public function test_login_remembers_user_and_preserves_role_redirect(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => Hash::make('password-benar'),
        ]);
        $user->assignRole(User::ROLE_ADMIN_INDUK);

        Livewire::test(IntegratedLogin::class)
            ->set('data.email', 'admin@example.test')
            ->set('data.password', 'password-benar')
            ->set('data.remember', true)
            ->call('authenticate')
            ->assertRedirect(url('/admin'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_school_admin_keeps_operational_panel_redirect(): void
    {
        $user = User::factory()->create([
            'email' => 'admin-sekolah@example.test',
            'password' => Hash::make('password-benar'),
        ]);
        $user->assignRole(User::ROLE_ADMIN_SEKOLAH_MADRASAH);

        Livewire::test(IntegratedLogin::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password-benar')
            ->call('authenticate')
            ->assertRedirect(url('/app'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_forgot_password_sends_branded_notification_without_revealing_unknown_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $user->assignRole(User::ROLE_ADMIN_INDUK);

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', 'reset@example.test')
            ->call('request')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, SidikmaResetPassword::class);

        Livewire::test(RequestPasswordReset::class)
            ->set('data.email', 'tidak-ada@example.test')
            ->call('request')
            ->assertHasNoErrors()
            ->assertNotified('Jika email terdaftar, link reset password akan dikirim.');
    }

    public function test_valid_token_resets_password_and_invalid_token_does_not(): void
    {
        $user = User::factory()->create([
            'email' => 'token@example.test',
            'password' => Hash::make('password-lama'),
        ]);
        $user->assignRole(User::ROLE_ADMIN_INDUK);
        $token = Password::broker()->createToken($user);

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => 'token-tidak-valid'])
            ->set('password', 'PasswordBaru2026!')
            ->set('passwordConfirmation', 'PasswordBaru2026!')
            ->call('resetPassword');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->set('password', 'PasswordBaru2026!')
            ->set('passwordConfirmation', 'PasswordBaru2026!')
            ->call('resetPassword')
            ->assertRedirect('/admin/login');

        $this->assertTrue(Hash::check('PasswordBaru2026!', $user->fresh()->password));
        $this->assertSame(Password::INVALID_TOKEN, Password::broker()->reset([
            'email' => $user->email,
            'password' => 'PasswordLain2026!',
            'password_confirmation' => 'PasswordLain2026!',
            'token' => $token,
        ], static function (): void {}));

        auth()->logout();

        Livewire::test(IntegratedLogin::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'PasswordBaru2026!')
            ->call('authenticate')
            ->assertRedirect(url('/admin'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_reset_token_cannot_change_password(): void
    {
        $user = User::factory()->create([
            'email' => 'kedaluwarsa@example.test',
            'password' => Hash::make('password-lama'),
        ]);
        $user->assignRole(User::ROLE_ADMIN_INDUK);
        $token = Password::broker()->createToken($user);

        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        Livewire::test(ResetPassword::class, ['email' => $user->email, 'token' => $token])
            ->set('password', 'PasswordBaru2026!')
            ->set('passwordConfirmation', 'PasswordBaru2026!')
            ->call('resetPassword');

        $this->assertTrue(Hash::check('password-lama', $user->fresh()->password));
    }
}
