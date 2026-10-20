<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use Illuminate\Http\Request;

class PublicTenderApiController extends Controller
{
    /**
     * Public API — returns published/active tenders, excludes awarded ones.
     * No authentication required. Intended for external website consumption.
     *
     * GET /api/tenders
     * Query params:
     *   ?limit=20       (default 20, max 100)
     *   ?search=keyword (optional title/number search)
     *   ?type=online|postal  (optional filter)
     */
    public function index(Request $request)
    {
        $limit  = min((int) ($request->query('limit', 20)), 100);
        $search = $request->query('search');
        $type   = $request->query('type');

        $query = Tender::whereIn('status', ['published', 'closed'])
            // Exclude tenders that have an active/awarded contract
            ->whereDoesntHave('contract', fn($q) => $q->whereIn('status', ['active', 'awarded']))
            // Also exclude tenders where an application was marked as awarded
            ->whereDoesntHave('awardedApplication')
            ->with(['documents' => function ($q) {
                $q->where('is_public', true)->select('id', 'tender_id', 'title', 'file_name', 'file_path', 'file_size', 'file_type');
            }]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('tender_number', 'like', "%{$search}%");
            });
        }

        if ($type && in_array($type, ['online', 'postal', 'hybrid'])) {
            $query->where('tender_type', $type);
        }

        $tenders = $query->orderBy('submission_deadline', 'asc')
                         ->limit($limit)
                         ->get();

        $data = $tenders->map(function (Tender $t) {
            $now = now();
            $deadline = \Carbon\Carbon::parse($t->submission_deadline);

            return [
                'id'                  => $t->id,
                'tender_number'       => $t->tender_number,
                'title'               => $t->title,
                'description'         => $t->description,
                'eligibility_criteria'=> $t->eligibility_criteria ?: null,
                'estimated_value'     => $t->estimated_value ? (float) $t->estimated_value : null,
                'emda_amount'         => $t->emda_amount ? (float) $t->emda_amount : null,
                'tender_type'         => $t->tender_type,
                'status'              => $t->status,
                'is_open'             => $deadline->isFuture() && $t->status === 'published',
                'published_date'      => $t->published_date?->format('Y-m-d'),
                'submission_deadline' => $t->submission_deadline?->format('Y-m-d'),
                'submission_time'     => $t->submission_time,
                'opening_date'        => $t->opening_date?->format('Y-m-d'),
                'opening_time'        => $t->opening_time,
                'opening_venue'       => $t->opening_venue,
                'postal_address'      => $t->postal_address,
                'days_remaining'      => max(0, (int) $now->diffInDays($deadline, false)),
                'documents'           => $t->documents->map(fn($d) => [
                    'id'        => $d->id,
                    'title'     => $d->title,
                    'file_name' => $d->file_name,
                    'file_size' => $d->file_size,
                    'file_type' => $d->file_type,
                    'url'       => url('storage/' . $d->file_path),
                ])->values(),
            ];
        });

        return response()->json([
            'success'    => true,
            'count'      => $data->count(),
            'generated'  => now()->toIso8601String(),
            'source'     => 'VMRF-DU Vendor Management System',
            'tenders'    => $data,
        ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type')
        ->header('Cache-Control', 'public, max-age=300');
    }

    /**
     * University website API — returns only tenders marked show_on_university=true.
     * GET /api/public/university/tenders
     * Query params: ?limit=20  ?search=keyword
     */
    public function universityTenders(Request $request)
    {
        $limit  = min((int) ($request->query('limit', 50)), 100);
        $search = $request->query('search');

        $query = Tender::where('status', 'published')
            ->where('show_on_university', true)
            ->where('submission_deadline', '>=', now()->toDateString());

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('tender_number', 'like', "%{$search}%");
            });
        }

        $tenders = $query->orderBy('submission_deadline', 'asc')
                         ->limit($limit)
                         ->get(['id', 'tender_number', 'title', 'university_description',
                                'tender_type', 'estimated_value', 'emda_amount',
                                'published_date', 'submission_deadline', 'submission_time',
                                'opening_date', 'opening_time', 'opening_venue',
                                'postal_address', 'status']);

        $now  = now();
        $data = $tenders->map(function (Tender $t) use ($now) {
            $deadline = \Carbon\Carbon::parse($t->submission_deadline);
            return [
                'id'                     => $t->id,
                'tender_number'          => $t->tender_number,
                'title'                  => $t->title,
                'university_description' => $t->university_description,
                'tender_type'            => $t->tender_type,
                'estimated_value'        => $t->estimated_value ? (float) $t->estimated_value : null,
                'emda_amount'            => $t->emda_amount ? (float) $t->emda_amount : null,
                'published_date'         => $t->published_date?->format('Y-m-d'),
                'submission_deadline'    => $t->submission_deadline?->format('Y-m-d'),
                'submission_time'        => $t->submission_time,
                'opening_date'           => $t->opening_date?->format('Y-m-d'),
                'opening_time'           => $t->opening_time,
                'opening_venue'          => $t->opening_venue,
                'postal_address'         => $t->postal_address,
                'days_remaining'         => max(0, (int) $now->diffInDays($deadline, false)),
                'is_open'                => $deadline->isFuture(),
            ];
        });

        return response()->json([
            'success'   => true,
            'count'     => $data->count(),
            'generated' => now()->toIso8601String(),
            'source'    => 'VMRF-DU Vendor Management System',
            'tenders'   => $data,
        ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type')
        ->header('Cache-Control', 'public, max-age=60');
    }

    /**
     * Single tender detail — public.
     * GET /api/tenders/{id}
     */
    public function show($id)
    {
        $tender = Tender::whereIn('status', ['published', 'closed'])
            ->whereDoesntHave('contract', fn($q) => $q->whereIn('status', ['active', 'awarded']))
            ->whereDoesntHave('awardedApplication')
            ->with(['documents' => fn($q) => $q->where('is_public', true)])
            ->findOrFail($id);

        // Track public/API view
        try {
            \App\Models\TenderView::create([
                'tender_id'  => $tender->id,
                'user_id'    => null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception) {}

        $now      = now();
        $deadline = \Carbon\Carbon::parse($tender->submission_deadline);

        return response()->json([
            'success' => true,
            'tender'  => [
                'id'                   => $tender->id,
                'tender_number'        => $tender->tender_number,
                'title'                => $tender->title,
                'description'          => $tender->description,
                'eligibility_criteria' => $tender->eligibility_criteria ?: null,
                'technical_requirements'  => $tender->technical_requirements,
                'financial_requirements'  => $tender->financial_requirements,
                'estimated_value'      => $tender->estimated_value ? (float) $tender->estimated_value : null,
                'emda_amount'          => $tender->emda_amount ? (float) $tender->emda_amount : null,
                'tender_type'          => $tender->tender_type,
                'status'               => $tender->status,
                'is_open'              => $deadline->isFuture() && $tender->status === 'published',
                'published_date'       => $tender->published_date?->format('Y-m-d'),
                'submission_deadline'  => $tender->submission_deadline?->format('Y-m-d'),
                'submission_time'      => $tender->submission_time,
                'opening_date'         => $tender->opening_date?->format('Y-m-d'),
                'opening_time'         => $tender->opening_time,
                'opening_venue'        => $tender->opening_venue,
                'postal_address'       => $tender->postal_address,
                'days_remaining'       => max(0, (int) $now->diffInDays($deadline, false)),
                'documents'            => $tender->documents->map(fn($d) => [
                    'id'        => $d->id,
                    'title'     => $d->title,
                    'file_name' => $d->file_name,
                    'file_size' => $d->file_size,
                    'file_type' => $d->file_type,
                    'url'       => url('storage/' . $d->file_path),
                ])->values(),
            ],
        ])
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Cache-Control', 'public, max-age=300');
    }
}
