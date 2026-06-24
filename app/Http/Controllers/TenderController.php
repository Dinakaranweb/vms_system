<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\TenderDocument;
use App\Models\EligibilityQuestion;
use App\Models\Category;
use App\Mail\TenderPublishedMail;
use App\Mail\TenderClosedMail;
use App\Models\TenderApplication;
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
    public function index()
{
    $user = auth()->user();
    
    // Get accessible tenders based on user role
    if ($user->hasRole('super-admin') || $user->hasRole('admin')) {
        $tenders = Tender::with(['creator', 'owner', 'categories'])->orderBy('created_at', 'desc')->paginate(10);
    }
    else if ($user->hasRole('tender-owner')) {
        $tenders = Tender::where('owner_id', $user->id)
            ->with(['creator', 'owner', 'categories'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
    else if ($user->isCommitteeMemberForAnyTender()) {
        $tenderIds = $user->committeeTenders()
            ->wherePivot('is_active', true)
            ->pluck('tender_id');
        $tenders = Tender::whereIn('id', $tenderIds)
            ->with(['creator', 'owner', 'categories'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }
    else {
        // Regular users (vendors) see only published tenders
        $tenders = Tender::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
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
            'submission_deadline' => 'required|date',
            'opening_date' => 'required|date|after:submission_deadline',
            'tender_type' => 'required|in:online,postal',
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
                'estimated_value' => $validated['estimated_value'] ?? null,
                'submission_deadline' => $validated['submission_deadline'],
                'opening_date' => $validated['opening_date'],
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
            ]);

            // Save eligibility questions
            if ($request->has('questions') && is_array($request->questions)) {
                foreach ($request->questions as $questionData) {
                    if (!empty($questionData['question'])) {
                        EligibilityQuestion::create([
                            'tender_id' => $tender->id,
                            'question' => $questionData['question'],
                            'description' => $questionData['description'] ?? null,
                            'field_type' => $questionData['field_type'] ?? 'text',
                            'options' => isset($questionData['options']) && !empty($questionData['options']) ? array_map('trim', explode(',', $questionData['options'])) : null,
                            'eligible_options' => isset($questionData['eligible_options']) && !empty($questionData['eligible_options']) ? array_map('trim', explode(',', $questionData['eligible_options'])) : null,
                            'is_required' => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                            'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                            'max_size' => $questionData['max_size'] ?? 5120,
                            'order' => $tender->eligibilityQuestions()->count() + 1,
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
    
    if (request()->ajax()) {
        return view('admin.tenders.show', compact('tender', 'stats'));
    }
    
    return view('admin.tenders.show', compact('tender', 'stats'));
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
            'submission_deadline'  => 'required|date',
            'opening_date'         => 'required|date',
            'tender_type'          => 'required|in:online,postal',
            'postal_address'       => 'required_if:tender_type,postal|nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $tender->update(array_merge($validated, [
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
            ]));

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
           // Handle new eligibility questions
if ($request->has('new_questions')) {
    foreach ($request->new_questions as $questionData) {
        if (!empty($questionData['question'])) {
            EligibilityQuestion::create([
                'tender_id' => $tender->id,
                'question' => $questionData['question'],
                'description' => $questionData['description'] ?? null,
                'field_type' => $questionData['field_type'] ?? 'text',
                'options' => isset($questionData['options']) && !empty($questionData['options']) ? array_map('trim', explode(',', $questionData['options'])) : null,
                'eligible_options' => isset($questionData['eligible_options']) && !empty($questionData['eligible_options']) ? array_map('trim', explode(',', $questionData['eligible_options'])) : null,
                'is_required' => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                'max_size' => $questionData['max_size'] ?? 5120,
                'order' => $tender->eligibilityQuestions()->count() + 1,
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
        $question->update([
            'question' => $request->question,
            'description' => $request->description,
            'field_type' => $request->field_type,
            'is_required' => $request->is_required,
            'determines_eligibility' => $request->determines_eligibility,
            'options' => $request->options ? array_map('trim', explode(',', $request->options)) : null,
            'eligible_options' => $request->eligible_options ? array_map('trim', explode(',', $request->eligible_options)) : null,
            'max_size' => $request->max_size,
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
            
            $question = EligibilityQuestion::create([
                'tender_id' => $tender->id,
                'question' => $request->question,
                'description' => $request->description,
                'field_type' => $request->field_type,
                'is_required' => $request->is_required,
                'determines_eligibility' => $request->determines_eligibility ?? false,
                'options' => $request->options ? array_map('trim', explode(',', $request->options)) : null,
                'eligible_options' => $request->eligible_options ? array_map('trim', explode(',', $request->eligible_options)) : null,
                'max_size' => $request->max_size,
                'order' => $tender->eligibilityQuestions()->count() + 1,
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
public function ownerIndex()
{
    $tenders = auth()->user()->ownedTenders()
        ->with(['categories', 'committeeMembers'])
        ->withCount('applications')
        ->latest()
        ->paginate(10);
    
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
        'submission_deadline' => 'required|date',
        'opening_date' => 'required|date',
        'tender_type' => 'required|in:online,postal',
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
            'estimated_value' => $validated['estimated_value'] ?? null,
            'emda_amount'     => $request->filled('emda_amount') ? $request->emda_amount : null,
            'submission_deadline' => $validated['submission_deadline'],
            'opening_date' => $validated['opening_date'],
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
        ]);

        \Log::info('Tender Created - ID: ' . $tender->id . ', Owner ID: ' . $tender->owner_id);

        // Save eligibility questions
        // Save eligibility questions
if ($request->has('questions') && is_array($request->questions)) {
    foreach ($request->questions as $questionData) {
        if (!empty($questionData['question'])) {
            EligibilityQuestion::create([
                'tender_id' => $tender->id,
                'question' => $questionData['question'],
                'description' => $questionData['description'] ?? null,
                'field_type' => $questionData['field_type'] ?? 'text',
                'options' => isset($questionData['options']) && !empty($questionData['options']) ? array_map('trim', explode(',', $questionData['options'])) : null,
                'eligible_options' => isset($questionData['eligible_options']) && !empty($questionData['eligible_options']) ? array_map('trim', explode(',', $questionData['eligible_options'])) : null,
                'is_required' => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                'max_size' => $questionData['max_size'] ?? 5120,
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
        'submission_deadline'  => 'required|date',
        'opening_date'         => 'required|date',
        'tender_type'          => 'required|in:online,postal',
        'postal_address'       => 'required_if:tender_type,postal|nullable|string',
    ]);

    DB::beginTransaction();

    try {
        $privateVendors = [];
        if ($request->filled('notify_specific_vendors')) {
            $privateVendors = array_values(array_filter(array_map('intval', explode(',', $request->notify_specific_vendors))));
        }

        $validated['eligibility_criteria'] = $validated['eligibility_criteria'] ?? '';

        $tender->update(array_merge($validated, [
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
        ]));

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
                    EligibilityQuestion::create([
                        'tender_id' => $tender->id,
                        'question' => $questionData['question'],
                        'description' => $questionData['description'] ?? null,
                        'field_type' => $questionData['field_type'] ?? 'text',
                        'options' => isset($questionData['options']) && !empty($questionData['options']) ? array_map('trim', explode(',', $questionData['options'])) : null,
                        'eligible_options' => isset($questionData['eligible_options']) && !empty($questionData['eligible_options']) ? array_map('trim', explode(',', $questionData['eligible_options'])) : null,
                        'is_required' => isset($questionData['is_required']) && $questionData['is_required'] == '1',
                        'determines_eligibility' => isset($questionData['determines_eligibility']) && $questionData['determines_eligibility'] == '1',
                        'max_size' => $questionData['max_size'] ?? 5120,
                        'order' => $tender->eligibilityQuestions()->count() + 1,
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