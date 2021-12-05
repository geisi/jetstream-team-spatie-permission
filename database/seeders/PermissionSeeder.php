<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        //Some initially role configuration
        $roles = [
            'Admin' => [
                'view posts',
                'create posts',
                'update posts',
                'delete posts',
            ],
            'Editor' => [
                'view posts',
                'create posts',
                'update posts'
            ],
            'Member' => [
                'view posts'
            ]
        ];

        collect($roles)->each(function ($permissions, $role) {
            $role = Role::findOrCreate($role);
            collect($permissions)->each(function ($permission) use ($role) {
                $role->permissions()->save(Permission::findOrCreate($permission));
            });
        });
    }
}
