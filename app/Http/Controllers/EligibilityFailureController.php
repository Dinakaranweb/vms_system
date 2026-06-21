<?php

namespace App\Http\Controllers;

use App\Models\EligibilityFailure;
use App\Models\Tender;
use Illuminate\Http\Request;

class EligibilityFailureController extends Controller
{
    public function index(Request $request)
    {
        $tenders  = Tender::whereHas('eligibilityQuestions')->orderBy('title')->get(['id', 'title', 'tender_number']);
        $tenderId = $request->tender_id;
        return view('admin.eligibility_failures.index', compact('tenders', 'tenderId'));
    }

    public function data(Request $request)
    {
        try {
            $query = EligibilityFailure::with(['tender', 'vendor'])
                ->orderBy('created_at', 'desc');

            if ($request->tender_id) {
                $query->where('tender_id', $request->tender_id);
            }

            if ($request->search) {
                $search = $request->search;
                $query->whereHas('vendor', function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $failures = $query->paginate(20);

            return response()->json([
                'success'      => true,
                'data'         => $failures->items(),
                'total'        => $failures->total(),
                'current_page' => $failures->currentPage(),
                'last_page'    => $failures->lastPage(),
            ]);
        } catch (\Exception $e) {
            \Log::error('EligibilityFailure data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
