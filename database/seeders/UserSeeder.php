<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super-admin');

        // Create Admin
        $admin = User::create([
            'name' => 'Mr.Manikandan',
            'email' => 'purchaseofficer@vmu.edu.in',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        // Create Vendor
        $vendor = Vendor::create([
            'company_name' => 'Tech Solutions Pvt Ltd',
            'email' => 'vendor@example.com',
            'phone' => '9876543210',
            'address' => '123 Business Park, Mumbai',
            'pan_number' => 'ABCDE1234F',
            'gst_number' => '27AAACA1234A1Z',
            'status' => 'approved',
        ]);

        $vendorUser = User::create([
            'name' => 'Vendor User',
            'email' => 'vendor@example.com',
            'password' => Hash::make('password123'),
            'vendor_id' => $vendor->id,
            'is_active' => true,
        ]);
        $vendorUser->assignRole('vendor');
    }
}