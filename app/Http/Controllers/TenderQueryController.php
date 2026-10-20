<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderQuery;
use App\Models\User;
use App\Notifications\TenderQueryNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenderQueryController extends Controller
{
    /**
     * Store a new query or reply on a tender.
     */
    public function store(Request $request, $tenderId)
    {
        $request->validate([
            'message'    => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $tender = Tender::findOrFail($tenderId);
        $user   = Auth::user();

        $senderRole = $user->roles->first()?->name ?? 'management';

        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file           = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store("tender-queries/{$tenderId}", 'public');
        }

        $query = TenderQuery::create([
            'tender_id'       => $tenderId,
            'sender_id'       => $user->id,
            'sender_role'     => $senderRole,
            'message'         => $request->message,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $this->sendNotifications($query, $tender, $user);

        return back()->with('success', 'Message sent successfully.');
    }

    /**
     * Mark all unread tender queries as read for the current user.
     */
    public function markRead(Request $request, $tenderId)
    {
        $user = Auth::user();

        $user->unreadNotifications()
            ->where('data->tender_id', $tenderId)
            ->get()
            ->each->markAsRead();

        return response()->json(['success' => true]);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * "Oversight" senders are any role permitted to raise tender queries in the first place
     * (Management today, any future custom oversight role tomorrow) — determined by permission,
     * not a hardcoded role name, so this keeps working for roles created via the Role Builder.
     */
    private function sendNotifications(TenderQuery $query, Tender $tender, User $sender): void
    {
        $notifyUsers = collect();
        $isOversightSender = $sender->can('raise tender queries');

        if ($isOversightSender) {
            // Notify the tender owner and every super-admin
            if ($tender->owner_id) {
                $owner = User::find($tender->owner_id);
                if ($owner) $notifyUsers->push($owner);
            }
            $notifyUsers = $notifyUsers->merge(User::role('super-admin')->get());
            $type = 'query';
        } else {
            // Owner/Admin/Super-Admin replying — notify every user with oversight permission
            $notifyUsers = User::permission('raise tender queries')->get();
            $type = 'reply';
        }

        foreach ($notifyUsers->unique('id') as $notifiable) {
            try {
                $notifiable->notify(new TenderQueryNotification($query, $type));
            } catch (\Exception $e) {
                \Log::error('Tender query notification error: ' . $e->getMessage());
            }
        }
    }
}
