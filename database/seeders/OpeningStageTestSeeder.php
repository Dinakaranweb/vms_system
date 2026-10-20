<?php

namespace Database\Seeders;

use App\Models\PurchaseCommitteeMember;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\TenderReviewOtp;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\TenderDocumentsSharedNotification;
use App\Notifications\TenderOpeningOtpNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stages hybrid and postal tenders for testing the committee opening flow by hand.
 *
 * Four tenders are created, all closed so they are eligible to be opened:
 *
 *   TEST-HYB-01  hybrid  — opening initiated, every committee code issued and OUTSTANDING.
 *   TEST-POST-01 postal  — opening initiated, every committee code issued and OUTSTANDING.
 *       → Use these to test the Purchase Officer's opening screen: each member reads their
 *         code out, the officer keys them in, and only the last one opens the tender.
 *
 *   TEST-HYB-02  hybrid  — already fully opened, documents shared with the committee.
 *   TEST-POST-02 postal  — already fully opened, documents shared with the committee.
 *       → Use these to check what each mode actually shares into a member's login, without
 *         having to run the OTP collection first.
 *
 * Re-running the seeder wipes and rebuilds only its own tenders (TEST-HYB-* / TEST-POST-*),
 * so real data is never touched. The outstanding codes are printed to the console because
 * they are otherwise only visible inside each committee member's own login — which is the
 * point of the flow.
 */
class OpeningStageTestSeeder extends Seeder
{
    /** Tender numbers this seeder owns, so re-runs are idempotent. */
    private const NUMBERS = ['TEST-HYB-01', 'TEST-POST-01', 'TEST-HYB-02', 'TEST-POST-02'];

