<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\User;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total_vendors' => Vendor::count(),
            'pending_vendors' => Vendor::where('status', 'pending')->count(),
            'approved_vendors' => Vendor::where('status', 'approved')->count(),
            'total_admins' => User::role('admin')->count(),
        ];
        
        return view('super-admin.dashboard', compact('stats'));
    }
}