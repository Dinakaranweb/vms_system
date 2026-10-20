<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PurchaseCommitteeMember;
use App\Models\User;
use App\Mail\PasswordResetByAdminMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

class PurchaseCommitteeManagementController extends Controller
{
    public function index()
    {
        $members = PurchaseCommitteeMember::with('user')->ordered()->get();
        return view('super-admin.purchase-committee.index', compact('members'));
    }

    public function create()
    {
        return view('super-admin.purchase-committee.create');
    }

    /**
     * Add a member — either an existing user, or create a brand-new user (name, email,
     * designation) and add them in one step, mirroring how Tender Owners add committee
     * members elsewhere in the app.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email',
            'designation'=> 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $user = User::where('email', $request->email)->first();
            $isNewUser = !$user;

            if (!$user) {
                $user = User::create([
                    'name'      => $request->name,
                    'email'     => $request->email,
                    'password'  => Hash::make(str()->random(32)),
                    'is_active' => true,
                ]);
            }

            if ($user->hasRole('purchase-committee')) {
                DB::rollBack();
                return back()->withErrors(['email' => 'This user is already a Purchase Committee member.'])->withInput();
            }

            $user->assignRole('purchase-committee');

            $nextOrder = (int) (PurchaseCommitteeMember::max('approval_order') ?? 0) + 1;

            PurchaseCommitteeMember::create([
                'user_id'        => $user->id,
                'designation'    => $request->designation,
                'approval_order' => $nextOrder,
                'is_active'      => true,
                'added_by'       => auth()->id(),
            ]);

            DB::commit();

            if ($isNewUser) {
                Password::sendResetLink(['email' => $user->email]);
            }

            $msg = "{$user->name} added to the Purchase Committee (approval order #{$nextOrder})."
                . ($isNewUser ? ' A password-setup email has been sent to them.' : '');

            return redirect()->route('super-admin.purchase-committee.index')->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to add member: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Persist a new approval order after drag-reorder on the index page.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'order'   => 'required|array',
            'order.*' => 'exists:purchase_committee_members,id',
        ]);

        foreach ($request->order as $i => $memberId) {
            PurchaseCommitteeMember::where('id', $memberId)->update(['approval_order' => $i + 1]);
        }

        return response()->json(['success' => true]);
    }

    public function toggleActive($id)
    {
        $member = PurchaseCommitteeMember::findOrFail($id);
        $member->update(['is_active' => !$member->is_active]);

        return response()->json(['success' => true, 'message' => $member->is_active ? 'Member reactivated.' : 'Member deactivated.']);
    }

    public function destroy($id)
    {
        $member = PurchaseCommitteeMember::findOrFail($id);
        $user = $member->user;
        $member->delete();

        if ($user) {
            $user->removeRole('purchase-committee');
        }

        return response()->json(['success' => true, 'message' => 'Member removed from the Purchase Committee.']);
    }

    /**
     * Set/reset a committee member's password directly from this page, without needing
     * to go through the separate User Management screen.
     */
    public function resetPassword(Request $request, $id)
    {
        $member = PurchaseCommitteeMember::with('user')->findOrFail($id);
        if (!$member->user) {
            return response()->json(['success' => false, 'message' => 'This member has no linked user account.'], 400);
        }

        $request->validate(['password' => 'required|min:8|confirmed']);

        $user = $member->user;
        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::log([
            'action'            => 'password_reset',
            'module'            => 'user',
            'record_id'         => $user->id,
            'record_identifier' => $user->email,
            'description'       => "Password reset for Purchase Committee member: {$user->name} ({$user->email}) by " . auth()->user()->name,
        ]);

        try {
            Mail::to($user->email)->send(new PasswordResetByAdminMail($user));
        } catch (\Exception $e) {
            \Log::error('Password reset notification email failed: ' . $e->getMessage());
        }

        return response()->json(['success' => true, 'message' => "Password reset for {$user->name}. They have been notified by email."]);
    }
}
