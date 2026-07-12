<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $accessLeaderTools = Permission::findOrCreate('access leader tools');
        $accessAdmin = Permission::findOrCreate('access admin');
        $manageLeaderApprovals = Permission::findOrCreate('manage leader approvals');

        Role::findOrCreate('leader')->syncPermissions([$accessLeaderTools]);
        Role::findOrCreate('site-admin')->syncPermissions([$accessAdmin, $manageLeaderApprovals]);
        Role::findOrCreate('super-admin')->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
