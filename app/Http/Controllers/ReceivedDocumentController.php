<?php

namespace App\Http\Controllers;

use App\Models\ApplicationReceivedDocument;
use App\Models\PurchaseCommitteeMember;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Notifications\BidValueRecordedNotification;
use App\Notifications\ReceivedDocumentsUploadedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Recording the physical documents that actually arrived for a postal or hybrid tender.
 *
 * This step happens AFTER the committee's opening codes have been collected and the tender is
 * open — the envelopes are opened at the table, and what was inside is uploaded or scanned in
 * against the vendor who sent it. Files land under that application's own path
 * (received-documents/{tender}/{application}/), and every upload notifies the whole Purchase
 * Committee, in their login and at their own email address.
 *
 * Two ways in, both ending in the same place:
 *   - multiple file upload, which also covers output from the user's own scanner software;
 *   - the in-browser scan interface, which captures pages from the device camera one at a time
 *     (browsers cannot drive a TWAIN/WIA scanner directly, so a desk scanner is used through
 *     its own software and the resulting file is uploaded).
 *
 * Online-only tenders have nothing to receive by post and are rejected outright.
 */
class ReceivedDocumentController extends Controller
{
    /** Anything the browser can render inline or we can accept from a scan. */
    private const ALLOWED_MIMES = 'pdf,jpg,jpeg,png,webp,tif,tiff,doc,docx,xls,xlsx';

    // ─── Screens ────────────────────────────────────────────────────────────

    /**
     * The upload screen: every application received against this tender, with its details and
     * whatever has already been recorded for it.
     */
    public function index($tenderId)
    {
        $tender = Tender::with('owner')->findOrFail($tenderId);

        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $applications = TenderApplication::where('tender_id', $tender->id)
            ->with(['vendor', 'receivedDocuments.uploadedBy', 'bidRecordedBy'])
            ->orderBy('application_number')
            ->get();

        $stats = [
            'applications'   => $applications->count(),
            'with_documents' => $applications->filter(fn($a) => $a->receivedDocuments->isNotEmpty())->count(),
            'files'          => $applications->sum(fn($a) => $a->receivedDocuments->count()),
            'pending'        => $applications->filter(fn($a) => $a->receivedDocuments->isEmpty())->count(),
            // A postal price only exists once someone types it in, so surface what is still blank.
            'bids_missing'   => $applications->filter(fn($a) => $a->bid_amount === null)->count(),
        ];

        return view('purchase-committee.received-documents.index', compact('tender', 'applications', 'stats'));
    }

    // ─── Actions ────────────────────────────────────────────────────────────

    /**
     * Multiple file upload against one application.
     */
    public function store(Request $request, $tenderId, $applicationId)
    {
        $tender = Tender::findOrFail($tenderId);
        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $application = $this->applicationOf($tender, $applicationId);

        $request->validate([
            'documents'   => 'required|array|min:1',
            'documents.*' => 'required|file|mimes:' . self::ALLOWED_MIMES . '|max:20480',
            'remarks'     => 'nullable|string|max:1000',
        ], [
            'documents.required'   => 'Choose at least one file to upload.',
            'documents.*.mimes'    => 'Allowed file types: PDF, images, Word and Excel.',
            'documents.*.max'      => 'Each file must be 20 MB or smaller.',
        ]);

        $batchId = (string) Str::uuid();
        $saved = 0;

        foreach ($request->file('documents') as $file) {
            $this->record($tender, $application, $file, 'upload', $batchId, null, $request->remarks);
            $saved++;
        }

        $this->notifyCommittee($tender, $application, $saved, 'upload');

        return back()->with('success', $saved . ' document(s) recorded for '
            . ($application->vendor->company_name ?? 'this vendor')
            . ' — the Purchase Committee has been notified.');
    }

