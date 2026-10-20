<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Tender;
use App\Models\TenderApplication;
use App\Models\Contract;
use App\Models\EligibilityQuestion;
use App\Models\EligibilityAnswer;
use App\Models\TenderDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\EligibilityFailedMail;
use App\Mail\ApplicationSubmittedVendorMail;
use App\Mail\ApplicationSubmittedAdminMail;
use App\Models\EligibilityFailure;
use App\Traits\HasDashboardWidgets;

class VendorController extends Controller
{
    use HasDashboardWidgets;

    // ==================== DASHBOARD ====================

    public function dashboard()
    {
        $vendor = Auth::user()->vendor;

        $stats = [
            'total_applications'    => TenderApplication::where('vendor_id', $vendor->id)->count(),
            'submitted_applications'=> TenderApplication::where('vendor_id', $vendor->id)->where('status', 'submitted')->count(),
            'shortlisted_applications' => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'shortlisted')->count(),
            'awarded_applications'  => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'awarded')->count(),
            'active_contracts'      => Contract::where('vendor_id', $vendor->id)->where('status', 'active')->count(),
            'total_contract_value'  => Contract::where('vendor_id', $vendor->id)->where('status', 'active')->sum('contract_value'),
        ];

        $openTenders = Tender::where('status', 'published')
            ->where('is_private', false)
            ->whereDate('submission_deadline', '>=', now()->toDateString())
            ->count();

        $privateTendersAssigned = Tender::where('status', 'published')
            ->where('is_private', true)
            ->whereJsonContains('notify_specific_vendors', $vendor->id)
            ->whereDate('submission_deadline', '>=', now()->toDateString())
            ->count();

        $recentApplications = TenderApplication::where('vendor_id', $vendor->id)
            ->with('tender')->latest()->take(5)->get();

        $recentContracts = Contract::where('vendor_id', $vendor->id)
            ->with('tender')->latest()->take(5)->get();

        // Last 6 months application trend
        $appMonthly = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $appMonthly[] = [
                'label' => $m->format('M y'),
                'count' => TenderApplication::where('vendor_id', $vendor->id)
                    ->whereYear('created_at', $m->year)
                    ->whereMonth('created_at', $m->month)
                    ->count(),
            ];
        }

        $widgetData = $this->getDashboardWidgetData();

        return view('vendor.dashboard', compact(
            'stats', 'openTenders', 'privateTendersAssigned',
            'recentApplications', 'recentContracts', 'appMonthly', 'widgetData'
        ));
    }

    // ==================== TENDERS ====================

    public function tenders()
    {
        $vendor = Auth::user()->vendor;
        $vendorCategoryIds = $vendor->categories()->pluck('categories.id');

        $query = Tender::where('status', 'published')
            ->whereDate('submission_deadline', '>=', now()->toDateString());

        // Category filter: vendor sees tenders in their categories (or uncategorised).
        // Always bypass for private tenders specifically assigned to this vendor.
        if ($vendorCategoryIds->isNotEmpty()) {
            $query->where(function ($q) use ($vendorCategoryIds, $vendor) {
                $q->where(function ($q2) use ($vendorCategoryIds) {
                    $q2->whereHas('categories', function ($q3) use ($vendorCategoryIds) {
                        $q3->whereIn('categories.id', $vendorCategoryIds);
                    })->orWhereDoesntHave('categories');
                })->orWhere(function ($q2) use ($vendor) {
                    $q2->where('is_private', true)
                       ->where(function ($q3) use ($vendor) {
                           $q3->whereJsonContains('notify_specific_vendors', $vendor->id)
                              ->orWhereJsonContains('notify_specific_vendors', (string) $vendor->id);
                       });
                });
            });
        }

        // Private tender access: only show public, or private if vendor is specifically invited
        $query->where(function ($q) use ($vendor) {
            $q->where('is_private', false)
              ->orWhere(function ($q2) use ($vendor) {
                  $q2->where('is_private', true)
                     ->where(function ($q3) use ($vendor) {
                         $q3->whereJsonContains('notify_specific_vendors', $vendor->id)
                            ->orWhereJsonContains('notify_specific_vendors', (string) $vendor->id);
                     });
              });
        });

        $type = request('type', 'online');
        if (in_array($type, ['online', 'postal', 'hybrid'])) {
            $query->where('tender_type', $type);
        }

        $tenders = $query->orderBy('submission_deadline', 'asc')->paginate(10);

        $appliedTenderIds = TenderApplication::where('vendor_id', $vendor->id)
            ->pluck('tender_id')
            ->flip(); // use as hash-map for O(1) lookup in the view

        return view('vendor.tenders.index', compact('tenders', 'type', 'appliedTenderIds'));
    }

    public function tenderDetails($id)
    {
        $tender = Tender::with(['documents', 'eligibilityQuestions', 'creator'])->findOrFail($id);
        $vendor  = Auth::user()->vendor;

        if (!$tender->isAccessibleByVendor($vendor->id)) {
            abort(403, 'This is a private tender. You have not been invited to participate.');
        }
        $hasApplied = TenderApplication::where('tender_id', $id)
            ->where('vendor_id', $vendor->id)->exists();

        // Track this page view
        \App\Models\TenderView::create([
            'tender_id'  => $id,
            'user_id'    => Auth::id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $stats = [
            'total_applications'      => TenderApplication::where('tender_id', $id)->count(),
            'submitted_applications'  => TenderApplication::where('tender_id', $id)->where('status', 'submitted')->count(),
            'shortlisted_applications'=> TenderApplication::where('tender_id', $id)->where('status', 'shortlisted')->count(),
            'awarded_applications'    => TenderApplication::where('tender_id', $id)->where('status', 'awarded')->count(),
        ];

        return view('vendor.tenders.show', compact('tender', 'hasApplied', 'stats'));
    }

    // ==================== APPLICATION FORM ====================

    public function showApplicationForm($id)
    {
        $tender = Tender::with(['eligibilityQuestions', 'documents', 'categories', 'owner'])->findOrFail($id);
        $vendor  = Auth::user()->vendor;

        if (!$tender->isAccessibleByVendor($vendor->id)) {
            abort(403, 'This is a private tender. You have not been invited to participate.');
        }

        if (TenderApplication::where('tender_id', $id)->where('vendor_id', $vendor->id)->exists()) {
            return redirect()->route('vendor.tenders.show', $id)
                ->with('error', 'You have already applied for this tender.');
        }

        $hasQuestions = $tender->eligibilityQuestions()->count() > 0;

        // No eligibility questions → vendor is auto-eligible; pre-set the session flag
        if (!$hasQuestions) {
            session(["eligible_tender_{$tender->id}" => true]);
        }

        return view('vendor.tenders.apply', compact('tender', 'vendor', 'hasQuestions'));
    }

    // ==================== ELIGIBILITY CHECK (AJAX) ====================

    /**
     * AJAX-only: evaluates eligibility answers, returns JSON, does NOT write to DB.
     */
    public function checkEligibilitySubmit(Request $request, $id)
    {
        $tender  = Tender::with('eligibilityQuestions')->findOrFail($id);
        $vendor  = Auth::user()->vendor;

        // If the tender has no questions, always eligible
        if ($tender->eligibilityQuestions->isEmpty()) {
            return response()->json(['eligible' => true]);
        }

        $failedQuestions = [];

        foreach ($tender->eligibilityQuestions as $question) {
            if (!$question->determines_eligibility) {
                continue;
            }

            $answer = $request->input("eligibility_answers.{$question->id}");

            // Treat missing required answers as ineligible
            if ($question->is_required && ($answer === null || $answer === '' || $answer === [])) {
                $failedQuestions[] = $question->question;
                continue;
            }

            if (!$this->checkEligibility($question, $answer)) {
                $failedQuestions[] = $question->question;
            }
        }

        if (!empty($failedQuestions)) {
            // Collect submitted answers for the record
            $submittedAnswers = [];
            foreach ($tender->eligibilityQuestions as $q) {
                $submittedAnswers[$q->id] = [
                    'question' => $q->question,
                    'answer'   => $request->input("eligibility_answers.{$q->id}"),
                ];
            }

            // Persist failure to DB
            try {
                EligibilityFailure::create([
                    'tender_id'        => $tender->id,
                    'vendor_id'        => $vendor->id,
                    'failed_questions' => $failedQuestions,
                    'submitted_answers'=> $submittedAnswers,
                    'ip_address'       => $request->ip(),
                ]);
            } catch (\Exception $e) {
                \Log::error('EligibilityFailure save error: ' . $e->getMessage());
            }

            // Email vendor
            try {
                Mail::to($vendor->email)->send(new EligibilityFailedMail($tender, $vendor));
            } catch (\Exception $e) {
                \Log::error('Eligibility failed email (vendor) error: ' . $e->getMessage());
            }

            // Email tender owner (if set)
            try {
                if ($tender->owner_id) {
                    $owner = \App\Models\User::find($tender->owner_id);
                    if ($owner) {
                        Mail::to($owner->email)->send(new EligibilityFailedMail($tender, $vendor));
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Eligibility failed email (owner) error: ' . $e->getMessage());
            }

            // Email all admins
            try {
                User::role(['admin', 'super-admin'])->get()->each(function ($admin) use ($tender, $vendor) {
                    Mail::to($admin->email)->send(new EligibilityFailedMail($tender, $vendor));
                });
            } catch (\Exception $e) {
                \Log::error('Eligibility failed email (admin) error: ' . $e->getMessage());
            }

            return response()->json([
                'eligible' => false,
                'message'  => 'You do not meet the eligibility criteria for this tender.',
                'failed'   => $failedQuestions,
            ]);
        }

        session(["eligible_tender_{$id}" => true]);
        return response()->json(['eligible' => true]);
    }

    // ==================== SUBMIT APPLICATION ====================

    public function submitApplication(Request $request, $id)
    {
        $tender = Tender::with('eligibilityQuestions')->findOrFail($id);
        $vendor  = Auth::user()->vendor;

        // Prevent duplicate applications (online & postal)
        if (TenderApplication::where('tender_id', $tender->id)->where('vendor_id', $vendor->id)->exists()) {
            return redirect()->route('vendor.tenders.show', $id)
                ->with('error', 'You have already submitted an application for this tender.');
        }

        $hasTechFile = $request->hasFile('technical_proposal_file');
        $hasFinFile  = $request->hasFile('financial_proposal_file');

        $rules = [];
        // Postal tenders: bid/proposals not required (submitted physically)
        if ($tender->isPostal()) {
            $rules['bid_amount']                = 'nullable|numeric|min:0';
            $rules['technical_proposal']        = 'nullable|string';
            $rules['financial_proposal']        = 'nullable|string';
        } else {
            $rules['bid_amount'] = ($tender->require_bid_amount ?? true)
                ? 'required|numeric|min:0' : 'nullable|numeric|min:0';

            // Text OR file upload both satisfy the "required" constraint
            $rules['technical_proposal']      = ($tender->require_technical_bid ?? true) && !$hasTechFile
                ? 'required|string' : 'nullable|string';
            $rules['technical_proposal_file'] = $hasTechFile
                ? 'required|file|mimes:pdf,doc,docx|max:10240' : 'nullable|file';

            $rules['financial_proposal']      = ($tender->require_financial_bid ?? true) && !$hasFinFile
                ? 'required|string' : 'nullable|string';
            $rules['financial_proposal_file'] = $hasFinFile
                ? 'required|file|mimes:pdf,doc,docx|max:10240' : 'nullable|file';
        }

        if ($request->hasFile('application_documents')) {
            $rules['application_documents']   = 'array';
            $rules['application_documents.*'] = 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
        }
        foreach ($tender->eligibilityQuestions as $question) {
            if ($question->field_type === 'file' && $request->hasFile("eligibility_files.{$question->id}")) {
                $rules["eligibility_files.{$question->id}"] = 'file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240';
            }
        }

        $request->validate($rules);

        DB::beginTransaction();
        try {
            // Check eligibility: session is the primary source (set by the AJAX check endpoint).
            // Fall back to re-evaluating request data for vendors who bypass the JS flow.
            $hasDeterminingQuestions = $tender->eligibilityQuestions
                ->filter(fn($q) => $q->determines_eligibility)
                ->isNotEmpty();

            $sessionKey  = "eligible_tender_{$tender->id}";
            $sessionPass = session($sessionKey, false);

            if ($hasDeterminingQuestions && !$sessionPass) {
                // Session not set — re-evaluate using request data (direct POST bypass attempt)
                $allEligible = true;
                foreach ($tender->eligibilityQuestions as $question) {
                    if (!$question->determines_eligibility) continue;
                    $answer = $request->input("eligibility_answers.{$question->id}");
                    if (!$this->checkEligibility($question, $answer)) {
                        $allEligible = false;
                        break;
                    }
                }

                if (!$allEligible) {
                    DB::rollback();
                    try {
                        Mail::to($vendor->email)->send(new EligibilityFailedMail($tender, $vendor));
                    } catch (\Exception $e) {
                        \Log::error('Eligibility failed email error: ' . $e->getMessage());
                    }
                    return back()->withErrors(['eligibility' => 'You do not meet the eligibility criteria for this tender.']);
                }
            }

            // Clear the session flag after a successful eligibility gate pass
            session()->forget($sessionKey);

            // Handle online document uploads
            $uploadedDocuments = [];
            if ($tender->hasOnlineBids() && $request->hasFile('application_documents')) {
                foreach ($request->file('application_documents') as $file) {
                    $path = $file->store('application-documents/' . $tender->id, 'public');
                    $uploadedDocuments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                    ];
                }
            }

            // Handle proposal file uploads (when vendor uploads doc instead of typing)
            $techProposalFile = null;
            $finProposalFile  = null;
            if ($request->hasFile('technical_proposal_file')) {
                $techProposalFile = $request->file('technical_proposal_file')->store('application-documents/' . $tender->id, 'public');
            }
            if ($request->hasFile('financial_proposal_file')) {
                $finProposalFile = $request->file('financial_proposal_file')->store('application-documents/' . $tender->id, 'public');
            }

            $application = TenderApplication::create([
                'tender_id'               => $tender->id,
                'vendor_id'               => $vendor->id,
                'bid_amount'              => $tender->isPostal() ? null : $request->bid_amount,
                'technical_proposal'      => $request->technical_proposal,
                'technical_proposal_file' => $techProposalFile,
                'financial_proposal'      => $request->financial_proposal,
                'financial_proposal_file' => $finProposalFile,
                'documents'               => $uploadedDocuments ?: null,
                'status'                  => 'submitted',
                'submitted_by'            => Auth::id(),
                'submitted_at'            => now(),
            ]);

            // Save eligibility answers
            foreach ($tender->eligibilityQuestions as $question) {
                $answer   = $request->input("eligibility_answers.{$question->id}");
                $filePath = null;

                if ($question->field_type === 'file' && $request->hasFile("eligibility_files.{$question->id}")) {
                    $file     = $request->file("eligibility_files.{$question->id}");
                    $filePath = $file->store('eligibility-documents/' . $tender->id, 'public');
                }

                EligibilityAnswer::create([
                    'tender_application_id' => $application->id,
                    'question_id'           => $question->id,
                    'answer'                => is_array($answer) ? json_encode($answer) : $answer,
                    'file_path'             => $filePath,
                    'is_eligible'           => $this->checkEligibility($question, $answer),
                ]);
            }

            DB::commit();

            // Send confirmation email to vendor
            try {
                $application->load('tender', 'vendor');
                Mail::to($vendor->email)->send(new ApplicationSubmittedVendorMail($application));
            } catch (\Exception $e) {
                \Log::error('Application vendor email error: ' . $e->getMessage());
            }

            // Notify admins
            try {
                $admins = User::role(['admin', 'super-admin'])->get();
                foreach ($admins as $admin) {
                    Mail::to($admin->email)->send(new ApplicationSubmittedAdminMail($application));
                }
            } catch (\Exception $e) {
                \Log::error('Application admin email error: ' . $e->getMessage());
            }

            // Notify tender owner if set
            try {
                if ($tender->owner_id) {
                    $owner = User::find($tender->owner_id);
                    if ($owner) {
                        Mail::to($owner->email)->send(new ApplicationSubmittedAdminMail($application));
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Application owner email error: ' . $e->getMessage());
            }

            // Hybrid included: the online part is submitted, but physical documents are
            // still expected by post, so the vendor must be shown the address.
            if ($tender->hasPostalBids()) {
                return redirect()->route('vendor.applications')
                    ->with('success', 'Application submitted successfully! Please send your physical documents to the postal address below.')
                    ->with('postal_address', $tender->postal_address)
                    ->with('application_number', $application->application_number);
            }

            return redirect()->route('vendor.applications')
                ->with('success', 'Application submitted successfully! Your application number is ' . $application->application_number . '.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Application submit error: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to submit application: ' . $e->getMessage()]);
        }
    }

    // ==================== ELIGIBILITY LOGIC ====================

    private function checkEligibility($question, $answer): bool
    {
        if (!$question->determines_eligibility) {
            return true;
        }

        switch ($question->field_type) {
            case 'checkbox':
                $vendorAnswers  = is_array($answer) ? $answer : (json_decode($answer, true) ?? (array)$answer);
                $requiredOptions = collect($question->options ?? [])
                    ->filter(fn($opt) => is_array($opt) ? ($opt['eligible'] ?? false) : false)
                    ->pluck('label')
                    ->toArray();
                // Legacy: fall back to eligible_options if no new format present
                if (empty($question->options) || (isset($question->options[0]) && is_string($question->options[0]))) {
                    $eligibleOptions = $question->eligible_options ?? [];
                    if (empty($eligibleOptions)) return true;
                    return count(array_intersect($vendorAnswers, $eligibleOptions)) > 0;
                }
                return empty($requiredOptions) ||
                       count(array_intersect($vendorAnswers, $requiredOptions)) === count($requiredOptions);

            case 'select':
            case 'radio':
                $vendorAnswer = $answer;
                $eligibleLabels = collect($question->options ?? [])
                    ->filter(fn($opt) => is_array($opt) ? ($opt['eligible'] ?? false) : false)
                    ->pluck('label')
                    ->toArray();
                // Legacy: fall back to eligible_options if no new format present
                if (empty($question->options) || (isset($question->options[0]) && is_string($question->options[0]))) {
                    $eligibleOptions = $question->eligible_options ?? [];
                    if (empty($eligibleOptions)) return true;
                    return in_array($vendorAnswer, $eligibleOptions);
                }
                return empty($eligibleLabels) || in_array($vendorAnswer, $eligibleLabels);

            case 'text':
            case 'textarea':
                $vendorAnswer = trim((string)($answer ?? ''));
                $cond = $question->eligibility_condition ?? ['operator' => 'any'];
                // Legacy: fall back to eligible_options if no new condition set
                if (empty($cond) || empty($cond['operator'])) {
                    $eligibleOptions = $question->eligible_options ?? [];
                    if (empty($eligibleOptions)) return $vendorAnswer !== '';
                    foreach ($eligibleOptions as $opt) {
                        if (strcasecmp($vendorAnswer, trim((string)$opt)) === 0) return true;
                    }
                    return false;
                }
                return match($cond['operator'] ?? 'any') {
                    'equals'   => strtolower($vendorAnswer) === strtolower($cond['value'] ?? ''),
                    'contains' => str_contains(strtolower($vendorAnswer), strtolower($cond['value'] ?? '')),
                    default    => $vendorAnswer !== '',
                };

            case 'number':
                $vendorAnswer = floatval($answer ?? 0);
                $cond = $question->eligibility_condition ?? ['operator' => 'any'];
                // Legacy: fall back to eligible_options if no new condition set
                if (empty($cond) || empty($cond['operator'])) {
                    $eligibleOptions = $question->eligible_options ?? [];
                    if (empty($eligibleOptions)) return true;
                    foreach ($eligibleOptions as $condition) {
                        if (preg_match('/^(>=|<=|>|<|=)(.+)$/', trim($condition), $m)) {
                            $val  = (float) $m[2];
                            $pass = match ($m[1]) {
                                '>='    => $vendorAnswer >= $val,
                                '<='    => $vendorAnswer <= $val,
                                '>'     => $vendorAnswer > $val,
                                '<'     => $vendorAnswer < $val,
                                default => $vendorAnswer == $val,
                            };
                            if ($pass) return true;
                        } elseif ($vendorAnswer == (float)$condition) {
                            return true;
                        }
                    }
                    return false;
                }
                $v  = floatval($cond['value'] ?? 0);
                $v2 = floatval($cond['value2'] ?? 0);
                return match($cond['operator'] ?? 'any') {
                    'equals'  => $vendorAnswer == $v,
                    'gte'     => $vendorAnswer >= $v,
                    'lte'     => $vendorAnswer <= $v,
                    'between' => $vendorAnswer >= $v && $vendorAnswer <= $v2,
                    default   => true,
                };

            default:
                return true;
        }
    }

    // ==================== APPLICATIONS ====================

    public function applications()
    {
        $vendor = Auth::user()->vendor;
        $applications = TenderApplication::where('vendor_id', $vendor->id)
            ->with('tender')->orderBy('created_at', 'desc')->paginate(10);

        $stats = [
            'total'       => $applications->total(),
            'submitted'   => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'submitted')->count(),
            'shortlisted' => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'shortlisted')->count(),
            'awarded'     => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'awarded')->count(),
            'rejected'    => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'rejected')->count(),
        ];

        return view('vendor.applications.index', compact('applications', 'stats'));
    }

    public function applicationDetails($id)
    {
        $vendor = Auth::user()->vendor;
        $application = TenderApplication::where('vendor_id', $vendor->id)
            ->with(['tender', 'tender.documents', 'queries.sender', 'eligibilityAnswers.question'])->findOrFail($id);
        return view('vendor.applications.show', compact('application'));
    }

    public function withdrawApplication($id)
    {
        try {
            $application = TenderApplication::where('vendor_id', Auth::user()->vendor->id)
                ->whereIn('status', ['submitted', 'draft'])->findOrFail($id);

            $application->update(['status' => 'withdrawn']);

            return response()->json(['success' => true, 'message' => 'Application withdrawn successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to withdraw application'], 500);
        }
    }

    // ==================== CONTRACTS ====================

    public function contracts()
    {
        $vendor = Auth::user()->vendor;
        $contracts = Contract::where('vendor_id', $vendor->id)
            ->with('tender')->orderBy('created_at', 'desc')->paginate(10);
        return view('vendor.contracts.index', compact('contracts'));
    }

    public function contractDetails($id)
    {
        $vendor   = Auth::user()->vendor;
        $contract = Contract::where('vendor_id', $vendor->id)
            ->with(['tender', 'extensions'])
            ->findOrFail($id);

        $siblingContracts = $contract->application_id
            ? Contract::where('application_id', $contract->application_id)
                ->where('id', '!=', $contract->id)
                ->where('vendor_id', $vendor->id)
                ->orderBy('created_at')
                ->get()
            : collect();

        return view('vendor.contracts.show', compact('contract', 'siblingContracts'));
    }

    public function downloadOrderPdf($id)
    {
        $vendor   = Auth::user()->vendor;
        $contract = Contract::where('vendor_id', $vendor->id)->findOrFail($id);

        if (empty($contract->order_items)) {
            return back()->with('error', 'No generated order PDF available for this contract.');
        }

        return app(ContractController::class)->downloadOrderPdf($id);
    }

    public function signContract($id)
    {
        try {
            $contract = Contract::where('vendor_id', Auth::user()->vendor->id)
                ->where('status', 'draft')->findOrFail($id);

            $contract->update([
                'signed_by_vendor'  => Auth::id(),
                'vendor_signed_at'  => now(),
            ]);

            if ($contract->signed_by_admin) {
                $contract->update(['status' => 'active']);
            }

            return response()->json(['success' => true, 'message' => 'Contract signed successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to sign contract'], 500);
        }
    }

    // ==================== PROFILE ====================

    public function profile()
    {
        $vendor = Auth::user()->vendor;
        return view('vendor.profile.index', compact('vendor'));
    }

    public function updateProfile(Request $request)
    {
        $vendor = Auth::user()->vendor;
        $request->validate([
            'company_name' => 'required|string|max:255',
            'user_name'    => 'required|string|max:255',
            'phone'        => 'required|string|max:20',
            'address'      => 'required|string',
            'city'         => 'required|string',
            'state'        => 'required|string',
            'pincode'      => 'required|string|max:10',
        ]);
        $vendor->update($request->only(['company_name', 'phone', 'address', 'city', 'state', 'pincode']));
        Auth::user()->update(['name' => $request->user_name]);
        return back()->with('success', 'Profile updated successfully');
    }

    public function uploadDocument(Request $request)
    {
        $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'type'     => 'required|string',
        ]);

        $vendor    = Auth::user()->vendor;
        $documents = $vendor->registration_documents ?? [];
        $path      = $request->file('document')->store('vendor-documents/' . $vendor->id, 'public');
        $documents[$request->type] = $path;
        $vendor->update(['registration_documents' => $documents]);

        return back()->with('success', 'Document uploaded successfully');
    }

    public function deleteDocument($type)
    {
        $vendor    = Auth::user()->vendor;
        $documents = $vendor->registration_documents ?? [];

        if (isset($documents[$type])) {
            Storage::disk('public')->delete($documents[$type]);
            unset($documents[$type]);
            $vendor->update(['registration_documents' => $documents]);
        }

        return back()->with('success', 'Document deleted successfully');
    }

    // ==================== STATS API ====================

    public function getStats()
    {
        $vendor = Auth::user()->vendor;
        return response()->json([
            'total_applications'       => TenderApplication::where('vendor_id', $vendor->id)->count(),
            'submitted_applications'   => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'submitted')->count(),
            'shortlisted_applications' => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'shortlisted')->count(),
            'awarded_applications'     => TenderApplication::where('vendor_id', $vendor->id)->where('status', 'awarded')->count(),
            'active_contracts'         => Contract::where('vendor_id', $vendor->id)->where('status', 'active')->count(),
            'total_contract_value'     => Contract::where('vendor_id', $vendor->id)->where('status', 'active')->sum('contract_value'),
            'open_tenders'             => Tender::where('status', 'published')->whereDate('submission_deadline', '>=', now()->toDateString())->count(),
        ]);
    }

    // ==================== DATA ENDPOINTS ====================

    public function getTendersData()
    {
        $vendor            = Auth::user()->vendor;
        $vendorCategoryIds = $vendor->categories()->pluck('categories.id');

        $query = Tender::where('status', 'published')->whereDate('submission_deadline', '>=', now()->toDateString());
        if ($vendorCategoryIds->isNotEmpty()) {
            $query->where(function ($q) use ($vendorCategoryIds) {
                $q->whereHas('categories', fn($q2) => $q2->whereIn('categories.id', $vendorCategoryIds))
                  ->orWhereDoesntHave('categories');
            });
        }
        $query->where(function ($q) use ($vendor) {
            $q->where('is_private', false)
              ->orWhere(function ($q2) use ($vendor) {
                  $q2->where('is_private', true)->whereJsonContains('notify_specific_vendors', $vendor->id);
              });
        });
        return response()->json(['data' => $query->orderBy('submission_deadline')->get()]);
    }

    public function getApplicationsData()
    {
        $vendor = Auth::user()->vendor;
        return response()->json([
            'data' => TenderApplication::where('vendor_id', $vendor->id)
                ->with('tender')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function getContractsData()
    {
        $vendor = Auth::user()->vendor;
        return response()->json([
            'data' => Contract::where('vendor_id', $vendor->id)
                ->with('tender')->orderBy('created_at', 'desc')->get(),
        ]);
    }

    // ==================== DOWNLOADS ====================

    public function downloadTenderDocument($id, $documentId)
    {
        $document = TenderDocument::where('tender_id', $id)->findOrFail($documentId);
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'Document file not found.');
        }
        return Storage::disk('public')->download($document->file_path, $document->file_name ?? 'document');
    }

    public function downloadDocument($applicationId, $documentIndex)
    {
        $vendor      = Auth::user()->vendor;
        $application = TenderApplication::where('vendor_id', $vendor->id)->findOrFail($applicationId);
        $documents   = $application->documents ?? [];

        if (!isset($documents[$documentIndex])) {
            abort(404);
        }
        $doc  = $documents[$documentIndex];
        $path = $doc['path'] ?? null;
        $name = $doc['name'] ?? 'document';

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Document file not found.');
        }
        return Storage::disk('public')->download($path, $name);
    }

    public function downloadContract($id)
    {
        $vendor   = Auth::user()->vendor;
        $contract = Contract::where('vendor_id', $vendor->id)->findOrFail($id);

        $docs = $contract->contract_documents ?? [];
        if (empty($docs)) {
            return redirect()->route('vendor.contracts.show', $id)
                ->with('info', 'No downloadable contract document is available yet.');
        }
        $first = $docs[0] ?? null;
        $path  = is_array($first) ? ($first['path'] ?? null) : $first;

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'Contract file not found.');
        }
        return Storage::disk('public')->download($path, 'Contract_' . $contract->contract_number . '.pdf');
    }

    public function downloadProfileDocument($type)
    {
        $vendor    = Auth::user()->vendor;
        $documents = $vendor->registration_documents ?? [];

        if (!isset($documents[$type])) {
            abort(404);
        }
        $path = $documents[$type];
        if (!Storage::disk('public')->exists($path)) {
            abort(404, 'Document file not found.');
        }
        return Storage::disk('public')->download($path, $type . '_document');
    }

    // ==================== BANK DETAILS ====================

    public function updateBankDetails(Request $request)
    {
        $request->validate([
            'bank_name'           => 'required|string|max:100',
            'account_number'      => 'required|string|max:30',
            'ifsc_code'           => 'required|string|max:20',
            'account_holder_name' => 'required|string|max:100',
        ]);

        Auth::user()->vendor->update([
            'bank_details' => [
                'bank_name'           => $request->bank_name,
                'account_number'      => $request->account_number,
                'ifsc_code'           => strtoupper($request->ifsc_code),
                'account_holder_name' => $request->account_holder_name,
            ],
        ]);

        return back()->with('success', 'Bank details updated successfully.');
    }

    // ==================== NOTIFICATIONS ====================

    public function notifications()
    {
        $notifications = Auth::user()->notifications()->latest()->paginate(20);
        return view('vendor.notifications.index', compact('notifications'));
    }

    public function markNotificationRead($id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if ($notification) $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }
}