    public function run(): void
    {
        $officer = User::role('admin')->first() ?: User::role('super-admin')->first();
        if (!$officer) {
            $this->command->error('No admin/super-admin user to act as the Purchase Officer. Aborting.');
            return;
        }

        $members = PurchaseCommitteeMember::active()->ordered()->with('user')->get();
        if ($members->isEmpty()) {
            $this->command->error('No active Purchase Committee members. Add some under Super Admin → Purchase Committee first.');
            return;
        }

        $vendors = Vendor::where('status', 'approved')->take(4)->get();
        if ($vendors->count() < 2) {
            $this->command->error('Need at least 2 approved vendors to seed applications. Aborting.');
            return;
        }

        $this->cleanUp();

        $this->command->info('Purchase Officer : ' . $officer->name . ' <' . $officer->email . '>');
        $this->command->info('Committee        : ' . $members->count() . ' active member(s)');
        $this->command->info('Vendors          : ' . $vendors->count() . ' applying to each tender');
        $this->command->newLine();

        // ── Stage 1: awaiting the officer's code collection ──────────────────
        $hyb1  = $this->makeTender('TEST-HYB-01',  'hybrid', 'Hybrid Tender — Awaiting Committee Codes', $officer);
        $post1 = $this->makeTender('TEST-POST-01', 'postal', 'Postal Tender — Awaiting Committee Codes', $officer);

        $this->makeApplications($hyb1, $vendors);
        $this->makeApplications($post1, $vendors);

        $issued = [];
        $issued['TEST-HYB-01']  = $this->initiateOpening($hyb1, $officer, $members);
        $issued['TEST-POST-01'] = $this->initiateOpening($post1, $officer, $members);

        // ── Stage 2: already open, documents shared ──────────────────────────
        $hyb2  = $this->makeTender('TEST-HYB-02',  'hybrid', 'Hybrid Tender — Open, Documents Shared', $officer);
        $post2 = $this->makeTender('TEST-POST-02', 'postal', 'Postal Tender — Open, Documents Shared', $officer);

        $this->makeApplications($hyb2, $vendors);
        $this->makeApplications($post2, $vendors);

        $this->initiateOpening($hyb2, $officer, $members);
        $this->initiateOpening($post2, $officer, $members);
        $this->forceOpen($hyb2, $officer, $members);
        $this->forceOpen($post2, $officer, $members);

        $this->report($issued, $members, [$hyb1, $post1, $hyb2, $post2]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function cleanUp(): void
    {
        $ids = Tender::whereIn('tender_number', self::NUMBERS)->pluck('id');
        if ($ids->isEmpty()) return;

        // Clear the notifications these tenders produced on an earlier run, so the
        // committee's feed does not fill up with stale codes.
        foreach (DB::table('notifications')->get() as $n) {
            $data = json_decode($n->data, true) ?: [];
            if (in_array($data['tender_id'] ?? null, $ids->all(), true)) {
                DB::table('notifications')->where('id', $n->id)->delete();
            }
        }

        TenderReviewOtp::whereIn('tender_id', $ids)->delete();
        DB::table('application_review_comments')
            ->whereIn('application_id', TenderApplication::whereIn('tender_id', $ids)->pluck('id'))->delete();

        // Received documents own real files, so clear the whole folder for each tender rather
        // than only the rows — otherwise a re-run leaves orphans behind in storage.
        DB::table('application_received_documents')->whereIn('tender_id', $ids)->delete();
        foreach ($ids as $id) {
            foreach (['received-documents/' . $id, 'application-documents/' . $id, 'postal-bid-scans/' . $id] as $dir) {
                if (Storage::disk('public')->exists($dir)) {
                    Storage::disk('public')->deleteDirectory($dir);
                }
            }
        }

        TenderApplication::whereIn('tender_id', $ids)->delete();
        DB::table('category_tender')->whereIn('tender_id', $ids)->delete();
        Tender::whereIn('id', $ids)->delete();

        $this->command->warn('Removed ' . $ids->count() . ' tender(s) from a previous run of this seeder.');
    }

    private function makeTender(string $number, string $mode, string $title, User $officer): Tender
    {
        $tender = new Tender();
        $tender->forceFill([
            'tender_number'           => $number,
            'title'                   => $title,
            'description'             => 'Seeded by OpeningStageTestSeeder to exercise the Purchase Committee opening flow for a ' . $mode . ' tender.',
            'eligibility_criteria'    => 'Registered supplier with at least 2 years of comparable supply experience.',
            'technical_requirements'  => 'As per the attached technical specification.',
            'financial_requirements'  => 'Rates inclusive of delivery and applicable taxes.',
            'estimated_value'         => 850000,
            'emda_amount'             => 25000,
            'tender_type'             => $mode,
            'status'                  => 'closed',
            'published_date'          => now()->subDays(30),
            'start_date'              => now()->subDays(30),
            'submission_deadline'     => now()->subDays(2),
            'opening_date'            => now()->subDay(),
            'opening_venue'           => 'Purchase Department, Admin Block',
            'require_bid_amount'      => $mode !== 'postal',
            'require_technical_bid'   => true,
            'require_financial_bid'   => true,
            'created_by'              => $officer->id,
            'owner_id'                => $officer->id,
            'closed_reason'           => 'Submission deadline passed — seeded for opening tests.',
        ]);

        if (in_array($mode, ['postal', 'hybrid'], true)) {
            $tender->postal_address = "Purchase Department\nVinayaka Mission's Research Foundation\nSalem 636308";
            $tender->postal_receipt_deadline = now()->subDays(2);
        }

        $tender->save();

        $this->command->line('  created ' . $number . ' (' . $tender->getSubmissionModeLabel() . ')');

        return $tender->fresh();
    }

    /**
     * One application per vendor. What gets attached depends on the mode, and the LAST
     * application of a postal/hybrid tender is deliberately left without its scan so the
     * "awaiting postal entry" state is testable too.
     */
    private function makeApplications(Tender $tender, $vendors): void
    {
        $last = $vendors->count() - 1;

        foreach ($vendors->values() as $i => $vendor) {
            $data = [
                'tender_id'    => $tender->id,
                'vendor_id'    => $vendor->id,
                'status'       => 'submitted',
                'submitted_at' => now()->subDays(5 - min($i, 4)),
                'emda_paid_amount'      => 25000,
                'emda_reference_number' => 'EMD-' . $tender->tender_number . '-' . ($i + 1),
            ];

            if ($tender->hasOnlineBids()) {
                $data['bid_amount']              = 720000 + ($i * 45000);
                $data['technical_proposal']      = 'Technical approach submitted online by ' . $vendor->company_name . '.';
                $data['financial_proposal']      = 'Commercial terms submitted online by ' . $vendor->company_name . '.';
                $data['technical_proposal_file'] = $this->stubFile($tender, $vendor, 'technical-proposal');
                $data['financial_proposal_file'] = $this->stubFile($tender, $vendor, 'financial-proposal');
                $data['documents']               = [[
                    'name' => 'Company Profile.pdf',
                    'path' => $this->stubFile($tender, $vendor, 'company-profile'),
                    'size' => 2048,
                ], [
                    'name' => 'GST Certificate.pdf',
                    'path' => $this->stubFile($tender, $vendor, 'gst-certificate'),
                    'size' => 1024,
                ]];
            } else {
                // Postal-only: nothing arrives through the portal, and the bid amount is
                // keyed in later from the physical quote.
                $data['bid_amount'] = null;
            }

            // The physical submission's scan — withheld on the last vendor on purpose.
            if ($tender->hasPostalBids() && $i !== $last) {
                $data['postal_quote_scan'] = $this->stubFile($tender, $vendor, 'postal-quote-scan', 'postal-bid-scans');
                if ($tender->isPostal()) {
                    $data['bid_amount'] = 735000 + ($i * 38000);
                }
            }

            TenderApplication::create($data);
        }

        $withheld = $tender->hasPostalBids() ? ' (last vendor left awaiting its postal scan)' : '';
        $this->command->line('    + ' . $vendors->count() . ' applications' . $withheld);
    }

    /**
     * Writes a small real PDF so the links on the committee's documents screen actually open.
     */
    private function stubFile(Tender $tender, Vendor $vendor, string $label, string $dir = 'application-documents'): string
    {
        $path = $dir . '/' . $tender->id . '/' . $vendor->id . '-' . $label . '.pdf';

        $title = $label . ' — ' . $vendor->company_name;
        $body  = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
               . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
               . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 400 200]/Contents 4 0 R"
               . "/Resources<</Font<</F1 5 0 R>>>>>>endobj\n"
               . "4 0 obj<</Length 90>>stream\nBT /F1 11 Tf 20 150 Td (" . $this->pdfEscape($title) . ") Tj ET\n"
               . "BT /F1 9 Tf 20 120 Td (" . $this->pdfEscape($tender->tender_number) . ") Tj ET\nendstream endobj\n"
               . "5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\n"
               . "trailer<</Root 1 0 R>>\n%%EOF";

        Storage::disk('public')->put($path, $body);

        return $path;
    }

    private function pdfEscape(string $s): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    }