    /**
     * Pages captured through the in-browser scan interface. The client posts each page as a
     * data URL; they are stored as individual numbered images under the same batch so a
     * multi-page scan stays together and in order.
     */
    public function storeScan(Request $request, $tenderId, $applicationId)
    {
        $tender = Tender::findOrFail($tenderId);
        if ($guard = $this->guard($tender)) {
            return $this->wantsJson($request)
                ? response()->json(['success' => false, 'message' => 'You are not allowed to record documents for this tender.'], 403)
                : $guard;
        }

        $application = $this->applicationOf($tender, $applicationId);

        $request->validate([
            'pages'   => 'required|array|min:1|max:40',
            'pages.*' => 'required|string',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $batchId = (string) Str::uuid();
        $saved = 0;
        $page = 1;

        foreach ($request->input('pages') as $dataUrl) {
            $binary = $this->decodeImageDataUrl($dataUrl);
            if ($binary === null) {
                continue; // skip anything that is not a plain image payload
            }

            $name = 'Scan page ' . $page . '.jpg';
            $path = $this->pathFor($tender, $application, 'scan-p' . $page . '-' . Str::random(6) . '.jpg');

            Storage::disk('public')->put($path, $binary);

            ApplicationReceivedDocument::create([
                'tender_id'     => $tender->id,
                'application_id'=> $application->id,
                'file_name'     => $name,
                'file_path'     => $path,
                'file_type'     => 'image/jpeg',
                'file_size'     => strlen($binary),
                'source'        => 'scan',
                'batch_id'      => $batchId,
                'page_number'   => $page,
                'remarks'       => $request->remarks,
                'uploaded_by'   => auth()->id(),
                'received_at'   => now(),
            ]);

            $saved++;
            $page++;
        }

        if ($saved === 0) {
            return $this->wantsJson($request)
                ? response()->json(['success' => false, 'message' => 'No readable scanned pages were received.'], 422)
                : back()->withErrors(['general' => 'No readable scanned pages were received.']);
        }

        $this->notifyCommittee($tender, $application, $saved, 'scan');

        $message = $saved . ' scanned page(s) recorded for '
            . ($application->vendor->company_name ?? 'this vendor')
            . ' — the Purchase Committee has been notified.';

        return $this->wantsJson($request)
            ? response()->json(['success' => true, 'message' => $message, 'saved' => $saved])
            : back()->with('success', $message);
    }

    /**
     * Records the bid value read off the physical quote in the envelope.
     *
     * For a postal tender this is the only way the price ever gets into the system — the vendor
     * never submitted it online — so it belongs on this screen, next to the documents it was
     * read from. A hybrid application already carries the bid the vendor submitted online; the
     * field is still offered there so a postal quote that disagrees can be corrected, and any
     * such change is reported to the committee with the old figure alongside the new one.
     *
     * Submitting an empty value clears the figure back to N/A, which is the only way to undo a
     * mistyped price without inventing one.
     */
    public function updateBid(Request $request, $tenderId, $applicationId)
    {
        $tender = Tender::findOrFail($tenderId);
        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $application = $this->applicationOf($tender, $applicationId);

        $validated = $request->validate([
            // 15,2 in the column, so cap the figure rather than let the insert blow up.
            'bid_amount' => 'nullable|numeric|min:0|max:9999999999999',
        ], [
            'bid_amount.numeric' => 'Enter the bid value as a number, without currency symbols or commas.',
            'bid_amount.min'     => 'A bid value cannot be negative.',
            'bid_amount.max'     => 'That bid value is larger than this system can store.',
        ]);

        $previous = $application->bid_amount === null ? null : (float) $application->bid_amount;
        $new = ($validated['bid_amount'] ?? null) === null || $validated['bid_amount'] === ''
            ? null
            : (float) $validated['bid_amount'];

        // Nothing actually changed — say so rather than notifying the committee for nothing.
        if ($previous === $new || ($previous !== null && $new !== null && abs($previous - $new) < 0.001)) {
            return back()->with('success', 'Bid value unchanged for '
                . ($application->vendor->company_name ?? 'this vendor') . '.');
        }

        $application->update([
            'bid_amount'      => $new,
            'bid_recorded_by' => $new === null ? null : auth()->id(),
            'bid_recorded_at' => $new === null ? null : now(),
        ]);

        $this->notifyCommitteeOfBid($tender, $application, $new, $previous);

        $vendor = $application->vendor->company_name ?? 'this vendor';
        $message = $new === null
            ? 'Bid value cleared for ' . $vendor . ' — the Purchase Committee has been notified.'
            : 'Bid value of ₹' . number_format($new, 2) . ' recorded for ' . $vendor
              . ' — the Purchase Committee has been notified.';

        return back()->with('success', $message);
    }

    /**
     * Removes one wrongly uploaded or mis-scanned file, together with the file on disk.
     */
    public function destroy($tenderId, $documentId)
    {
        $tender = Tender::findOrFail($tenderId);
        if ($guard = $this->guard($tender)) {
            return $guard;
        }

        $document = ApplicationReceivedDocument::where('tender_id', $tender->id)->findOrFail($documentId);
        $name = $document->file_name;
        $document->deleteWithFile();

        return back()->with('success', 'Removed "' . $name . '".');
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    /**
     * Returns a redirect/abort response when the current user or tender is not eligible, or
     * null when the action may proceed.
     *
     * Eligibility is deliberately the same shape as the rest of the opening flow: the tender
     * must take postal submissions, must already be open, and the user must either be
     * privileged (Admin / Super Admin / the tender's own owner) or a committee member whose
     * opening code was actually collected.
     */
    private function guard(Tender $tender)
    {
        $user = auth()->user();

        if (!$tender->hasPostalBids()) {
            abort(404, 'This tender does not accept postal submissions, so there is nothing to receive by post.');
        }

        if (!$tender->isBidOpen()) {
            return redirect()->route($this->backRoute(), $tender->id)->withErrors([
                'general' => 'Received documents can only be recorded once the tender has been opened with the Purchase Committee\'s codes.',
            ]);
        }

        $isPrivileged = $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || ($user->hasRole('tender-owner') && $tender->owner_id === $user->id);

        if ($isPrivileged) {
            return null;
        }

        if (!$user->hasRole('purchase-committee')) {
            abort(403, 'You are not authorized to record received documents for this tender.');
        }

        if (!$tender->isReviewUnlockedFor($user->id)) {
            return redirect()->route('purchase-committee.tenders.index')->withErrors([
                'general' => 'Your opening code was not collected for this tender, so you cannot record its received documents.',
            ]);
        }

        return null;
    }

    /**
     * Where to send a user who cannot proceed — their own role's tender page.
     */
    private function backRoute(): string
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin'))  return 'super-admin.tenders.show';
        if ($user->hasRole('admin'))        return 'admin.tenders.show';
        if ($user->hasRole('tender-owner')) return 'tender-owner.tenders.show';

        return 'purchase-committee.review.documents';
    }

    private function applicationOf(Tender $tender, $applicationId): TenderApplication
    {
        return TenderApplication::where('tender_id', $tender->id)
            ->with('vendor')
            ->findOrFail($applicationId);
    }

    /**
     * Files go under the application's own folder, so a vendor's papers never mix with another's.
     */
    private function pathFor(Tender $tender, TenderApplication $application, string $filename): string
    {
        return 'received-documents/' . $tender->id . '/' . $application->id . '/' . $filename;
    }

    private function record(Tender $tender, TenderApplication $application, $file, string $source, string $batchId, ?int $page, ?string $remarks): ApplicationReceivedDocument
    {
        $original = $file->getClientOriginalName();
        $path = $this->pathFor(
            $tender,
            $application,
            Str::slug(pathinfo($original, PATHINFO_FILENAME)) . '-' . Str::random(6) . '.' . $file->getClientOriginalExtension()
        );

        Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

        return ApplicationReceivedDocument::create([
            'tender_id'      => $tender->id,
            'application_id' => $application->id,
            'file_name'      => $original,
            'file_path'      => $path,
            'file_type'      => $file->getClientMimeType(),
            'file_size'      => $file->getSize(),
            'source'         => $source,
            'batch_id'       => $batchId,
            'page_number'    => $page,
            'remarks'        => $remarks,
            'uploaded_by'    => auth()->id(),
            'received_at'    => now(),
        ]);
    }

    /**
     * Only accepts a plain base64 image data URL, and never trusts the declared type — the
     * payload is decoded and re-checked as a real image before anything is written to disk.
     */
    private function decodeImageDataUrl(string $dataUrl): ?string
    {
        if (!preg_match('#^data:image/(jpeg|jpg|png|webp);base64,#i', $dataUrl, $m)) {
            return null;
        }

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64, true);

        if ($binary === false || strlen($binary) < 100 || strlen($binary) > 20 * 1024 * 1024) {
            return null;
        }

        $info = @getimagesizefromstring($binary);
        if ($info === false || empty($info[0]) || empty($info[1])) {
            return null;
        }

        return $binary;
    }

    /**
     * Every active committee member is told separately, each in their own login and at their
     * own email address. One member's mail failing (bad address, SMTP hiccup) must not stop
     * the others from being told, so each send is isolated and logged on failure.
     */
    private function notifyCommittee(Tender $tender, TenderApplication $application, int $count, string $source): void
    {
        $members = PurchaseCommitteeMember::active()->with('user')->get();
        $uploader = auth()->user()?->name;

        foreach ($members as $member) {
            if (!$member->user) continue;

            try {
                $member->user->notify(new ReceivedDocumentsUploadedNotification(
                    $tender, $application, $count, $source, $uploader
                ));
            } catch (\Exception $e) {
                \Log::error('ReceivedDocumentsUploadedNotification failed for user '
                    . $member->user_id . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Same per-member isolation as notifyCommittee(): each member hears about the price in
     * their own login and at their own email address, and one failing send never silences the
     * rest.
     */
    private function notifyCommitteeOfBid(Tender $tender, TenderApplication $application, ?float $new, ?float $previous): void
    {
        $members = PurchaseCommitteeMember::active()->with('user')->get();
        $recordedBy = auth()->user()?->name;

        foreach ($members as $member) {
            if (!$member->user) continue;

            try {
                $member->user->notify(new BidValueRecordedNotification(
                    $tender, $application, $new, $previous, $recordedBy
                ));
            } catch (\Exception $e) {
                \Log::error('BidValueRecordedNotification failed for user '
                    . $member->user_id . ': ' . $e->getMessage());
            }
        }
    }

    private function wantsJson(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson();
    }
}
