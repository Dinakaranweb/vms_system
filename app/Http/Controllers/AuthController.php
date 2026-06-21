<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\VendorRegisteredMail;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole();
        }
        return view('auth.login');
    }

   public function login(Request $request)
{
    // Validate input
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);
    
    // Log attempt for debugging
    \Log::info('Login attempt', ['email' => $request->email]);
    
    // Attempt login
    if (Auth::attempt($request->only('email', 'password'), $request->remember)) {
        $user = Auth::user();
        
        \Log::info('Login successful', ['user_id' => $user->id, 'email' => $user->email]);
        
        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return back()->withErrors(['email' => 'Your account has been deactivated.']);
        }
        
        // Check vendor approval
        if ($user->hasRole('vendor')) {
            if (!$user->vendor) {
                Auth::logout();
                return back()->withErrors(['email' => 'Vendor profile not found.']);
            }
            
            if ($user->vendor->status !== 'approved') {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is pending admin approval.']);
            }
        }
        
        // Regenerate session to prevent fixation
        $request->session()->regenerate();
        
        // Update last login
        $user->update(['last_login_at' => now()]);
        
        // Redirect based on role
        if ($user->hasRole('super-admin')) {
            return redirect()->route('super-admin.dashboard');
        }
        
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }
        
        if ($user->hasRole('tender-owner')) {
            return redirect()->route('tender-owner.dashboard');
        }
        
        if ($user->hasRole('vendor')) {
            return redirect()->route('vendor.dashboard');
        }

        if ($user->hasRole('committee-member')) {
            return redirect()->route('committee.dashboard');
        }

        // Default redirect if no role found
        Auth::logout();
        return back()->withErrors(['email' => 'Your account does not have a valid role.']);
    }
    
    \Log::warning('Login failed - invalid credentials', ['email' => $request->email]);
    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
}
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'company_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'pincode' => 'required|string|max:10',
            'pan_number' => 'required|string|unique:vendors',
            'gst_number' => 'nullable|string|unique:vendors',
            'registration_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'pan_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();
        
        try {
            // Handle document uploads
            $documents = [];
            if ($request->hasFile('registration_certificate')) {
                $documents['registration_certificate'] = $request->file('registration_certificate')->store('vendor-documents', 'public');
            }
            if ($request->hasFile('pan_document')) {
                $documents['pan_document'] = $request->file('pan_document')->store('vendor-documents', 'public');
            }

            // Create vendor
            $vendor = Vendor::create([
                'company_name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'pincode' => $validated['pincode'],
                'pan_number' => $validated['pan_number'],
                'gst_number' => $validated['gst_number'] ?? null,
                'status' => 'pending',
                'registration_documents' => $documents,
            ]);

            // Create user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'vendor_id' => $vendor->id,
                'is_active' => true,
            ]);

            $user->assignRole('vendor');

            DB::commit();

            // Notify admins of new vendor registration
            try {
                $admins = User::role(['admin', 'super-admin'])->get();
                foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new VendorRegisteredMail($vendor));
                }
            } catch (\Exception $e) {
                \Log::error('Vendor registration email failed: ' . $e->getMessage());
            }

            return redirect('/login')->with('success', 'Registration successful! Your application is pending admin approval. You will be notified once approved.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    private function redirectBasedOnRole()
    {
        $user = Auth::user();
        
        if ($user->hasRole('super-admin')) {
            return redirect('/super-admin/dashboard');
        }
        if ($user->hasRole('admin')) {
            return redirect('/admin/dashboard');
        }
        if ($user->hasRole('vendor')) {
            return redirect('/vendor/dashboard');
        }
        
        return redirect('/login');
    }
}