    /**
     * Runs the real opening initiation so the codes, notifications and audit columns are
     * exactly what the application itself would produce. Returns the plain codes for the
     * console summary.
     */
    private function initiateOpening(Tender $tender, User $officer, $members): array
    {
        $tender->update(['bid_opened_by' => $officer->id]);

        $codes = [];

        foreach ($members as $member) {
            $code = (string) random_int(100000, 999999);
            $expiresAt = now()->addHours(12); // generous window, so a manual test does not race the clock

            $otp = TenderReviewOtp::updateOrCreate(
                ['tender_id' => $tender->id, 'committee_member_id' => $member->user_id],
                [
                    'otp_code'         => $code,
                    'otp_expires_at'   => $expiresAt,
                    'otp_sent_at'      => now(),
                    'otp_verified_at'  => null,
                    'otp_collected_by' => null,
                ]
            );

            $member->user->notify(new TenderOpeningOtpNotification($tender, $code, $expiresAt, $officer->name));

            $codes[$member->user->name] = $code;
            unset($otp);
        }

        return $codes;
    }

    /**
     * Completes an opening as if the officer had collected every code, so the
     * documents-shared state can be inspected directly.
     */
    private function forceOpen(Tender $tender, User $officer, $members): void
    {
        TenderReviewOtp::where('tender_id', $tender->id)->update([
            'otp_verified_at'  => now(),
            'otp_collected_by' => $officer->id,
        ]);

        $tender->update([
            'bid_opening_at'   => now(),
            'review_opened_at' => now(),
            'review_opened_by' => $officer->id,
        ]);

        $count = TenderApplication::where('tender_id', $tender->id)->count();
        foreach ($members as $member) {
            $member->user->notify(new TenderDocumentsSharedNotification($tender->fresh(), $count));
        }

        $this->command->line('    → opened; documents shared with the committee');
    }

    private function report(array $issued, $members, array $tenders): void
    {
        $this->command->newLine();
        $this->command->info(str_repeat('=', 78));
        $this->command->info('OUTSTANDING OPENING CODES — for testing the officer\'s collection screen');
        $this->command->info(str_repeat('=', 78));
        $this->command->warn('In the real flow each member reads their code off their own notifications');
        $this->command->warn('page and tells the officer. They are printed here only to save you logging');
        $this->command->warn('in as three people. Codes are valid for 12 hours.');

        foreach ($issued as $number => $codes) {
            $this->command->newLine();
            $this->command->info('  ' . $number);
            foreach ($codes as $name => $code) {
                $this->command->line('    ' . str_pad($name, 22) . ' ' . $code);
            }
        }

        $this->command->newLine();
        $this->command->info(str_repeat('-', 78));
        $this->command->info('WHERE TO GO');
        $this->command->info(str_repeat('-', 78));

        foreach ($tenders as $t) {
            $t->refresh();
            $state = $t->isBidOpen()
                ? 'OPEN — documents shared'
                : $t->openingOtpsVerifiedCount() . '/' . $t->openingOtpsIssuedCount() . ' codes collected';
            $this->command->line('  ' . str_pad($t->tender_number, 14) . str_pad($t->getSubmissionModeLabel(), 26) . $state);
            $this->command->line('    officer : /purchase-committee/tenders/' . $t->id . '/opening-codes');
            if ($t->isBidOpen()) {
                $this->command->line('    member  : /purchase-committee/tenders/' . $t->id . '/documents');
            }
        }

        $this->command->newLine();
        $this->command->line('  Committee members (their codes appear at /purchase-committee/notifications):');
        foreach ($members as $m) {
            $this->command->line('    ' . str_pad($m->user->name, 22) . $m->user->email);
        }
        $this->command->newLine();
    }
}
