<?php

namespace App\Http\Controllers;

use App\Models\ApplicationQuery;
use App\Models\TenderApplication;
use App\Models\User;
use App\Notifications\ApplicationQueryNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ApplicationQueryController extends Controller
{
    /**
     * Store a new query or reply on an application.
     */
    public function store(Request $request, $applicationId)
    {
        $request->validate([
            'message'    => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $application = TenderApplication::with(['tender', 'vendor.user'])->findOrFail($applicationId);
        $user        = Auth::user();

        // Determine sender role
        $senderRole = 'vendor';
        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            $senderRole = 'admin';
        } elseif ($user->hasRole('tender-owner')) {
            $senderRole = 'tender-owner';
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store("application-queries/{$applicationId}", 'public');
        }

        $query = ApplicationQuery::create([
            'application_id' => $applicationId,
            'sender_id'      => $user->id,
            'sender_role'    => $senderRole,
            'message'        => $request->message,
            'attachment_path'=> $attachmentPath,
            'attachment_name'=> $attachmentName,
        ]);

        // Determine who to notify
        $this->sendNotifications($query, $application, $senderRole);

        return back()->with('success', 'Message sent successfully.');
    }

    /**
     * Mark all unread queries on an application as read for the current user.
     */
    public function markRead(Request $request, $applicationId)
    {
        $user = Auth::user();

        // Mark DB notifications read
        $user->unreadNotifications()
            ->where('data->application_id', $applicationId)
            ->get()
            ->each->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Return query thread for AJAX (optional – views handle it via blade).
     */
    public function thread($applicationId)
    {
        $application = TenderApplication::findOrFail($applicationId);
        $queries     = ApplicationQuery::where('application_id', $applicationId)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return response()->json(['queries' => $queries]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function sendNotifications(ApplicationQuery $query, TenderApplication $application, string $senderRole): void
    {
        $notifyUsers = collect();

        if ($senderRole === 'vendor') {
            // Notify admin(s) and tender owner
            $adminUsers = User::role(['super-admin', 'admin'])->get();
            $notifyUsers = $notifyUsers->merge($adminUsers);

            if ($application->tender->owner_id) {
                $owner = User::find($application->tender->owner_id);
                if ($owner) $notifyUsers->push($owner);
            }
            $type = 'reply';
        } else {
            // Notify the vendor
            $vendorUser = $application->vendor?->user;
            if ($vendorUser) $notifyUsers->push($vendorUser);
            $type = 'query';
        }

        foreach ($notifyUsers->unique('id') as $notifiable) {
            try {
                $notifiable->notify(new ApplicationQueryNotification($query, $type));
            } catch (\Exception $e) {
                \Log::error('Query notification error: ' . $e->getMessage());
            }
        }
    }
}
