<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:reset-password {email} {password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset a user password with proper hashing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Usuario con email '$email' no encontrado.");
            return 1;
        }

        $user->update([
            'password' => Hash::make($password),
            'password_hash' => Hash::make($password),
        ]);

        $this->info("Contraseña actualizada correctamente para $email");
        return 0;
    }
}
