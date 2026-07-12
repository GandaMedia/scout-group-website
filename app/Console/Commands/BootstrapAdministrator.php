<?php

namespace App\Console\Commands;

use App\Enums\UserApprovalStatus;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('users:bootstrap-admin {email : Email address of an existing verified user} {--force : Allow the command in production}')]
#[Description('Approve an existing user and grant the super-admin role')]
class BootstrapAdministrator extends Command
{
    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->error('Pass --force to bootstrap an administrator in production.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('email', mb_strtolower((string) $this->argument('email')))
            ->first();

        if (! $user instanceof User) {
            $this->error('No user exists with that email address. Register and verify the account first.');

            return self::FAILURE;
        }

        if (! $user->hasVerifiedEmail()) {
            $this->error('The user must verify their email address first.');

            return self::FAILURE;
        }

        $this->callSilent('db:seed', [
            '--class' => RolesAndPermissionsSeeder::class,
            '--force' => true,
        ]);

        $user->forceFill([
            'approval_status' => UserApprovalStatus::APPROVED,
            'approved_at' => $user->approved_at ?? now(),
            'rejected_at' => null,
        ])->save();
        $user->assignRole('super-admin');

        $this->info("{$user->email} is now an approved super-administrator.");

        return self::SUCCESS;
    }
}
