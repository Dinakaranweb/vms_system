<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Vendor;
use App\Models\Tender;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount(['vendors', 'tenders'])
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $vendors = Vendor::where('status', 'approved')
            ->orderBy('company_name')
            ->get();

        return view('admin.categories.create', compact('vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
            'icon'        => 'nullable|string|max:60',
            'vendor_ids'  => 'nullable|array',
            'vendor_ids.*'=> 'exists:vendors,id',
        ]);

        $category = Category::create([
            'name'        => $request->name,
            'description' => $request->description,
            'color'       => $request->color ?? '#6366f1',
            'icon'        => $request->icon ?? 'fa-tag',
            'is_active'   => true,
            'created_by'  => auth()->id(),
        ]);

        if ($request->filled('vendor_ids')) {
            $category->vendors()->sync($request->vendor_ids);
        }

        return redirect()->route('admin.categories')
            ->with('success', 'Category "' . $category->name . '" created successfully.');
    }

    public function edit($id)
    {
        $category = Category::with('vendors')->findOrFail($id);

        $vendors = Vendor::where('status', 'approved')
            ->orderBy('company_name')
            ->get();

        $assignedVendorIds = $category->vendors->pluck('id')->toArray();

        return view('admin.categories.edit', compact('category', 'vendors', 'assignedVendorIds'));
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name'        => 'required|string|max:100|unique:categories,name,' . $id,
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
            'icon'        => 'nullable|string|max:60',
            'is_active'   => 'nullable|boolean',
            'vendor_ids'  => 'nullable|array',
            'vendor_ids.*'=> 'exists:vendors,id',
        ]);

        // Regenerate slug only if name changed
        $slug = $category->slug;
        if ($category->name !== $request->name) {
            $base = Str::slug($request->name);
            $slug = $base;
            $i = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug = $base . '-' . $i++;
            }
        }

        $category->update([
            'name'        => $request->name,
            'slug'        => $slug,
            'description' => $request->description,
            'color'       => $request->color ?? $category->color,
            'icon'        => $request->icon ?? $category->icon,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        $category->vendors()->sync($request->vendor_ids ?? []);

        return redirect()->route('admin.categories')
            ->with('success', 'Category "' . $category->name . '" updated successfully.');
    }

    public function toggle($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        $state = $category->is_active ? 'activated' : 'deactivated';
        return back()->with('success', 'Category "' . $category->name . '" ' . $state . '.');
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $name = $category->name;

        // Detach relationships before deleting
        $category->vendors()->detach();
        $category->tenders()->detach();
        $category->delete();

        return redirect()->route('admin.categories')
            ->with('success', 'Category "' . $name . '" deleted.');
    }

    /**
     * AJAX: return vendors assigned to a category (used in tender-owner context).
     */
    public function vendorsJson($id)
    {
        $category = Category::with('vendors:id,company_name,vendor_code,email')->findOrFail($id);
        return response()->json($category->vendors);
    }
}
