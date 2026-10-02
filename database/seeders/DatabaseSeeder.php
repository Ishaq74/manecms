<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $localAccount = $this->account(
            name: $this->localValue('username'),
            email: $this->localValue('email'),
            password: $this->localValue('password'),
        );

        $guestAccount = $this->account(
            name: 'Guest',
            email: 'guest@manecms.test',
            password: 'Guest+123',
        );

        if (app()->environment('local')) {
            $this->call(TenancySeeder::class, parameters: ['owner' => $localAccount, 'guest' => $guestAccount]);
            $this->call(AuditSeeder::class);
            $this->call(IdentitySeeder::class, parameters: ['user' => $localAccount]);
        }
    }

    /**
     * Read one value of the local account from the configuration.
     *
     * None of them is stored in the repository. Failing loudly matters here: a
     * missing password would leave an account that anyone can log into, so the
     * seeder stops instead of falling back to something guessable.
     *
     * @throws RuntimeException
     */
    protected function localValue(string $key): string
    {
        $value = config("database.seeder.local.{$key}");

        if (! is_string($value) || $value === '') {
            throw new RuntimeException(
                sprintf('SEEDER_%s is required to seed the local account. Add it to .env.', strtoupper($key)),
            );
        }

        return $value;
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
