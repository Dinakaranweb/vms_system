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
     * Everyone with a stake in a tender's query thread hears about every message in it.
     *
     * This used to branch on who sent the message and tell only the other side of one
     * particular pairing: an oversight sender reached the owner and the super-admins, and a
     * staff reply reached oversight. Two whole constituencies were therefore never told
     * anything at all — the Purchase Officer, who runs the tender, and the vendors bidding
     * on it, who are the people a clarification usually concerns. A vendor could ask a
     * question and nobody on the tender would see it.
     *
     * So the thread now notifies its participants: the tender's owner, the Purchase Officer
     * and super-admins, anyone holding the oversight permission, the vendors who applied, and
     * anyone who has already written in this thread. The sender is dropped — nobody needs
     * an email about their own message.
     *
     * Vendors are scoped to those who actually applied. Every vendor on the system would be
     * both noise and a disclosure, since the fact of a query on a tender is itself a signal.
     */
    private function sendNotifications(TenderQuery $query, Tender $tender, User $sender): void
    {
        $notifyUsers = collect();

        // The staff who run this tender.
        if ($tender->owner_id) {
            $owner = User::find($tender->owner_id);
            if ($owner) {
                $notifyUsers->push($owner);
            }
        }

        $notifyUsers = $notifyUsers->merge(User::role(['super-admin', 'admin'])->get());

        // Spatie throws if the permission has been removed from the system. That used to
        // cost one branch of the notifications; now it would cost all of them, so it must
        // not be able to take the whole thread down with it.
        try {
            $notifyUsers = $notifyUsers->merge(User::permission('raise tender queries')->get());
        } catch (\Throwable $e) {
            \Log::warning('Tender query oversight recipients skipped: ' . $e->getMessage());
        }

        // The vendors bidding on it.
        $appliedVendorIds = $tender->applications()->pluck('vendor_id')->filter()->unique();

        if ($appliedVendorIds->isNotEmpty()) {
            $notifyUsers = $notifyUsers->merge(
                User::whereNotNull('vendor_id')->whereIn('vendor_id', $appliedVendorIds)->get()
            );
        }

        // Anyone already in the conversation, whatever their role.
        $participantIds = TenderQuery::where('tender_id', $tender->id)
            ->pluck('sender_id')
            ->filter()
            ->unique();

        if ($participantIds->isNotEmpty()) {
            $notifyUsers = $notifyUsers->merge(User::whereIn('id', $participantIds)->get());
        }

        // A thread that already had messages in it is being replied to; the first message is
        // the query itself. This reads correctly for every recipient, which the old
        // sender-based label did not once more than two parties were involved.
        $type = TenderQuery::where('tender_id', $tender->id)
            ->where('id', '<', $query->id)
            ->exists() ? 'reply' : 'query';

        foreach ($notifyUsers->unique('id') as $notifiable) {
            if ((int) $notifiable->id === (int) $sender->id) {
                continue;
            }

            try {
                $notifiable->notify(new TenderQueryNotification($query, $type));
            } catch (\Exception $e) {
                \Log::error('Tender query notification error for user ' . $notifiable->id . ': ' . $e->getMessage());
            }
        }
    }
}
