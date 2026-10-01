<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates the PostgreSQL roles of todo/todo.md §17.1 and hands the schema to the owner.
 *
 * Runs once per environment with a superuser. Running it again only refreshes
 * passwords, ownership and grants.
 */
#[Signature('database:provision
    {--superuser= : Superuser name (defaults to DB_SUPERUSER_USERNAME, then the current connection user)}
    {--database=* : Databases to prepare (defaults to the current database)}')]
#[Description('Create the owner, app and test roles and give the schema to the owner')]
class ProvisionDatabaseCommand extends Command
{
    public function handle(): int
    {
        $roles = $this->roles();
        $databases = $this->databases();

        $this->createRoles($this->superuserConnection($databases[0]), $roles);

        foreach ($databases as $database) {
            $this->prepareDatabase($this->superuserConnection($database), $database, $roles);
            $this->components->info("Database [{$database}] is owned by [{$roles['owner']['username']}].");
        }

        return self::SUCCESS;
    }

    /**
     * @return array{owner: array{username: string, password: string}, app: array{username: string, password: string}, test: array{username: string, password: string}}
     */
    private function roles(): array
    {
        $roles = [];

        foreach (['owner', 'app', 'test'] as $key) {
            $username = config()->string("database.roles.{$key}.username");
            $this->guardIdentifier($username);

            $roles[$key] = [
                'username' => $username,
                'password' => config()->string("database.roles.{$key}.password"),
            ];
        }

        return $roles;
    }

    /**
     * @return non-empty-list<string>
     */
    private function databases(): array
    {
        /** @var list<string> $requested */
        $requested = $this->option('database');
        $databases = $requested !== [] ? $requested : [config()->string('database.connections.pgsql.database')];

        foreach ($databases as $database) {
            $this->guardIdentifier($database);
        }

        return $databases;
    }

    private function superuserConnection(string $database): Connection
    {
        $base = config()->array('database.connections.pgsql');
        $username = $this->option('superuser') ?? config('database.superuser.username') ?? $base['username'];
        $password = config('database.superuser.password') ?? ($username === $base['username'] ? $base['password'] : '');

        config(['database.connections.provisioning' => [
            ...$base,
            'url' => null,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ]]);

        DB::purge('provisioning');

        return DB::connection('provisioning');
    }

    /**
     * @param  array{owner: array{username: string, password: string}, app: array{username: string, password: string}, test: array{username: string, password: string}}  $roles
     */
    private function createRoles(Connection $connection, array $roles): void
    {
        $attributes = [
            'owner' => 'LOGIN NOSUPERUSER NOCREATEROLE NOCREATEDB BYPASSRLS',
            'app' => 'LOGIN NOSUPERUSER NOCREATEROLE NOCREATEDB NOBYPASSRLS',
            'test' => 'LOGIN NOSUPERUSER NOCREATEROLE NOCREATEDB NOBYPASSRLS',
        ];

        foreach ($roles as $key => $role) {
            $exists = $connection->selectOne('select 1 as found from pg_roles where rolname = ?', [$role['username']]) !== null;
            $password = $role['password'] === '' ? '' : ' PASSWORD '.$connection->getPdo()->quote($role['password']);

            $connection->statement(sprintf(
                '%s ROLE "%s" WITH %s%s',
                $exists ? 'ALTER' : 'CREATE',
                $role['username'],
                $attributes[$key],
                $password,
            ));
        }

        $connection->statement(sprintf('GRANT "%s" TO "%s"', $roles['app']['username'], $roles['test']['username']));
        $connection->statement(sprintf(
            'GRANT "%s" TO "%s" WITH INHERIT FALSE, SET TRUE',
            $roles['owner']['username'],
            $roles['test']['username'],
        ));
    }

    /**
     * @param  array{owner: array{username: string, password: string}, app: array{username: string, password: string}, test: array{username: string, password: string}}  $roles
     */
    private function prepareDatabase(Connection $connection, string $database, array $roles): void
    {
        $owner = $roles['owner']['username'];
        $app = $roles['app']['username'];

        $connection->statement("ALTER DATABASE \"{$database}\" OWNER TO \"{$owner}\"");
        $connection->statement("ALTER SCHEMA public OWNER TO \"{$owner}\"");
        $connection->statement("GRANT CONNECT ON DATABASE \"{$database}\" TO \"{$app}\"");
        $connection->statement("GRANT USAGE ON SCHEMA public TO \"{$app}\"");

        foreach ($this->names($connection, 'pg_tables', 'tablename') as $table) {
            $connection->statement(sprintf('ALTER TABLE public."%s" OWNER TO "%s"', $table, $owner));
        }

        foreach ($this->names($connection, 'pg_sequences', 'sequencename') as $sequence) {
            $connection->statement(sprintf('ALTER SEQUENCE public."%s" OWNER TO "%s"', $sequence, $owner));
        }

        $connection->statement("GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO \"{$app}\"");
        $connection->statement("GRANT USAGE, SELECT, UPDATE ON ALL SEQUENCES IN SCHEMA public TO \"{$app}\"");
        $connection->statement("ALTER DEFAULT PRIVILEGES FOR ROLE \"{$owner}\" IN SCHEMA public GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO \"{$app}\"");
        $connection->statement("ALTER DEFAULT PRIVILEGES FOR ROLE \"{$owner}\" IN SCHEMA public GRANT USAGE, SELECT, UPDATE ON SEQUENCES TO \"{$app}\"");
    }

    /**
     * @return list<string>
     */
    private function names(Connection $connection, string $catalog, string $column): array
    {
        return array_values(array_filter(
            $connection->table($catalog)->where('schemaname', 'public')->pluck($column)->all(),
            'is_string',
        ));
    }

    private function guardIdentifier(string $identifier): void
    {
        if (preg_match('/^[a-z_][a-z0-9_]*$/', $identifier) !== 1) {
            throw new InvalidArgumentException("Invalid PostgreSQL identifier [{$identifier}].");
        }
    }
}
