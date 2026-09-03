<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * A non-interactive alternative to `make:filament-user`, for hosts without
 * SSH where a one-off command can only be run with plain --flag=value
 * arguments (e.g. Plesk Scheduled Tasks) - no quoting or nested PHP syntax
 * to get mangled in transit. updateOrCreate() also makes this safe to
 * reuse later purely to reset a password.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {--name=} {--email=} {--password=}';

    protected $description = 'Create or update a Filament admin user (name/email/password given as plain flags)';

    public function handle(): int
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        if (! $name || ! $email || ! $password) {
            $this->error('Usage: artisan admin:create --name=Name --email=you@example.com --password=secret');

            return self::FAILURE;
        }

        User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password]);

        $this->info("Admin user {$email} is ready.");

        return self::SUCCESS;
    }
}
