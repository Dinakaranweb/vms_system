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

class VendorController extends Controller
{
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

        return view('vendor.dashboard', compact('stats', 'openTenders', 'privateTendersAssigned', 'recentApplications', 'recentContracts'));
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

        $tenders = $query->orderBy('submission_deadline', 'asc')->paginate(10);
        return view('vendor.tenders.index', compact('tenders'));
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

        return response()->json(['eligible' => true]);
    }

    // ==================== SUBMIT APPLICATION ====================

    public function submitApplication(Request $request, $id)
    {
        $tender = Tender::with('eligibilityQuestions')->findOrFail($id);
        $vendor  = Auth::user()->vendor;

        $rules = [];
        $rules['bid_amount']         = ($tender->require_bid_amount    ?? true) ? 'required|numeric|min:0'  : 'nullable|numeric|min:0';
        $rules['technical_proposal'] = ($tender->require_technical_bid ?? true) ? 'required|string'         : 'nullable|string';
        $rules['financial_proposal'] = ($tender->require_financial_bid ?? true) ? 'required|string'         : 'nullable|string';
        $request->validate($rules);

        DB::beginTransaction();
        try {
            // Re-check eligibility server-side before creating the application
            $allEligible = true;
            foreach ($tender->eligibilityQuestions as $question) {
                if (!$question->determines_eligibility) {
                    continue;
                }
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

            // Handle online document uploads
            $uploadedDocuments = [];
            if ($tender->isOnline() && $request->hasFile('application_documents')) {
                foreach ($request->file('application_documents') as $file) {
                    $path = $file->store('application-documents/' . $tender->id, 'public');
                    $uploadedDocuments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => $path,
                        'size' => $file->getSize(),
                    ];
                }
            }

            $application = TenderApplication::create([
                'tender_id'           => $tender->id,
                'vendor_id'           => $vendor->id,
                'bid_amount'          => $request->bid_amount,
                'technical_proposal'  => $request->technical_proposal,
                'financial_proposal'  => $request->financial_proposal,
                'documents'           => $uploadedDocuments ?: null,
                'status'              => 'submitted',
                'submitted_by'        => Auth::id(),
                'submitted_at'        => now(),
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
                    'is_eligible'           => true,
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

            if ($tender->isPostal()) {
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

        $eligibleOptions = $question->eligible_options ?? [];

        if (empty($eligibleOptions)) {
            return true;
        }

        switch ($question->field_type) {
            case 'checkbox':
                $selected = is_array($answer) ? $answer : (json_decode($answer, true) ?? [$answer]);
                return count(array_intersect($selected, $eligibleOptions)) > 0;

            case 'select':
            case 'radio':
                return in_array($answer, $eligibleOptions);

            case 'number':
                foreach ($eligibleOptions as $condition) {
                    if (preg_match('/^(>=|<=|>|<|=)(.+)$/', trim($condition), $m)) {
                        $val = (float) $m[2];
                        $num = (float) $answer;
                        $pass = match ($m[1]) {
                            '>='    => $num >= $val,
                            '<='    => $num <= $val,
                            '>'     => $num > $val,
                            '<'     => $num < $val,
                            default => $num == $val,
                        };
                        if ($pass) return true;
                    } elseif ((float) $answer == (float) $condition) {
                        return true;
                    }
                }
                return false;

            case 'text':
            case 'textarea':
            case 'date':
            default:
                foreach ($eligibleOptions as $option) {
                    if (strcasecmp(trim((string) $answer), trim((string) $option)) === 0) {
                        return true;
                    }
                }
                return false;
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
            ->with(['tender', 'tender.documents'])->findOrFail($id);
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
        $contract = Contract::where('vendor_id', $vendor->id)->with('tender')->findOrFail($id);
        return view('vendor.contracts.show', compact('contract'));
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
        return view('vendor.notifications.index');
    }

    public function markNotificationRead($id)
    {
        return response()->json(['success' => true]);
    }

    public function markAllNotificationsRead()
    {
        return response()->json(['success' => true]);
    }
}
