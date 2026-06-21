<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ========== CREATE PERMISSIONS ==========
        
        // Document Permissions
        $documentPermissions = [
            'view documents',
            'upload documents',
            'delete documents',
            'download documents',
            'view own documents',
            'upload own documents',
            'delete own documents',
            'download own documents',
        ];

        // Tender Permissions
        $tenderPermissions = [
            'view tenders',
            'create tenders',
            'edit tenders',
            'delete tenders',
            'publish tenders',
            'close tenders',
            'view own tenders',
            'create own tenders',
            'edit own tenders',
            'delete own tenders',
            'publish own tenders',
            'close own tenders',
        ];

        // Application Permissions
        $applicationPermissions = [
            'view applications',
            'submit applications',
            'review applications',
            'shortlist applications',
            'reject applications',
            'award applications',
            'view own applications',
        ];

        // Contract Permissions
        $contractPermissions = [
            'view contracts',
            'create contracts',
            'sign contracts',
            'view own contracts',
        ];

        // Vendor Management Permissions
        $vendorPermissions = [
            'view vendors',
            'create vendors',
            'edit vendors',
            'delete vendors',
            'approve vendors',
        ];

        // Committee Permissions
        $committeePermissions = [
            'manage committee',
            'view committee',
        ];

        // Report Permissions
        $reportPermissions = [
            'view reports',
            'export data',
        ];

        // User Management Permissions
        $userPermissions = [
            'manage users',
        ];

        // Merge all permissions
        $allPermissions = array_merge(
            $documentPermissions,
            $tenderPermissions,
            $applicationPermissions,
            $contractPermissions,
            $vendorPermissions,
            $committeePermissions,
            $reportPermissions,
            $userPermissions
        );

        // Create permissions
        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // ========== CREATE ROLES ==========
        
        // Super Admin Role - Full system access
        $superAdminRole = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // Admin Role - Manage everything except system settings
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions([
            'view vendors',
            'edit vendors',
            'approve vendors',
            'view tenders',
            'create tenders',
            'edit tenders',
            'delete tenders',
            'publish tenders',
            'close tenders',
            'view documents',
            'upload documents',
            'delete documents',
            'download documents',
            'view applications',
            'review applications',
            'shortlist applications',
            'reject applications',
            'award applications',
            'view contracts',
            'create contracts',
            'sign contracts',
            'manage committee',
            'view committee',
            'view reports',
            'export data',
            'manage users',
        ]);

        // Tender Owner Role - Manage only their own tenders
        $tenderOwnerRole = Role::firstOrCreate(['name' => 'tender-owner', 'guard_name' => 'web']);
        $tenderOwnerRole->syncPermissions([
            'view own documents',
            'upload own documents',
            'delete own documents',
            'download own documents',
            'view own tenders',
            'create own tenders',
            'edit own tenders',
            'delete own tenders',
            'publish own tenders',
            'close own tenders',
            'view own applications',
            'shortlist applications',
            'reject applications',
            'award applications',
            'view own contracts',
            'sign contracts',
            'manage committee',
            'view committee',
            'view reports',
            'export data',
        ]);

        // Vendor Role - Limited access for vendors
        $vendorRole = Role::firstOrCreate(['name' => 'vendor', 'guard_name' => 'web']);
        $vendorRole->syncPermissions([
            'view tenders',
            'submit applications',
            'view own applications',
            'view own contracts',
            'sign contracts',
        ]);

        $this->command->info('========================================');
        $this->command->info('Roles and Permissions Created Successfully!');
        $this->command->info('========================================');
        $this->command->info('');
        $this->command->info('Roles Created:');
        $this->command->info('  - super-admin (Full System Access)');
        $this->command->info('  - admin (Administrative Access)');
        $this->command->info('  - tender-owner (Own Tender Management)');
        $this->command->info('  - vendor (Limited Access)');
        $this->command->info('');
        $this->command->info('To assign roles to users, use:');
        $this->command->info('  $user->assignRole(\'role-name\')');
        $this->command->info('');
        $this->command->info('Example:');
        $this->command->info('  $user = User::find(1);');
        $this->command->info('  $user->assignRole(\'tender-owner\');');
        $this->command->info('');
    }
}