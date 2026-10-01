<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->account(
            name: 'Ishaq',
            email: 'ishaq.achour@gmail.com',
            password: 'Sofia+123',
        );

        $this->account(
            name: 'Guest',
            email: 'guest@manecms.test',
            password: 'Guest+123',
        );
    }

    /**
     * Create a verified account, or refresh the existing one.
     *
     * The User model casts the password to hashed, so the plaintext is stored
     * as given. Two-factor authentication is left off: the feature is enabled in
     * config/fortify.php, so the settings page is reachable, but no account is
     * challenged at login.
     */
    protected function account(string $name, string $email, string $password): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
            ],
        );
    }
}
