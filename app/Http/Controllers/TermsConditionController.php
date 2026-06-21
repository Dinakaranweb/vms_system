<?php

namespace App\Http\Controllers;

use App\Models\TermsCondition;
use Illuminate\Http\Request;

class TermsConditionController extends Controller
{
    public function index()
    {
        $terms = TermsCondition::orderBy('applicable_to')->orderBy('sort_order')->get();
        return view('admin.masters.terms_conditions.index', compact('terms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'content'       => 'required|string',
            'applicable_to' => 'required|in:work_order,purchase_order,both',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        TermsCondition::create($data);

        return back()->with('success', 'Term added successfully.');
    }

    public function update(Request $request, TermsCondition $termsCondition)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'content'       => 'required|string',
            'applicable_to' => 'required|in:work_order,purchase_order,both',
            'sort_order'    => 'nullable|integer|min:0',
            'is_active'     => 'boolean',
        ]);
        $data['is_active']  = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $termsCondition->update($data);

        return back()->with('success', 'Term updated successfully.');
    }

    public function destroy(TermsCondition $termsCondition)
    {
        $termsCondition->delete();
        return back()->with('success', 'Term deleted.');
    }

    public function toggle(TermsCondition $termsCondition)
    {
        $termsCondition->update(['is_active' => !$termsCondition->is_active]);
        return response()->json(['success' => true, 'is_active' => $termsCondition->is_active]);
    }
}
