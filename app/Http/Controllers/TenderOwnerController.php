<?php

namespace App\Http\Controllers;

use App\Mail\CommitteeAssignedMail;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\User;
use App\Traits\HasDashboardWidgets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class TenderOwnerController extends Controller
{
   use HasDashboardWidgets;

   public function dashboard()
    {
        $user = auth()->user();
        $ownedIds = $user->ownedTenders()->pluck('id');

        $stats = [
            'total'              => $user->ownedTenders()->count(),
            'published'          => $user->ownedTenders()->where('status', 'published')->count(),
            'draft'              => $user->ownedTenders()->where('status', 'draft')->count(),
            'closed'             => $user->ownedTenders()->where('status', 'closed')->count(),
            'total_applications' => TenderApplication::whereIn('tender_id', $ownedIds)->count(),
        ];

        $appStats = [
            'submitted'   => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'shortlisted')->count(),
            'rejected'    => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'rejected')->count(),
            'awarded'     => TenderApplication::whereIn('tender_id', $ownedIds)->where('status', 'awarded')->count(),
        ];

        // Top 5 tenders by application count
        $topTenders = $user->ownedTenders()
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->take(5)->get();

        // Applications per month (last 6 months)
        $appMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $appMonthly[] = [
                'label' => $m->format('M y'),
                'count' => TenderApplication::whereIn('tender_id', $ownedIds)
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->count(),
            ];
        }

        $onlineCount = $user->ownedTenders()->where('tender_type', 'online')->count();
        $postalCount = $user->ownedTenders()->where('tender_type', 'postal')->count();
        $hybridCount = $user->ownedTenders()->where('tender_type', 'hybrid')->count();

        $tenders = $user->ownedTenders()->withCount('applications')->latest()->paginate(10);
        $widgetData = $this->getDashboardWidgetData();

        return view('tender-owner.dashboard', compact(
            'tenders', 'stats', 'appStats',
            'topTenders', 'appMonthly',
            'onlineCount', 'postalCount', 'hybridCount', 'widgetData'
        ));
    }
    
    public function showCommittee($id)
    {
        $tender = Tender::with('committeeMembers')->findOrFail($id);
        $isAdmin = auth()->user()->hasAnyRole(['admin', 'super-admin']);

        if (!$isAdmin && $tender->owner_id !== auth()->id()) {
            abort(403, 'You do not have permission to manage committee for this tender.');
        }

        $users = User::whereDoesntHave('committeeTenders', function ($q) use ($id) {
            $q->where('tender_id', $id);
        })->whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'tender-owner', 'committee-member']);
        })->get();

        $availableRoles = Role::whereIn('name', $this->assignableSystemRoles())->get();

        if ($isAdmin) {
            $segment = request()->segment(1); // 'admin' or 'super-admin'
            return view('admin.tenders.committee', compact('tender', 'users', 'availableRoles', 'segment'));
        }

        return view('tender-owner.committee', compact('tender', 'users', 'availableRoles'));
    }
    
    public function addCommitteeMember(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:chairman,member,observer,technical_expert,financial_expert',
            'designation' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);
        
        // Putting somebody on a tender's committee is no longer immediate. The request goes
        // to the main Purchase Committee in the Super Admin's approval order, and the seat
        // carries nothing — no documents, no opening code, not even an email to the person
        // concerned — until the last main member has approved it. The chain owns all of
        // that, including the assignment email, which is why none of it happens here.
        try {
            $member = User::findOrFail($request->user_id);

            $result = app(\App\Services\TenderCommitteeApprovalChain::class)->request(
                $tender,
                $member,
                auth()->user(),
                $request->role,
                $request->designation,
                $request->permissions ?? []
            );

            return response()->json([
                'success'  => $result['ok'],
                'approved' => $result['approved'],
                'message'  => $result['message'],
            ], $result['ok'] ? 200 : 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to add committee member: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function removeCommitteeMember($tenderId, $userId)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        try {
            $tender->committeeMembers()->detach($userId);
            
            return response()->json([
                'success' => true,
                'message' => 'Committee member removed successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove committee member'
            ], 500);
        }
    }
    
    /**
     * System roles the current actor may hand to a user they create on this screen.
     *
     * This endpoint validated spatie_role as `required|string` and then passed it to
     * Role::firstOrCreate() before assignRole(), so any caller could mint a user with ANY
     * role - 'super-admin' and 'purchase-committee' included - and invent new roles on the
     * way. The dropdown openly offered 'admin' to tender owners, so a tender owner could
     * create themselves an admin account; a hand-posted value could go further still.
     *
     * Purchase Committee is absent at every level on purpose. Those members hold the codes
     * that open tenders, so only a Super Admin maintains them - the same rule the user
     * management screen enforces.
     */
    private function assignableSystemRoles(): array
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            return ['committee-member', 'tender-owner', 'admin'];
        }

        if ($user->hasRole('admin')) {
            return ['committee-member', 'tender-owner'];
        }

        return ['committee-member'];
    }

    /**
     * Create a brand-new user and immediately add them to the tender committee.
     */
    public function createAndAddCommitteeMember(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $assignable = $this->assignableSystemRoles();

        $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:8',
            // Allowlisted, and it must be a role that already exists - never one this
            // request can bring into being.
            'spatie_role'  => 'required|string|exists:roles,name|in:' . implode(',', $assignable),
            'role'         => 'required|in:chairman,member,observer,technical_expert,financial_expert',
            'designation'  => 'nullable|string|max:255',
            'permissions'  => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            // Validated against assignableSystemRoles() and exists:roles,name above, so
            // there is nothing to create here - roles are never minted from request input.
            $spatieRoleName = $request->spatie_role;

            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => Hash::make($request->password),
                'is_active' => true,
            ]);
            $user->assignRole($spatieRoleName);

            // The login is created here and now - it has to be, since the seat is requested
            // against a real user. The seat itself goes to the main Purchase Committee for
            // approval like any other, so creating the account is not a way around that.
            DB::commit();

            $result = app(\App\Services\TenderCommitteeApprovalChain::class)->request(
                $tender,
                $user,
                auth()->user(),
                $request->role,
                $request->designation,
                $request->permissions ?? []
            );

            return response()->json([
                'success'  => $result['ok'],
                'approved' => $result['approved'],
                'message'  => "Login created for '{$user->name}'. " . $result['message'],
                'user'     => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            ], $result['ok'] ? 200 : 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateCommitteeMember(Request $request, $tenderId, $userId)
    {
        $tender = Tender::findOrFail($tenderId);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasAnyRole(['admin', 'super-admin'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'role' => 'required|in:chairman,member,observer,technical_expert,financial_expert',
            'designation' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);
        
        try {
            $tender->committeeMembers()->updateExistingPivot($userId, [
                'role' => $request->role,
                'designation' => $request->designation,
                'is_active' => $request->is_active ?? true,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Committee member updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update committee member'
            ], 500);
        }
    }

    public function notifications()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);
        return view('tender-owner.notifications.index', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        $notification = auth()->user()->notifications()->find($id);
        if ($notification) $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}