<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Mail\VendorRegisteredMail;
use App\Mail\VendorRegistrationAcknowledgementMail;
use App\Mail\PasswordResetMail;

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

        if ($user->hasRole('management')) {
            return redirect()->route('management.dashboard');
        }

        if ($user->hasRole('purchase-committee')) {
            return redirect()->route('purchase-committee.dashboard');
        }

        // Any custom role created via the Super Admin Role Builder
        if ($user->can('view all tenders')) {
            return redirect()->route('portal.dashboard');
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
        $hasGst = $request->boolean('has_gst');
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
            'gst_number' => $hasGst ? 'required|string|unique:vendors' : 'nullable|string',
            'gst_document' => $hasGst ? 'required|file|mimes:pdf,jpg,jpeg,png|max:5120' : 'nullable|file',
            'pan_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'certificates.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Require at least one certificate if no GST
        if (!$hasGst && !$request->hasFile('certificates')) {
            return back()->withErrors(['certificates' => 'Please upload at least one supporting certificate (MSME, Udyam Aadhaar, or equivalent).'])->withInput();
        }

        DB::beginTransaction();

        try {
            // Handle document uploads
            $documents = [];
            if ($request->hasFile('pan_document')) {
                $documents['pan_document'] = $request->file('pan_document')->store('vendor-documents', 'public');
            }

            if ($hasGst && $request->hasFile('gst_document')) {
                $gstFile = $request->file('gst_document');
                $documents['gst_document'] = [
                    'path' => $gstFile->store('vendor-documents', 'public'),
                    'name' => $gstFile->getClientOriginalName(),
                ];
            }

            // Handle dynamic certificates (any one acceptable)
            if ($request->hasFile('certificates')) {
                $documents['certificates'] = [];
                foreach ($request->file('certificates') as $certKey => $certFile) {
                    if ($certFile && $certFile->isValid()) {
                        $documents['certificates'][$certKey] = [
                            'path' => $certFile->store('vendor-documents', 'public'),
                            'name' => $certFile->getClientOriginalName(),
                            'label' => $request->input("certificate_labels.$certKey", $certKey),
                        ];
                    }
                }
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
                'gst_number' => $hasGst ? ($validated['gst_number'] ?? null) : null,
                'has_gst' => $hasGst,
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

            // Acknowledge the vendor's own registration
            try {
                Mail::to($vendor->email)->send(new VendorRegistrationAcknowledgementMail($vendor));
            } catch (\Exception $e) {
                \Log::error('Vendor registration acknowledgement email failed: ' . $e->getMessage());
            }

            return redirect('/login')->with('success', 'Registration successful! Your application is pending admin approval. You will be notified once approved.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'A password reset link has been sent to your email address.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])
                     ->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Password reset successfully. You can now log in with your new password.');
        }

        return back()->withErrors(['email' => __($status)]);
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
        if ($user->hasRole('management')) {
            return redirect('/management/dashboard');
        }
        if ($user->hasRole('purchase-committee')) {
            return redirect('/purchase-committee/dashboard');
        }
        if ($user->can('view all tenders')) {
            return redirect('/portal/dashboard');
        }

        return redirect('/login');
    }
}