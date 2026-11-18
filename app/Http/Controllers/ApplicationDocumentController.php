<?php

namespace App\Http\Controllers;

use App\Models\TenderApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serves the files a vendor submitted with an application, and nothing else.
 *
 * Until this existed, every submitted document was linked with Storage::url(), a direct
 * URL to the storage bucket. Hiding the link on a sealed page therefore did not seal the
 * file: anyone who had the URL - or who had seen the page before the seal was added - could
 * still fetch a competitor's bid straight from the bucket, with the application never
 * consulted. Routing the files through here makes the seal real, because the rules below run
 * on every single fetch.
 *
 * The companion check is in TenderApplication::ownedFilePaths(): a request can only ever be
 * served a file the named application actually owns, so this cannot be turned into a reader
 * for arbitrary objects in the bucket.
 */
class ApplicationDocumentController extends Controller
{
    public function show(Request $request, $applicationId)
    {
        $application = TenderApplication::with([
            'tender', 'eligibilityAnswers', 'receivedDocuments',
        ])->findOrFail($applicationId);

        $path = (string) $request->query('path', '');

        // Never serve a path this application does not own, whatever the caller's role.
        if ($path === '' || ! in_array($path, $application->ownedFilePaths(), true)) {
            abort(404);
        }

        if (! $this->mayRead($application)) {
            abort(403, $application->tender && $application->tender->areSubmissionsSealed()
                ? 'This document is sealed until the tender is opened by the Purchase Committee.'
                : 'You are not authorized to view this document.');
        }

        $disk = Storage::disk(config('filesystems.default'));

        if (! $disk->exists($path)) {
            abort(404);
        }

        return $disk->response(
            $path,
            basename($path),
            [
                // A sealed bid must never be cached by a shared proxy, and must never be
                // indexed if a link ever escapes.
                'Cache-Control' => 'private, no-store, max-age=0, must-revalidate',
                'X-Robots-Tag'  => 'noindex, nofollow',
            ],
            $request->boolean('download') ? 'attachment' : 'inline'
        );
    }

    /**
     * Who may read a submitted file.
     *
     * The vendor who submitted it can always reopen their own upload - they have the file
     * already, and locking them out of their own paperwork protects nobody. Everyone else is
     * staff, and staff get nothing at all while the tender is sealed, regardless of rank:
     * that is the whole point of a sealed bid. Once the committee's codes have opened the
     * tender, the usual role rules apply.
     */
    private function mayRead(TenderApplication $application): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->vendor_id && (int) $user->vendor_id === (int) $application->vendor_id) {
            return true;
        }

        $tender = $application->tender;

        if (! $tender || $tender->areSubmissionsSealed()) {
            return false;
        }

        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ($user->hasRole('tender-owner') && (int) $tender->owner_id === (int) $user->id) {
            return true;
        }

        // A Purchase Committee member additionally needs their own opening code verified.
        if ($user->hasRole('purchase-committee') && $tender->isReviewUnlockedFor($user->id)) {
            return true;
        }

        // A tender committee member needs an active seat on this particular tender.
        if ($user->hasRole('committee-member')
            && $user->committeeTenders()
                ->where('tenders.id', $tender->id)
                ->wherePivot('is_active', true)
                ->exists()) {
            return true;
        }

        return false;
    }
}
