<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AddTenderOwnerRoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create Tender Owner Role
        $ownerRole = Role::create(['name' => 'tender-owner']);
        
        // Create permissions for tender owner
        $permissions = [
            'create tender',
            'edit own tender',
            'delete own tender',
            'view own tender',
            'publish own tender',
            'close own tender',
            'manage committee',
            'view applications on own tender',
            'shortlist applications on own tender',
            'reject applications on own tender',
            'award applications on own tender',
            'view reports on own tender',
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        // Assign permissions to tender owner
        $ownerRole->givePermissionTo($permissions);
        
        $this->command->info('Tender Owner role and permissions created successfully!');
    }
}