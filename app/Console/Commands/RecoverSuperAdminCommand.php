<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('app:recover-super-admin')]
#[Description('Reset the Administrator Utama password using confirmed interactive console access.')]
class RecoverSuperAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Pemulihan Administrator Utama membutuhkan console interaktif.');

            return self::FAILURE;
        }

        $user = User::where('super_admin_key', 'primary')->first();

        if (! $user?->isSuperAdmin()) {
            $this->error('Akun Administrator Utama tidak ditemukan atau penandanya tidak valid.');

            return self::FAILURE;
        }

        $confirmation = text(label: 'Ketik RECOVER-SUPER-ADMIN untuk mengganti password', required: true);

        if ($confirmation !== 'RECOVER-SUPER-ADMIN') {
            $this->error('Konfirmasi tidak cocok. Password tidak diubah.');

            return self::FAILURE;
        }

        $newPassword = password(label: 'Password baru (minimal 8 karakter)', required: true);
        $passwordConfirmation = password(label: 'Konfirmasi password baru', required: true);

        if ($newPassword !== $passwordConfirmation) {
            $this->error('Konfirmasi password tidak cocok.');

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

        DB::transaction(function () use ($user, $newPassword): void {
            $locked = User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isSuperAdmin()) {
                throw new \RuntimeException('Penanda Administrator Utama berubah; password tidak diubah.');
            }

            $locked->forceFill([
                'password' => $newPassword,
                'remember_token' => Str::random(60),
            ])->save();

            ActivityLog::create([
                'log_name' => 'user_management',
                'description' => 'Administrator Utama password recovered through console.',
                'subject_type' => User::class,
                'subject_id' => $locked->id,
                'event' => 'super_admin_password_recovered',
                'properties' => ['email' => $locked->email],
            ]);
        });

        $this->info('Password Administrator Utama berhasil diganti.');

        return self::SUCCESS;
    }
}
