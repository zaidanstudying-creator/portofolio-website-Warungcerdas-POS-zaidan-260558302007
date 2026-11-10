<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:create-super-admin')]
#[Description('Create the single Administrator Utama account through a confirmed interactive setup.')]
class CreateSuperAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! app()->runningInConsole()) {
            $this->error('Perintah ini hanya dapat dijalankan melalui console.');

            return self::FAILURE;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Setup Administrator Utama membutuhkan console interaktif.');

            return self::FAILURE;
        }

        if (User::whereNotNull('super_admin_key')->exists()) {
            $this->error('Administrator Utama sudah ada. Gunakan prosedur pemulihan akses jika diperlukan.');

            return self::FAILURE;
        }

        $confirmation = text(label: 'Ketik CREATE-ONE-SUPER-ADMIN untuk melanjutkan', required: true);

        if ($confirmation !== 'CREATE-ONE-SUPER-ADMIN') {
            $this->error('Konfirmasi tidak cocok. Tidak ada akun yang dibuat.');

            return self::FAILURE;
        }

        $name = trim(text(label: 'Nama lengkap', required: true));
        $email = strtolower(trim(text(
            label: 'Email Administrator Utama',
            required: true,
            validate: fn (string $value) => match (true) {
                ! filter_var($value, FILTER_VALIDATE_EMAIL) => 'Format email tidak valid.',
                User::where('email', strtolower(trim($value)))->exists() => 'Email sudah digunakan.',
                default => null,
            },
        )));

        $newPassword = password(label: 'Kata sandi (minimal 8 karakter)', required: true);
        $passwordConfirmation = password(label: 'Konfirmasi kata sandi', required: true);

        if ($newPassword !== $passwordConfirmation) {
            $this->error('Konfirmasi kata sandi tidak cocok.');

            return self::FAILURE;
        }

        $validator = Validator::make(['password' => $newPassword], [
            'password' => ['required', Password::min(8)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $newPassword): void {
            $user = new User;
            $user->name = $name;
            $user->email = $email;
            $user->password = $newPassword;
            $user->email_verified_at = now();
            $user->forceFill([
                'role' => UserRole::AdministratorUtama,
                'is_active' => true,
                'super_admin_key' => 'primary',
            ]);
            $user->save();

            ActivityLog::create([
                'log_name' => 'user_management',
                'description' => 'Administrator Utama created through console setup.',
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'event' => 'super_admin_created',
                'properties' => ['name' => $user->name, 'email' => $user->email],
            ]);
        });

        $this->info('Administrator Utama berhasil dibuat. Simpan kredensial secara aman.');

        return self::SUCCESS;
    }
}
