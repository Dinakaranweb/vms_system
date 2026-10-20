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

        // Management (oversight) Permissions
        $managementPermissions = [
            'view all tenders',
            'raise tender queries',
        ];

        // Purchase Committee Permissions
        $purchaseCommitteePermissions = [
            'open tenders',
            'review award recommendations',
            'approve award recommendations',
            'enter postal bid data',
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
            $userPermissions,
            $managementPermissions,
            $purchaseCommitteePermissions
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

        // Management Role - University leadership (VC / Pro-VC / Registrar / Finance Officer):
        // read-only oversight of all published/closed tenders, can raise queries seen by Super Admin & Tender Owner
        $managementRole = Role::firstOrCreate(['name' => 'management', 'guard_name' => 'web']);
        $managementRole->syncPermissions([
            'view all tenders',
            'raise tender queries',
        ]);

        // Purchase Committee Role - reviews & sequentially approves vendor awards after bid opening
        $purchaseCommitteeRole = Role::firstOrCreate(['name' => 'purchase-committee', 'guard_name' => 'web']);
        $purchaseCommitteeRole->syncPermissions([
            'open tenders',
            'review award recommendations',
            'approve award recommendations',
            'enter postal bid data',
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
        $this->command->info('  - management (University Leadership — Read-Only Oversight)');
        $this->command->info('  - purchase-committee (Sequential Award Approval)');
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