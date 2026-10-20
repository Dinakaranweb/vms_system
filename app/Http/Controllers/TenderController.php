<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderDocument;
use App\Models\EligibilityQuestion;
use App\Models\Category;
use App\Models\TenderView;
use App\Mail\TenderPublishedMail;
use App\Mail\TenderClosedMail;
use App\Notifications\TenderOpeningOtpNotification;
use App\Models\TenderApplication;
use App\Models\TenderReviewOtp;
use App\Models\PurchaseCommitteeMember;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TenderController extends Controller
{
    /**
     * Display a listing of tenders.
     */
    public function index(Request $request)
{
    $user = auth()->user();
    $search = trim((string) $request->query('search', ''));
    $statusFilter = $request->query('status', '');

    $applySearch = function ($query) use ($search, $statusFilter) {
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('tender_number', 'like', "%{$search}%");
            });
        }
        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }
        return $query;
    };

    // Get accessible tenders based on user role
    if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
        $tenders = $applySearch(Tender::with(['creator', 'owner', 'categories']))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }
    else if ($user->hasRole('tender-owner')) {
        $tenders = $applySearch(Tender::where('owner_id', $user->id)->with(['creator', 'owner', 'categories']))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }
    else if ($user->isCommitteeMemberForAnyTender()) {
        $tenderIds = $user->committeeTenders()
            ->wherePivot('is_active', true)
            ->pluck('tender_id');
        $tenders = $applySearch(Tender::whereIn('id', $tenderIds)->with(['creator', 'owner', 'categories']))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }
    else {
        // Regular users (vendors) see only published tenders
        $tenders = $applySearch(Tender::where('status', 'published'))
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
    }

    $stats = [
        'total' => Tender::count(),
        'published' => Tender::where('status', 'published')->count(),
        'draft' => Tender::where('status', 'draft')->count(),
        'closed' => Tender::where('status', 'closed')->count(),
    ];
    
    return view('admin.tenders.index', compact('tenders', 'stats'));
}

    /**
     * Show the form for creating a new tender.
     */
    public function create()
    {
        $user = auth()->user();
        $categories = Category::active()->orderBy('name')->get();

        if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
            return view('admin.tenders.create', compact('categories'));
        }

        if ($user->hasRole('tender-owner')) {
            return view('tender-owner.tenders.create', compact('categories'));
        }

        abort(403, 'You do not have permission to create tenders.');
    }

    /**
     * Store a newly created tender.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'eligibility_criteria' => 'nullable|string',
            'estimated_value' => 'nullable|numeric',
            'start_date' => 'required|date',
            'submission_deadline' => 'required|date',
            'opening_date' => 'nullable|date',
            'tender_type' => 'required|in:online,postal,hybrid',
            'postal_address' => 'required_if:tender_type,postal|nullable|string',
            'documents.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'questions' => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            $tender = Tender::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'eligibility_criteria' => $validated['eligibility_criteria'] ?? '',
                'submission_deadline' => $validated['submission_deadline'],
                'start_date' => $validated['start_date'],
                'opening_date' => $validated['opening_date'] ?? null,
                'tender_type' => $validated['tender_type'],
                'is_private'               => $request->boolean('is_private'),
                'postal_address'           => $validated['postal_address'] ?? null,
                'postal_receipt_deadline'  => $request->filled('postal_receipt_deadline') ? $request->postal_receipt_deadline : null,
                'status' => 'draft',
                'created_by' => auth()->id(),
                'owner_id' => auth()->id(),
                'require_bid_amount'          => $request->boolean('require_bid_amount'),
                'require_technical_bid'       => $request->boolean('require_technical_bid'),
                'require_financial_bid'       => $request->boolean('require_financial_bid'),
                'require_contract_signing'    => $request->boolean('require_contract_signing'),
                'require_emd'                 => $request->boolean('require_emd'),
                'require_estimated_value'     => $request->boolean('require_estimated_value'),
                'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
                'emda_amount'                 => $request->boolean('require_emd') && $request->filled('emda_amount') ? $request->emda_amount : null,
                'estimated_value'             => $request->boolean('require_estimated_value') && $request->filled('estimated_value') ? $request->estimated_value : null,
                'notify_specific_vendors'     => $request->filled('notify_specific_vendors')
                    ? array_values(array_filter(array_map('intval', explode(',', $request->notify_specific_vendors))))
                    : null,
                'auto_publish'            => $request->boolean('auto_publish'),
                'show_on_university'      => $request->boolean('auto_publish') ? $request->boolean('show_on_university') : false,
                'university_description'  => $request->boolean('auto_publish') ? ($request->input('university_description') ?: null) : null,
            ]);

            // Save eligibility questions
            if ($request->has('questions') && is_array($request->questions)) {
                foreach ($request->questions as $questionData) {
                    if (!empty($questionData['question'])) {
                        $optionsData = null;
                        if (!empty($questionData['options']) && is_array($questionData['options'])) {
                            $optionsData = array_values(array_filter(
                                array_map(function ($opt) {
                                    $label = trim($opt['label'] ?? '');
                                    if ($label === '') return null;
                                    return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible'] == '1'];
                                }, $questionData['options'])
                            ));
                            if (empty($optionsData)) $optionsData = null;
                        }
                        $conditionData = null;
                        if (!empty($questionData['eligibility_condition']['operator'])) {
                            $conditionData = [
                                'operator' => $questionData['eligibility_condition']['operator'],
                                'value'    => $questionData['eligibility_condition']['value'] ?? null,
                                'value2'   => $questionData['eligibility_condition']['value2'] ?? null,
                            ];
                        }
                        EligibilityQuestion::create([
                            'tender_id'              => $tender->id,
                            'question'               => $questionData['question'],
                            'description'            => $questionData['description'] ?? null,
                            'field_type'             => $questionData['field_type'] ?? 'text',
                            'options'                => $optionsData,
                            'eligible_options'       => null,
                            'eligibility_condition'  => $conditionData,
                            'is_required'            => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                            'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                            'max_size'               => $questionData['max_size'] ?? 5120,
                            'order'                  => $tender->eligibilityQuestions()->count() + 1,
                        ]);
                    }
                }
            }

            // Handle documents
            if ($request->hasFile('documents')) {
                $docCategories = $request->input('document_categories', []);
                $mandatories = $request->input('is_mandatory', []);
                foreach ($request->file('documents') as $index => $file) {
                    if ($file && $file->isValid()) {
                        $path = $file->store('tenders/' . $tender->id, 'public');
                        TenderDocument::create([
                            'tender_id' => $tender->id,
                            'title' => $file->getClientOriginalName(),
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'file_extension' => $file->getClientOriginalExtension(),
                            'document_category' => $docCategories[$index] ?? 'bidding_document',
                            'is_mandatory' => isset($mandatories[$index]),
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }

            // Sync categories
            if ($request->filled('category_ids')) {
                $tender->categories()->sync($request->input('category_ids'));
            }

            DB::commit();

            if ($user->hasRole('tender-owner')) {
                return redirect()->route('tender-owner.dashboard')
                    ->with('success', 'Tender created successfully!');
            }

            return redirect()->route('admin.tenders')
                ->with('success', 'Tender created successfully!');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to create tender: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified tender.
     */
    public function show($id)
{
    $tender = Tender::with(['creator', 'documents', 'eligibilityQuestions'])->findOrFail($id);
    
    // Check access
    if (!auth()->user()->canAccessTender($id)) {
        abort(403, 'You do not have permission to view this tender.');
    }
    
    $stats = [
        'total_applications' => $tender->applications()->count(),
        'submitted_applications' => $tender->applications()->where('status', 'submitted')->count(),
        'shortlisted_applications' => $tender->applications()->where('status', 'shortlisted')->count(),
        'awarded_applications' => $tender->applications()->where('status', 'awarded')->count(),
    ];
    
    // The "Quick View" modal on the tenders list page fetches this same URL and injects
    // the raw fragment into a modal div — direct navigation should get the full page
    // with the dashboard layout instead (see admin/tenders/index.blade.php's fetch call).
    if (request()->header('X-Requested-With') === 'XMLHttpRequest') {
        return view('admin.tenders.show', compact('tender', 'stats'));
    }

    return view('admin.tenders.show-full', compact('tender', 'stats'));
}

    /**
     * Show the form for editing the specified tender.
     */
    public function edit($id)
    {
        $tender = Tender::with(['documents', 'eligibilityQuestions', 'categories'])->findOrFail($id);
        $categories = Category::active()->orderBy('name')->get();
        $selectedCategoryIds = $tender->categories->pluck('id')->toArray();
        return view('admin.tenders.edit', compact('tender', 'categories', 'selectedCategoryIds'));
    }

    /**
     * Update the specified tender.
     */
    public function update(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);
        
        $validated = $request->validate([
            'title'                => 'required|string|max:255',
            'description'          => 'required|string',
            'eligibility_criteria' => 'nullable|string',
            'estimated_value'      => 'nullable|numeric|min:0',
            'emda_amount'          => 'nullable|numeric|min:0',
            'start_date'           => 'required|date',
            'submission_deadline'  => 'required|date',
            'opening_date'         => 'nullable|date',
            'tender_type'          => 'required|in:online,postal,hybrid',
            'postal_address'       => 'required_if:tender_type,postal|nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $autoPublishData = [];
            if ($tender->isDraft()) {
                $autoPublishData['auto_publish'] = $request->boolean('auto_publish');
                if ($request->boolean('auto_publish')) {
                    $autoPublishData['show_on_university'] = $request->boolean('show_on_university');
                    $autoPublishData['university_description'] = $request->input('university_description') ?: null;
                }
            }

            $tender->update(array_merge($validated, [
                'start_date'                  => $request->filled('start_date') ? $request->start_date : null,
                'opening_date'                => $request->filled('opening_date') ? $request->opening_date : null,
                'eligibility_criteria'        => $validated['eligibility_criteria'] ?? '',
                'is_private'                  => $request->boolean('is_private'),
                'postal_receipt_deadline'     => $request->filled('postal_receipt_deadline') ? $request->postal_receipt_deadline : null,
                'require_bid_amount'          => $request->boolean('require_bid_amount'),
                'require_technical_bid'       => $request->boolean('require_technical_bid'),
                'require_financial_bid'       => $request->boolean('require_financial_bid'),
                'require_contract_signing'    => $request->boolean('require_contract_signing'),
                'require_emd'                 => $request->boolean('require_emd'),
                'require_estimated_value'     => $request->boolean('require_estimated_value'),
                'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
                'emda_amount'                 => $request->boolean('require_emd') && $request->filled('emda_amount') ? $request->emda_amount : null,
                'estimated_value'             => $request->boolean('require_estimated_value') && $request->filled('estimated_value') ? $request->estimated_value : null,
            ] + $autoPublishData));

            // Handle new document uploads
            if ($request->hasFile('new_documents')) {
                foreach ($request->file('new_documents') as $index => $file) {
                    if ($file->isValid()) {
                        $path = $file->store('tenders/' . $tender->id, 'public');
                        TenderDocument::create([
                            'tender_id' => $tender->id,
                            'title' => $file->getClientOriginalName(),
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $path,
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'file_extension' => $file->getClientOriginalExtension(),
                            'document_category' => $request->document_categories[$index] ?? 'bidding_document',
                            'is_mandatory' => isset($request->is_mandatory[$index]),
                            'uploaded_by' => auth()->id(),
                        ]);
                    }
                }
            }
            
            // Handle new eligibility questions
            if ($request->has('new_questions')) {
                foreach ($request->new_questions as $questionData) {
                    if (!empty($questionData['question'])) {
                        $optionsData = null;
                        if (!empty($questionData['options']) && is_array($questionData['options'])) {
                            $optionsData = array_values(array_filter(
                                array_map(function ($opt) {
                                    $label = trim($opt['label'] ?? '');
                                    if ($label === '') return null;
                                    return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible'] == '1'];
                                }, $questionData['options'])
                            ));
                            if (empty($optionsData)) $optionsData = null;
                        }
                        $conditionData = null;
                        if (!empty($questionData['eligibility_condition']['operator'])) {
                            $conditionData = [
                                'operator' => $questionData['eligibility_condition']['operator'],
                                'value'    => $questionData['eligibility_condition']['value'] ?? null,
                                'value2'   => $questionData['eligibility_condition']['value2'] ?? null,
                            ];
                        }
                        EligibilityQuestion::create([
                            'tender_id'              => $tender->id,
                            'question'               => $questionData['question'],
                            'description'            => $questionData['description'] ?? null,
                            'field_type'             => $questionData['field_type'] ?? 'text',
                            'options'                => $optionsData,
                            'eligible_options'       => null,
                            'eligibility_condition'  => $conditionData,
                            'is_required'            => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                            'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                            'max_size'               => $questionData['max_size'] ?? 5120,
                            'order'                  => $tender->eligibilityQuestions()->count() + 1,
                        ]);
                    }
                }
            }

            // Handle removed eligibility questions
            if ($request->has('eligibility_questions_removed')) {
                $removedIds = json_decode($request->eligibility_questions_removed, true);
                if (!empty($removedIds)) {
                    EligibilityQuestion::whereIn('id', $removedIds)->delete();
                }
            }

            // Sync categories
            $tender->categories()->sync($request->input('category_ids', []));

            DB::commit();

            return redirect()->route('admin.tenders')
                ->with('success', 'Tender updated successfully!');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Failed to update tender: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified tender.
     */
    public function destroy($id)
    {
        try {
            $tender = Tender::findOrFail($id);
            
            foreach ($tender->documents as $document) {
                if (Storage::disk('public')->exists($document->file_path)) {
                    Storage::disk('public')->delete($document->file_path);
                }
                $document->delete();
            }
            
            $tender->delete();
            
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tender deleted successfully!'
                ]);
            }
            
            return redirect()->route('admin.tenders')
                ->with('success', 'Tender deleted successfully!');
                
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete tender'
                ], 500);
            }
            return back()->withErrors(['error' => 'Failed to delete tender']);
        }
    }

    /**
     * Publish tender.
     */
    public function publish(Request $request, $id)
    {
        try {
            $tender = Tender::with('categories.vendors')->findOrFail($id);
            $showOnUniversity = $request->boolean('show_on_university');
            $tender->update([
                'status'                 => 'published',
                'published_date'         => now(),
                'approved_by'            => auth()->id(),
                'approved_at'            => now(),
                'show_on_university'     => $showOnUniversity,
                'university_description' => $showOnUniversity ? ($request->university_description ?? null) : null,
            ]);

            $this->notifyVendorsOnPublish($tender);

            return response()->json([
                'success' => true,
                'message' => 'Tender published successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish tender: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update university website display settings for a published tender.
     */
    public function updateUniversityDisplay(Request $request, $id)
    {
        try {
            $tender = Tender::findOrFail($id);

            if ($tender->status !== 'published') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only published tenders can be shown on the university website.'
                ], 400);
            }

            $showOnUniversity = $request->boolean('show_on_university');
            $tender->update([
                'show_on_university'     => $showOnUniversity,
                'university_description' => $showOnUniversity ? ($request->university_description ?? null) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => $showOnUniversity
                    ? 'Tender will now be shown on the university website.'
                    : 'Tender removed from the university website.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tender view analytics for admin / super-admin.
     */
    public function analytics($id)
    {
        $tender = Tender::findOrFail($id);
        $rp     = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin';

        $views = TenderView::where('tender_id', $id)
            ->with('user')
            ->orderBy('viewed_at', 'desc')
            ->paginate(50);

        $stats = [
            'total_hits'   => TenderView::where('tender_id', $id)->count(),
            'unique_ips'   => TenderView::where('tender_id', $id)->distinct('ip_address')->count('ip_address'),
            'logged_users' => TenderView::where('tender_id', $id)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
            'today'        => TenderView::where('tender_id', $id)->whereDate('viewed_at', today())->count(),
        ];

        // Build a 30-day daily view series (fill missing days with 0)
        $rawDaily = TenderView::where('tender_id', $id)
            ->where('viewed_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date   = now()->subDays($i)->format('Y-m-d');
            $days[] = ['date' => $date, 'count' => $rawDaily->get($date)?->count ?? 0];
        }

        // Top IPs (by hit count)
        $topIps = TenderView::where('tender_id', $id)
            ->selectRaw('ip_address, COUNT(*) as hits')
            ->groupBy('ip_address')
            ->orderByDesc('hits')
            ->limit(10)
            ->get();

        return view('admin.tenders.analytics', compact('tender', 'views', 'stats', 'days', 'topIps', 'rp'));
    }

    /**
     * Close tender.
     */
    public function close(Request $request, $id)
    {
        try {
            $tender = Tender::findOrFail($id);
            $reason = $request->input('reason', '');
            $tender->update(['status' => 'closed', 'closed_reason' => $reason]);

            // Notify vendors who applied
            if ($tender->email_notifications_enabled ?? false) {
                $vendorIds = TenderApplication::where('tender_id', $tender->id)
                    ->whereNotNull('vendor_id')
                    ->pluck('vendor_id')
                    ->unique();
                $emails = Vendor::whereIn('id', $vendorIds)->pluck('email');
                foreach ($emails as $email) {
                    try {
                        Mail::to($email)->send(new TenderClosedMail($tender, $reason));
                    } catch (\Exception $mailEx) {
                        \Log::error('TenderClosedMail failed to ' . $email . ': ' . $mailEx->getMessage());
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Tender closed and vendors notified!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close tender: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initiate opening a closed tender — a decision the Purchase Officer, Tender Owner, or a
     * Purchase Committee member makes on their own schedule, never automatically, and never
     * before the tender has actually closed.
     *
     * This does not open the tender. It issues one one-time code per active Purchase Committee
     * member, delivered to that member's own login (notification bell) and inbox, and then
     * hands the initiating officer the opening screen where all of those codes get keyed in
     * together. Each member reads their code out to the officer in person; the tender opens —
     * and the documents reach the committee's logins — only once every code has been entered.
     * See TenderReviewController::openingOtpPanel() and verifyOpeningOtps().
     *
     * Idempotent: clicking it again while codes are outstanding re-issues fresh codes to
     * everyone not yet verified, acting as a "resend to all" action. Members whose code the
     * officer has already entered stay verified and are not disturbed.
     */
    public function openBidding($id)
    {
        $tender = Tender::findOrFail($id);
        $user = auth()->user();

        $isAuthorized = $user->hasRole('super-admin')
            || $user->hasRole('admin')
            || $user->hasRole('purchase-committee')
            || ($user->hasRole('tender-owner') && $tender->owner_id === $user->id);

        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to open bids for this tender.'], 403);
        }

        if (!$tender->canBeOpened()) {
            $reason = $tender->isBidOpen() ? 'Bids for this tender have already been opened.' : 'This tender must be closed before bids can be opened.';
            return response()->json(['success' => false, 'message' => $reason], 400);
        }

        $members = PurchaseCommitteeMember::active()->ordered()->get();
        if ($members->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No active Purchase Committee members are configured. Add at least one before a tender can be opened.'], 400);
        }

        $issued = 0;

        DB::transaction(function () use ($tender, $user, $members, &$issued) {
            $tender->update(['bid_opened_by' => $user->id]);

            foreach ($members as $member) {
                $reviewOtp = TenderReviewOtp::firstOrCreate(
                    ['tender_id' => $tender->id, 'committee_member_id' => $member->user_id]
                );

                // Leave members the officer has already keyed in alone — re-issuing would throw
                // away a code that was already read out and accepted.
                if ($reviewOtp->isVerified()) {
                    continue;
                }

                $this->issueOpeningOtp($reviewOtp, $user->name);
                $issued++;
            }
        });

        return response()->json([
            'success'  => true,
            'redirect' => route('purchase-committee.review.opening-codes', $tender->id),
            'message'  => $issued . ' of ' . $members->count() . ' Purchase Committee member(s) have been sent an opening code in their login. Collect each code from them in person and enter them together on the opening screen.',
        ]);
    }

    /**
     * Generates a member's code and pushes it to that member's own notifications (and inbox).
     * The code is deliberately never surfaced to the officer here — the officer only learns it
     * when the member reads it out to them.
     */
    private function issueOpeningOtp(TenderReviewOtp $reviewOtp, ?string $officerName = null): void
    {
        $code = (string) random_int(100000, 999999);
        $expiresAt = now()->addMinutes(30);

        $reviewOtp->update([
            'otp_code'         => $code,
            'otp_expires_at'   => $expiresAt,
            'otp_sent_at'      => now(),
            'otp_verified_at'  => null,
            'otp_collected_by' => null,
        ]);

        try {
            $reviewOtp->committeeMember->notify(
                new TenderOpeningOtpNotification($reviewOtp->tender, $code, $expiresAt, $officerName)
            );
        } catch (\Exception $e) {
            \Log::error('TenderOpeningOtpNotification failed for user ' . $reviewOtp->committee_member_id . ': ' . $e->getMessage());
        }
    }

    /**
     * Duplicate tender.
     */
    public function duplicate($id)
    {
        try {
            $originalTender = Tender::findOrFail($id);
            
            $newTender = $originalTender->replicate();
            $newTender->status = 'draft';
            $newTender->tender_number = null;
            $newTender->published_date = null;
            $newTender->approved_by = null;
            $newTender->approved_at = null;
            $newTender->created_at = now();
            $newTender->updated_at = now();
            $newTender->save();
            
            // Duplicate documents
            foreach ($originalTender->documents as $doc) {
                $newDoc = $doc->replicate();
                $newDoc->tender_id = $newTender->id;
                $newDoc->save();
            }
            
            // Duplicate eligibility questions
            foreach ($originalTender->eligibilityQuestions as $question) {
                $newQuestion = $question->replicate();
                $newQuestion->tender_id = $newTender->id;
                $newQuestion->save();
            }
            
            return redirect()->route('admin.tenders.edit', $newTender->id)
                ->with('success', 'Tender duplicated successfully!');
                
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to duplicate tender: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete document from tender.
     */
    public function deleteDocument($id)
    {
        try {
            $document = TenderDocument::findOrFail($id);
            $tenderId = $document->tender_id;
            
            Storage::disk('public')->delete($document->file_path);
            $document->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update document order.
     */
    public function updateDocumentOrder(Request $request, $id)
    {
        try {
            $document = TenderDocument::findOrFail($id);
            $direction = $request->direction;
            
            $currentOrder = $document->order ?? 0;
            
            if ($direction === 'up') {
                $newOrder = $currentOrder - 1;
                $swapDoc = TenderDocument::where('tender_id', $document->tender_id)
                    ->where('order', $newOrder)
                    ->first();
                if ($swapDoc) {
                    $swapDoc->update(['order' => $currentOrder]);
                }
                $document->update(['order' => $newOrder]);
            } elseif ($direction === 'down') {
                $newOrder = $currentOrder + 1;
                $swapDoc = TenderDocument::where('tender_id', $document->tender_id)
                    ->where('order', $newOrder)
                    ->first();
                if ($swapDoc) {
                    $swapDoc->update(['order' => $currentOrder]);
                }
                $document->update(['order' => $newOrder]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Document order updated!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the dedicated "Define Eligibility Criteria" screen for a tender.
     */
    public function eligibilityQuestions($id)
    {
        $tender = Tender::with('eligibilityQuestions')->findOrFail($id);

        $existingQuestionsForJs = $tender->eligibilityQuestions->map(function ($q) {
            return [
                'question'               => $q->question,
                'description'            => $q->description,
                'field_type'             => $q->field_type,
                'options'                => $q->options,
                'eligibility_condition'  => $q->eligibility_condition,
                'is_required'            => (bool) $q->is_required,
                'determines_eligibility' => (bool) $q->determines_eligibility,
                'max_size'               => $q->max_size,
            ];
        })->values();

        return view('admin.tenders.eligibility', compact('tender', 'existingQuestionsForJs'));
    }

    /**
     * Save the full set of eligibility questions from the dedicated screen.
     * Full replace: this page represents "the" eligibility criteria for the tender,
     * so existing questions are cleared and re-created from the submitted set.
     */
    public function storeEligibilityQuestions(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);

        $request->validate([
            'questions'             => 'nullable|array',
            'questions.*.question'  => 'required_with:questions|string',
        ]);

        DB::beginTransaction();
        try {
            $tender->eligibilityQuestions()->delete();

            if ($request->has('questions') && is_array($request->questions)) {
                foreach ($request->questions as $questionData) {
                    if (empty($questionData['question'])) {
                        continue;
                    }

                    $optionsData = null;
                    if (!empty($questionData['options']) && is_array($questionData['options'])) {
                        $optionsData = array_values(array_filter(
                            array_map(function ($opt) {
                                $label = trim($opt['label'] ?? '');
                                if ($label === '') return null;
                                return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible'] == '1'];
                            }, $questionData['options'])
                        ));
                        if (empty($optionsData)) $optionsData = null;
                    }

                    $conditionData = null;
                    if (!empty($questionData['eligibility_condition']['operator'])) {
                        $conditionData = [
                            'operator' => $questionData['eligibility_condition']['operator'],
                            'value'    => $questionData['eligibility_condition']['value'] ?? null,
                            'value2'   => $questionData['eligibility_condition']['value2'] ?? null,
                        ];
                    }

                    EligibilityQuestion::create([
                        'tender_id'              => $tender->id,
                        'question'               => $questionData['question'],
                        'description'            => $questionData['description'] ?? null,
                        'field_type'             => $questionData['field_type'] ?? 'text',
                        'options'                => $optionsData,
                        'eligible_options'       => null,
                        'eligibility_condition'  => $conditionData,
                        'is_required'            => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                        'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                        'max_size'               => $questionData['max_size'] ?? 5120,
                        'order'                  => $tender->eligibilityQuestions()->count() + 1,
                    ]);
                }
            }

            DB::commit();

            $rp = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin';
            return redirect()->route($rp . '.tenders.show', $tender->id)
                ->with('success', 'Eligibility questions updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Failed to save eligibility questions: ' . $e->getMessage()]);
        }
    }

    /**
     * Get eligibility question for editing.
     */
    public function getEligibilityQuestion($id)
    {
        try {
            $question = EligibilityQuestion::findOrFail($id);
            return response()->json([
                'success' => true,
                'question' => $question
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update eligibility question.
     */
    public function updateEligibilityQuestion(Request $request, $id)
    {
        try {
            $question = EligibilityQuestion::findOrFail($id);

            $optionsData = null;
            if ($request->options && is_array($request->options)) {
                $optionsData = array_values(array_filter(
                    array_map(function ($opt) {
                        $label = trim($opt['label'] ?? (is_string($opt) ? $opt : ''));
                        if ($label === '') return null;
                        return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible']];
                    }, $request->options)
                ));
                if (empty($optionsData)) $optionsData = null;
            }

            $conditionData = null;
            if ($request->eligibility_condition && !empty($request->eligibility_condition['operator'])) {
                $conditionData = [
                    'operator' => $request->eligibility_condition['operator'],
                    'value'    => $request->eligibility_condition['value'] ?? null,
                    'value2'   => $request->eligibility_condition['value2'] ?? null,
                ];
            }

            $question->update([
                'question'               => $request->question,
                'description'            => $request->description,
                'field_type'             => $request->field_type,
                'is_required'            => $request->is_required,
                'determines_eligibility' => $request->determines_eligibility,
                'options'                => $optionsData,
                'eligible_options'       => null,
                'eligibility_condition'  => $conditionData,
                'max_size'               => $request->max_size,
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully!'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Delete eligibility question.
     */
    public function deleteEligibilityQuestion($id)
    {
        try {
            $question = EligibilityQuestion::findOrFail($id);
            $question->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Question deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete question'
            ], 500);
        }
    }

    /**
     * Store a new eligibility question.
     */
    public function storeEligibilityQuestion(Request $request, $id)
    {
        try {
            $tender = Tender::findOrFail($id);

            $optionsData = null;
            if ($request->options && is_array($request->options)) {
                $optionsData = array_values(array_filter(
                    array_map(function ($opt) {
                        $label = trim($opt['label'] ?? (is_string($opt) ? $opt : ''));
                        if ($label === '') return null;
                        return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible']];
                    }, $request->options)
                ));
                if (empty($optionsData)) $optionsData = null;
            }

            $conditionData = null;
            if ($request->eligibility_condition && !empty($request->eligibility_condition['operator'])) {
                $conditionData = [
                    'operator' => $request->eligibility_condition['operator'],
                    'value'    => $request->eligibility_condition['value'] ?? null,
                    'value2'   => $request->eligibility_condition['value2'] ?? null,
                ];
            }

            $question = EligibilityQuestion::create([
                'tender_id'              => $tender->id,
                'question'               => $request->question,
                'description'            => $request->description,
                'field_type'             => $request->field_type,
                'is_required'            => $request->is_required,
                'determines_eligibility' => $request->determines_eligibility ?? false,
                'options'                => $optionsData,
                'eligible_options'       => null,
                'eligibility_condition'  => $conditionData,
                'max_size'               => $request->max_size,
                'order'                  => $tender->eligibilityQuestions()->count() + 1,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Question added successfully!',
                'question' => $question
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== TENDER OWNER METHODS ====================

    /**
     * Show form for tender owner to create tender.
     */
    public function ownerCreate()
    {
        $user = auth()->user();

        if (!$user->hasRole('tender-owner') && !$user->hasRole('admin')) {
            abort(403, 'Only tender owners and admins can create tenders.');
        }

        $categories = Category::active()->orderBy('name')->get();
        return view('tender-owner.tenders.create', compact('categories'));
    }

    /**
 * Display list of owner's tenders.
 */
public function ownerIndex(Request $request)
{
    $search = trim((string) $request->query('search', ''));
    $statusFilter = $request->query('status', '');

    $tenders = auth()->user()->ownedTenders()
        ->with(['categories', 'committeeMembers'])
        ->withCount('applications')
        ->when($search !== '', function ($q) use ($search) {
            $q->where(function ($q2) use ($search) {
                $q2->where('title', 'like', "%{$search}%")
                   ->orWhere('tender_number', 'like', "%{$search}%");
            });
        })
        ->when($statusFilter !== '', fn($q) => $q->where('status', $statusFilter))
        ->latest()
        ->paginate(10)
        ->withQueryString();

    $stats = [
        'total' => $tenders->total(),
        'published' => auth()->user()->ownedTenders()->where('status', 'published')->count(),
        'draft' => auth()->user()->ownedTenders()->where('status', 'draft')->count(),
        'closed' => auth()->user()->ownedTenders()->where('status', 'closed')->count(),
        'total_applications' => auth()->user()->ownedTenders()->withCount('applications')->get()->sum('applications_count'),
    ];
    
    return view('tender-owner.tenders.index', compact('tenders', 'stats'));
}
    /**
     * Store tender created by owner.
     */
   public function ownerStore(Request $request)
{
    $user = auth()->user();
    
    \Log::info('Tender Owner Store - User ID: ' . $user->id);
    \Log::info('Tender Owner Store - User Role: ' . $user->getRoleNames()->first());
    
   $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'eligibility_criteria' => 'nullable|string',
        'estimated_value' => 'nullable|numeric',
        'start_date' => 'required|date',
        'submission_deadline' => 'required|date',
        'opening_date' => 'nullable|date',
        'tender_type' => 'required|in:online,postal,hybrid',
        'postal_address' => 'required_if:tender_type,postal|nullable|string',
        'documents.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        'questions' => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {
        // Create tender with owner_id explicitly set
        $privateVendors = [];
        if ($request->filled('notify_specific_vendors')) {
            $privateVendors = array_values(array_filter(array_map('intval', explode(',', $request->notify_specific_vendors))));
        }

        $tender = Tender::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'eligibility_criteria' => $validated['eligibility_criteria'] ?? '',
            'submission_deadline' => $validated['submission_deadline'],
            'start_date' => $validated['start_date'],
            'opening_date' => $validated['opening_date'] ?? null,
            'opening_time'  => $request->filled('opening_time') ? $request->opening_time : null,
            'opening_venue' => $request->filled('opening_venue') ? $request->opening_venue : null,
            'tender_type' => $validated['tender_type'],
            'is_private'               => $request->boolean('is_private'),
            'notify_specific_vendors'  => $privateVendors,
            'notify_vendors_on_create' => $request->boolean('notify_vendors_on_create'),
            'postal_address'           => $validated['postal_address'] ?? null,
            'postal_receipt_deadline'  => $request->filled('postal_receipt_deadline') ? $request->postal_receipt_deadline : null,
            'status' => 'draft',
            'created_by' => $user->id,
            'owner_id' => $user->id,
            'require_bid_amount'          => $request->boolean('require_bid_amount'),
            'require_technical_bid'       => $request->boolean('require_technical_bid'),
            'require_financial_bid'       => $request->boolean('require_financial_bid'),
            'require_contract_signing'    => $request->boolean('require_contract_signing'),
            'require_emd'                 => $request->boolean('require_emd'),
            'require_estimated_value'     => $request->boolean('require_estimated_value'),
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled', true),
            'emda_amount'                 => $request->boolean('require_emd') && $request->filled('emda_amount') ? $request->emda_amount : null,
            'estimated_value'             => $request->boolean('require_estimated_value') && $request->filled('estimated_value') ? $request->estimated_value : null,
            'auto_publish'            => $request->boolean('auto_publish'),
            'show_on_university'      => $request->boolean('auto_publish') ? $request->boolean('show_on_university') : false,
            'university_description'  => $request->boolean('auto_publish') ? ($request->input('university_description') ?: null) : null,
        ]);

        \Log::info('Tender Created - ID: ' . $tender->id . ', Owner ID: ' . $tender->owner_id);

        // Save eligibility questions
        if ($request->has('questions') && is_array($request->questions)) {
            foreach ($request->questions as $questionData) {
                if (!empty($questionData['question'])) {
                    $optionsData = null;
                    if (!empty($questionData['options']) && is_array($questionData['options'])) {
                        $optionsData = array_values(array_filter(
                            array_map(function ($opt) {
                                $label = trim($opt['label'] ?? '');
                                if ($label === '') return null;
                                return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible'] == '1'];
                            }, $questionData['options'])
                        ));
                        if (empty($optionsData)) $optionsData = null;
                    }
                    $conditionData = null;
                    if (!empty($questionData['eligibility_condition']['operator'])) {
                        $conditionData = [
                            'operator' => $questionData['eligibility_condition']['operator'],
                            'value'    => $questionData['eligibility_condition']['value'] ?? null,
                            'value2'   => $questionData['eligibility_condition']['value2'] ?? null,
                        ];
                    }
                    EligibilityQuestion::create([
                        'tender_id'              => $tender->id,
                        'question'               => $questionData['question'],
                        'description'            => $questionData['description'] ?? null,
                        'field_type'             => $questionData['field_type'] ?? 'text',
                        'options'                => $optionsData,
                        'eligible_options'       => null,
                        'eligibility_condition'  => $conditionData,
                        'is_required'            => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                        'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                        'max_size'               => $questionData['max_size'] ?? 5120,
                    ]);
                }
            }
        }

        // Handle documents
        if ($request->hasFile('documents')) {
            $docCategories = $request->input('document_categories', []);
            $mandatories = $request->input('is_mandatory', []);
            foreach ($request->file('documents') as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('tenders/' . $tender->id, 'public');
                    TenderDocument::create([
                        'tender_id' => $tender->id,
                        'title' => $file->getClientOriginalName(),
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'file_extension' => $file->getClientOriginalExtension(),
                        'document_category' => $docCategories[$index] ?? 'bidding_document',
                        'is_mandatory' => isset($mandatories[$index]),
                        'uploaded_by' => $user->id,
                    ]);
                }
            }
        }

        // Sync categories
        if ($request->filled('category_ids')) {
            $tender->categories()->sync($request->input('category_ids'));
        }

        DB::commit();

        return redirect()->route('tender-owner.dashboard')
            ->with('success', 'Tender created successfully!');

    } catch (\Exception $e) {
        DB::rollback();
        \Log::error('Tender creation failed: ' . $e->getMessage());
        return back()->withErrors(['error' => 'Failed to create tender: ' . $e->getMessage()])->withInput();
    }
}

    /**
     * Show tender details for owner.
     */
  /**
 * Show tender details for owner.
 */
public function ownerShow($id)
{
    $tender = Tender::with(['creator', 'documents', 'eligibilityQuestions' => function($query) {
        $query->orderBy('order', 'asc');
    }, 'committeeMembers'])->findOrFail($id);

    if ($tender->owner_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
        abort(403, 'You do not have permission to view this tender.');
    }
    
    $stats = [
        'total_applications' => $tender->applications()->count(),
        'submitted_applications' => $tender->applications()->where('status', 'submitted')->count(),
        'shortlisted_applications' => $tender->applications()->where('status', 'shortlisted')->count(),
        'awarded_applications' => $tender->applications()->where('status', 'awarded')->count(),
    ];
    
    return view('tender-owner.tenders.show', compact('tender', 'stats'));
}
    /**
     * Edit form for owner.
     */
  /**
 * Edit form for owner.
 */
/**
 * Edit form for owner.
 */
/**
 * Edit form for owner.
 */
/**
 * Edit form for owner.
 */
/**
 * Edit form for owner.
 */
public function ownerEdit($id)
{
    $tender = Tender::with(['documents', 'eligibilityQuestions' => function($query) {
        $query->orderBy('order', 'asc');
    }, 'categories'])->findOrFail($id);

    if ($tender->owner_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
        abort(403, 'You do not have permission to edit this tender.');
    }

    $categories = Category::active()->orderBy('name')->get();
    $selectedCategoryIds = $tender->categories->pluck('id')->toArray();

    return view('tender-owner.tenders.edit', compact('tender', 'categories', 'selectedCategoryIds'));
}
    /**
     * Update tender for owner.
     */
   /**
 * Update tender for owner.
 */
public function ownerUpdate(Request $request, $id)
{
    $tender = Tender::findOrFail($id);
    
    if ($tender->owner_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
        abort(403, 'You do not have permission to update this tender.');
    }
    
    $validated = $request->validate([
        'title'                => 'required|string|max:255',
        'description'          => 'required|string',
        'eligibility_criteria' => 'nullable|string',
        'estimated_value'      => 'nullable|numeric|min:0',
        'emda_amount'          => 'nullable|numeric|min:0',
        'start_date'           => 'required|date',
        'submission_deadline'  => 'required|date',
        'opening_date'         => 'nullable|date',
        'tender_type'          => 'required|in:online,postal,hybrid',
        'postal_address'       => 'required_if:tender_type,postal|nullable|string',
    ]);

    DB::beginTransaction();

    try {
        $privateVendors = [];
        if ($request->filled('notify_specific_vendors')) {
            $privateVendors = array_values(array_filter(array_map('intval', explode(',', $request->notify_specific_vendors))));
        }

        $validated['eligibility_criteria'] = $validated['eligibility_criteria'] ?? '';

        $ownerAutoPublishData = [];
        if ($tender->isDraft()) {
            $ownerAutoPublishData['auto_publish'] = $request->boolean('auto_publish');
            if ($request->boolean('auto_publish')) {
                $ownerAutoPublishData['show_on_university'] = $request->boolean('show_on_university');
                $ownerAutoPublishData['university_description'] = $request->input('university_description') ?: null;
            }
        }

        $tender->update(array_merge($validated, [
            'start_date'                  => $request->filled('start_date') ? $request->start_date : null,
            'opening_date'                => $request->filled('opening_date') ? $request->opening_date : null,
            'is_private'                  => $request->boolean('is_private'),
            'notify_specific_vendors'     => $privateVendors,
            'notify_vendors_on_create'    => $request->boolean('notify_vendors_on_create'),
            'opening_time'                => $request->filled('opening_time') ? $request->opening_time : null,
            'opening_venue'               => $request->filled('opening_venue') ? $request->opening_venue : null,
            'postal_receipt_deadline'     => $request->filled('postal_receipt_deadline') ? $request->postal_receipt_deadline : null,
            'require_bid_amount'          => $request->boolean('require_bid_amount'),
            'require_technical_bid'       => $request->boolean('require_technical_bid'),
            'require_financial_bid'       => $request->boolean('require_financial_bid'),
            'require_contract_signing'    => $request->boolean('require_contract_signing'),
            'require_emd'                 => $request->boolean('require_emd'),
            'require_estimated_value'     => $request->boolean('require_estimated_value'),
            'email_notifications_enabled' => $request->boolean('email_notifications_enabled'),
            'emda_amount'                 => $request->boolean('require_emd') && $request->filled('emda_amount') ? $request->emda_amount : null,
            'estimated_value'             => $request->boolean('require_estimated_value') && $request->filled('estimated_value') ? $request->estimated_value : null,
        ] + $ownerAutoPublishData));

        // Handle new document uploads
        if ($request->hasFile('new_documents')) {
            $currentOrder = $tender->documents()->max('order') ?? 0;
            
            foreach ($request->file('new_documents') as $index => $file) {
                if ($file && $file->isValid()) {
                    $path = $file->store('tenders/' . $tender->id, 'public');
                    
                    TenderDocument::create([
                        'tender_id' => $tender->id,
                        'title' => $file->getClientOriginalName(),
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'file_extension' => $file->getClientOriginalExtension(),
                        'document_category' => $request->document_categories[$index] ?? 'bidding_document',
                        'is_mandatory' => isset($request->is_mandatory[$index]),
                        'uploaded_by' => auth()->id(),
                        'order' => $currentOrder + $index + 1,
                    ]);
                }
            }
        }
        
        // Handle new eligibility questions
        if ($request->has('new_questions')) {
            foreach ($request->new_questions as $questionData) {
                if (!empty($questionData['question'])) {
                    $optionsData = null;
                    if (!empty($questionData['options']) && is_array($questionData['options'])) {
                        $optionsData = array_values(array_filter(
                            array_map(function ($opt) {
                                $label = trim($opt['label'] ?? '');
                                if ($label === '') return null;
                                return ['label' => $label, 'eligible' => isset($opt['eligible']) && $opt['eligible'] == '1'];
                            }, $questionData['options'])
                        ));
                        if (empty($optionsData)) $optionsData = null;
                    }
                    $conditionData = null;
                    if (!empty($questionData['eligibility_condition']['operator'])) {
                        $conditionData = [
                            'operator' => $questionData['eligibility_condition']['operator'],
                            'value'    => $questionData['eligibility_condition']['value'] ?? null,
                            'value2'   => $questionData['eligibility_condition']['value2'] ?? null,
                        ];
                    }
                    EligibilityQuestion::create([
                        'tender_id'              => $tender->id,
                        'question'               => $questionData['question'],
                        'description'            => $questionData['description'] ?? null,
                        'field_type'             => $questionData['field_type'] ?? 'text',
                        'options'                => $optionsData,
                        'eligible_options'       => null,
                        'eligibility_condition'  => $conditionData,
                        'is_required'            => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                        'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                        'max_size'               => $questionData['max_size'] ?? 5120,
                        'order'                  => $tender->eligibilityQuestions()->count() + 1,
                    ]);
                }
            }
        }

        // Handle removed eligibility questions
        if ($request->has('eligibility_questions_removed')) {
            $removedIds = json_decode($request->eligibility_questions_removed, true);
            if (!empty($removedIds)) {
                EligibilityQuestion::whereIn('id', $removedIds)->delete();
            }
        }

        // Sync categories
        $tender->categories()->sync($request->input('category_ids', []));

        DB::commit();

        return redirect()->route('tender-owner.tenders.index')
            ->with('success', 'Tender updated successfully!');
            
    } catch (\Exception $e) {
        DB::rollback();
        return back()->withErrors(['error' => 'Failed to update tender: ' . $e->getMessage()])->withInput();
    }
}
    /**
     * Delete tender for owner.
     */
    public function ownerDestroy($id)
    {
        $tender = Tender::findOrFail($id);
        
        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        try {
            foreach ($tender->documents as $document) {
                Storage::disk('public')->delete($document->file_path);
                $document->delete();
            }
            
            $tender->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Tender deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tender'
            ], 500);
        }
    }

    /**
     * Publish tender for owner.
     */
    public function ownerPublish(Request $request, $id)
    {
        $tender = Tender::with('categories.vendors')->findOrFail($id);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $showOnUniversity = $request->boolean('show_on_university');
            $tender->update([
                'status'                 => 'published',
                'published_date'         => now(),
                'approved_by'            => auth()->id(),
                'approved_at'            => now(),
                'show_on_university'     => $showOnUniversity,
                'university_description' => $showOnUniversity ? ($request->university_description ?? null) : null,
            ]);

            $this->notifyVendorsOnPublish($tender);

            return response()->json([
                'success' => true,
                'message' => 'Tender published successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to publish tender'
            ], 500);
        }
    }

    /**
     * Close tender for owner.
     */
    public function ownerClose(Request $request, $id)
    {
        $tender = Tender::findOrFail($id);

        if ($tender->owner_id !== auth()->id() && !auth()->user()->hasRole('admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        try {
            $reason = $request->input('reason', '');
            $tender->update(['status' => 'closed', 'closed_reason' => $reason]);

            // Notify vendors who applied
            if ($tender->email_notifications_enabled ?? false) {
                $vendorIds = TenderApplication::where('tender_id', $tender->id)
                    ->whereNotNull('vendor_id')
                    ->pluck('vendor_id')
                    ->unique();
                $emails = Vendor::whereIn('id', $vendorIds)->pluck('email');
                foreach ($emails as $email) {
                    try {
                        Mail::to($email)->send(new TenderClosedMail($tender, $reason));
                    } catch (\Exception $mailEx) {
                        \Log::error('TenderClosedMail failed to ' . $email . ': ' . $mailEx->getMessage());
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Tender closed and vendors notified!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close tender'
            ], 500);
        }
    }
    /**
 * Delete document for owner.
 */
/**
 * Delete document for owner.
 */
public function ownerDeleteDocument($id)
{
    try {
        \Log::info('Owner delete document called', ['document_id' => $id, 'user_id' => auth()->id()]);
        
        $document = TenderDocument::findOrFail($id);
        $tender = $document->tender;
        
        \Log::info('Document found', ['tender_id' => $tender->id, 'owner_id' => $tender->owner_id]);
        
        // Check if the current user is the owner of the tender
        if ($tender->owner_id !== auth()->id()) {
            \Log::warning('Unauthorized delete attempt', ['user_id' => auth()->id(), 'tender_owner_id' => $tender->owner_id]);
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You do not have permission to delete this document.'
            ], 403);
        }
        
        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
            \Log::info('File deleted from storage', ['path' => $document->file_path]);
        }
        
        // Delete record
        $document->delete();
        \Log::info('Document record deleted', ['document_id' => $id]);
        
        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully!'
        ]);
    } catch (\Exception $e) {
        \Log::error('Error deleting document', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete document: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Send TenderPublishedMail when a tender is created, either to all category vendors
     * or to a specific list of vendor IDs.
     */
    private function notifyVendorsOnCreate(Tender $tender, string $mode, ?string $specificVendorIds): void
    {
        if (!($tender->email_notifications_enabled ?? false)) return;
        $tender->loadMissing('categories.vendors');

        if ($mode === 'specific' && $specificVendorIds) {
            $ids = array_filter(array_map('intval', explode(',', $specificVendorIds)));
            if (empty($ids)) return;
            $vendors = \App\Models\Vendor::whereIn('id', $ids)->whereNotNull('email')->get();
        } else {
            // All vendors in selected categories
            $vendorCollection = collect();
            foreach ($tender->categories as $category) {
                foreach ($category->vendors as $vendor) {
                    if (!$vendorCollection->contains('id', $vendor->id)) {
                        $vendorCollection->push($vendor);
                    }
                }
            }
            $vendors = $vendorCollection;
        }

        foreach ($vendors as $vendor) {
            if ($vendor->email) {
                try {
                    Mail::to($vendor->email)->send(new TenderPublishedMail($tender, $vendor));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send tender create notification to vendor ' . $vendor->id . ': ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Send TenderPublishedMail respecting the vendor selection set during tender creation.
     * - If notify_specific_vendors is set → only those vendor IDs get the mail.
     * - Otherwise → all approved vendors belonging to the tender's categories.
     */
    private function notifyVendorsOnPublish(Tender $tender): void
    {
        if (!($tender->email_notifications_enabled ?? false)) return;
        $specificIds = array_filter(
            array_map('intval', (array) ($tender->notify_specific_vendors ?? []))
        );

        if (!empty($specificIds)) {
            $vendors = \App\Models\Vendor::whereIn('id', $specificIds)
                ->where('status', 'approved')
                ->whereNotNull('email')
                ->get();
        } else {
            $tender->loadMissing('categories.vendors');
            $seen    = collect();
            $vendors = collect();
            foreach ($tender->categories as $category) {
                foreach ($category->vendors as $vendor) {
                    if (!$seen->contains($vendor->id)) {
                        $seen->push($vendor->id);
                        if ($vendor->email && $vendor->status === 'approved') {
                            $vendors->push($vendor);
                        }
                    }
                }
            }
        }

        foreach ($vendors as $vendor) {
            try {
                Mail::to($vendor->email)->send(new TenderPublishedMail($tender, $vendor));
            } catch (\Exception $e) {
                \Log::warning('TenderPublishedMail failed for vendor ' . $vendor->id . ': ' . $e->getMessage());
            }
        }
    }
}