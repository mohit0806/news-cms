<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;

#[Signature('app:create-admin {email : Administrator email address} {--role=Admin : Role to assign} {--password= : Password for automation only; prefer the interactive prompt as --password appears in shell history}')]
#[Description('Create the first administrator account (PRD §21.1 bootstrap)')]
class CreateAdminUser extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $role = (string) $this->option('role');

        if (! in_array($role, ['Admin', 'Editor', 'Reporter', 'Contributor'], true)) {
            $this->components->error("Unknown role [{$role}].");

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');

        if (User::query()->where('email', $email)->exists()) {
            $this->components->error("A user already exists for [{$email}].");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?: password(
            label: 'Administrator password (minimum 10 characters)',
            validate: ['required', 'string', Password::min(10)],
        ));

        // RoleSeeder is additive-safe: ensures the role exists on fresh installs.
        $this->call('db:seed', ['--class' => RoleSeeder::class]);

        DB::transaction(function () use ($email, $password, $role): void {
            $user = User::create([
                'name' => explode('@', $email)[0],
                'email' => $email,
                'password' => $password,
            ]);

            $user->assignRole($role);
        });

        $this->info("Administrator [{$email}] created with role [{$role}].");

        return self::SUCCESS;
    }
}
