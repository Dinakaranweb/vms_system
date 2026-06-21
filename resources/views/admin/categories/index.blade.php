@extends('layouts.dashboard')

@section('title', 'Category Management')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
            <p class="text-sm text-gray-500 mt-1">Group vendors and tenders to control visibility and notifications.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-plus"></i> New Category
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($categories->isEmpty())
            <div class="text-center py-16 text-gray-400">
                <i class="fas fa-tags text-5xl mb-4 block"></i>
                <p class="text-lg font-medium">No categories yet</p>
                <p class="text-sm mt-1">Create your first category to group vendors and tenders.</p>
                <a href="{{ route('admin.categories.create') }}"
                   class="mt-4 inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    <i class="fas fa-plus"></i> Create Category
                </a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-gray-600 text-xs uppercase tracking-wide">
                        <th class="px-6 py-3 text-left">Category</th>
                        <th class="px-6 py-3 text-left">Description</th>
                        <th class="px-6 py-3 text-center">Vendors</th>
                        <th class="px-6 py-3 text-center">Tenders</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($categories as $category)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="w-9 h-9 rounded-lg flex items-center justify-center text-white text-sm flex-shrink-0"
                                      style="background-color: {{ $category->color }}">
                                    <i class="fas {{ $category->icon }}"></i>
                                </span>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $category->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $category->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-500 max-w-xs truncate">
                            {{ $category->description ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-1 rounded-full text-xs font-semibold">
                                <i class="fas fa-users text-xs"></i> {{ $category->vendors_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center gap-1 bg-indigo-50 text-indigo-700 px-2 py-1 rounded-full text-xs font-semibold">
                                <i class="fas fa-file-alt text-xs"></i> {{ $category->tenders_count }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($category->is_active)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-semibold">Active</span>
                            @else
                                <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-full text-xs font-semibold">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Toggle Active/Inactive --}}
                                <form method="POST" action="{{ route('admin.categories.toggle', $category->id) }}">
                                    @csrf
                                    <button type="submit"
                                            title="{{ $category->is_active ? 'Deactivate' : 'Activate' }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition
                                                   {{ $category->is_active
                                                       ? 'bg-green-50 text-green-700 hover:bg-green-100'
                                                       : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                        <i class="fas {{ $category->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </form>

                                {{-- Edit --}}
                                <a href="{{ route('admin.categories.edit', $category->id) }}"
                                   class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 hover:bg-blue-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                    <i class="fas fa-edit"></i> Edit
                                </a>

                                {{-- Delete --}}
                                <form method="POST" action="{{ route('admin.categories.delete', $category->id) }}"
                                      onsubmit="return confirm('Delete category \'{{ addslashes($category->name) }}\'? This will not delete associated vendors or tenders.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 bg-red-50 text-red-600 hover:bg-red-100 px-3 py-1.5 rounded-lg text-xs font-semibold transition">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
@endsection
