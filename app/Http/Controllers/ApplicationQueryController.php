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

    /**
     * Everyone with a stake in an application's query thread hears about every message.
     *
     * The old branch told one side of one pairing: a vendor's message reached staff, a
     * staff message reached the vendor. Nobody else in the thread was told, so a query
     * answered by the tender owner left the Purchase Officer unaware, and whoever had
     * written earlier never learned there was a reply.
     *
     * Recipients are now the application's own vendor, the Purchase Officer and
     * super-admins, the tender's owner, and anyone who has already written in this thread -
     * minus the sender, who does not need an email about their own message.
     */
    private function sendNotifications(ApplicationQuery $query, TenderApplication $application, string $senderRole): void
    {
        $notifyUsers = collect();

        // The vendor whose application this is.
        $vendorUser = $application->vendor?->user;
        if ($vendorUser) {
            $notifyUsers->push($vendorUser);
        }

        // The staff who run the tender.
        $notifyUsers = $notifyUsers->merge(User::role(['super-admin', 'admin'])->get());

        if ($application->tender && $application->tender->owner_id) {
            $owner = User::find($application->tender->owner_id);
            if ($owner) {
                $notifyUsers->push($owner);
            }
        }

        // Anyone already in the conversation.
        $participantIds = ApplicationQuery::where('application_id', $application->id)
            ->pluck('sender_id')
            ->filter()
            ->unique();
        if ($participantIds->isNotEmpty()) {
            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $participantIds)->get());
        }

        // First message in the thread is the query; anything after it is a reply.
        $type = ApplicationQuery::where('application_id', $application->id)
            ->where('id', '<', $query->id)
            ->exists() ? 'reply' : 'query';

        foreach ($notifyUsers->unique('id') as $notifiable) {
            if ((int) $notifiable->id === (int) $query->sender_id) {
                continue;
            }

            try {
                $notifiable->notify(new ApplicationQueryNotification($query, $type));
            } catch (\Exception $e) {
                \Log::error('Query notification error for user ' . $notifiable->id . ': ' . $e->getMessage());
            }
        }
    }
}
