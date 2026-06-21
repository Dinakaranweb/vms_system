@extends('layouts.dashboard')

@section('title', 'Masters — Terms & Conditions')
@section('header', 'Masters')

@section('content')
@php $rp = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp

<div class="max-w-6xl mx-auto space-y-5">

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-purple-600 to-indigo-500 rounded-xl flex items-center justify-center text-white shadow-sm flex-shrink-0">
                <i class="fas fa-gavel text-sm"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Terms &amp; Conditions</h2>
                <p class="text-xs text-gray-500">Preset clauses for Work Orders &amp; Purchase Orders</p>
            </div>
        </div>
        <button onclick="openAddModal()"
            class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition shadow-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> Add Term
        </button>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm flex items-center gap-2">
        <i class="fas fa-check-circle text-green-500"></i> {{ session('success') }}
    </div>
    @endif

    {{-- Filter tabs --}}
    @php
        $filter = request('type', 'all');
        $groups = ['all'=>'All', 'work_order'=>'Work Order', 'purchase_order'=>'Purchase Order', 'both'=>'Shared (Both)'];
    @endphp
    <div class="flex gap-2 flex-wrap">
        @foreach($groups as $key => $label)
        <a href="?type={{ $key }}"
            class="px-4 py-1.5 rounded-full text-sm font-medium transition
            {{ $filter === $key ? 'bg-purple-600 text-white shadow-sm' : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
            {{ $label }}
            <span class="ml-1 text-xs opacity-75">
                ({{ $terms->when($key!=='all', fn($q)=>$q->where('applicable_to',$key))->count() }})
            </span>
        </a>
        @endforeach
    </div>

    {{-- Terms table --}}
    @php
        $filtered = $filter === 'all' ? $terms : $terms->where('applicable_to', $filter);
        $typeLabels = ['work_order'=>['Work Order','bg-blue-100 text-blue-700'],'purchase_order'=>['Purchase Order','bg-amber-100 text-amber-700'],'both'=>['Both','bg-purple-100 text-purple-700']];
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($filtered->isEmpty())
        <div class="py-16 text-center text-gray-400">
            <i class="fas fa-scroll text-4xl text-gray-200 block mb-3"></i>
            No terms found for this filter.
        </div>
        @else
        <div class="divide-y divide-gray-50">
            @foreach($filtered->sortBy([['applicable_to','asc'],['sort_order','asc']]) as $term)
            <div class="px-5 py-4 hover:bg-gray-50/60 transition group" id="term_{{ $term->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        {{-- Sort order badge --}}
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-gray-100 text-gray-500 text-xs flex items-center justify-center font-semibold mt-0.5">
                            {{ $term->sort_order ?: '—' }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="font-semibold text-gray-900 text-sm">{{ $term->title }}</span>
                                @php [$tl, $tc] = $typeLabels[$term->applicable_to]; @endphp
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $tc }}">{{ $tl }}</span>
                                @if(!$term->is_active)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-400">Inactive</span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $term->content }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition flex-shrink-0">
                        {{-- Toggle active --}}
                        <button onclick="toggleTerm({{ $term->id }}, this)"
                            class="p-1.5 rounded-lg text-sm transition {{ $term->is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100' }}"
                            title="{{ $term->is_active ? 'Deactivate' : 'Activate' }}">
                            <i class="fas {{ $term->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                        <button onclick="openEditModal({{ $term->id }}, '{{ addslashes($term->title) }}', {{ json_encode($term->content) }}, '{{ $term->applicable_to }}', {{ $term->sort_order }})"
                            class="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50 transition text-sm" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" action="{{ route($rp.'.masters.terms.delete', $term) }}"
                            onsubmit="return confirm('Delete this term?')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-1.5 rounded-lg text-red-400 hover:bg-red-50 transition text-sm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- ADD Modal --}}
<div id="addModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none!important;">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">Add Term / Clause</h3>
            <button onclick="closeModal('addModal')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form method="POST" action="{{ route($rp.'.masters.terms.store') }}" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Title / Heading <span class="text-red-500">*</span></label>
                <input type="text" name="title" required placeholder="e.g. Payment Mode"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Content <span class="text-red-500">*</span></label>
                <textarea name="content" required rows="4" placeholder="Enter the clause text…"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent resize-y"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Applies To <span class="text-red-500">*</span></label>
                    <select name="applicable_to" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                        <option value="both">Both (Work &amp; Purchase)</option>
                        <option value="work_order">Work Order only</option>
                        <option value="purchase_order">Purchase Order only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" checked class="rounded accent-purple-600">
                Active (shown as preset in work orders)
            </label>
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-50">
                <button type="button" onclick="closeModal('addModal')" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition font-medium">
                    <i class="fas fa-save mr-1"></i> Save Term
                </button>
            </div>
        </form>
    </div>
</div>

{{-- EDIT Modal --}}
<div id="editModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display:none!important;">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-900">Edit Term / Clause</h3>
            <button onclick="closeModal('editModal')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>
        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Title / Heading <span class="text-red-500">*</span></label>
                <input type="text" name="title" id="editTitle" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Content <span class="text-red-500">*</span></label>
                <textarea name="content" id="editContent" required rows="4"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400 focus:border-transparent resize-y"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Applies To <span class="text-red-500">*</span></label>
                    <select name="applicable_to" id="editApplicableTo" required
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                        <option value="both">Both (Work &amp; Purchase)</option>
                        <option value="work_order">Work Order only</option>
                        <option value="purchase_order">Purchase Order only</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" id="editSortOrder" min="0"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-400">
                </div>
            </div>
            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                <input type="checkbox" name="is_active" id="editIsActive" value="1" class="rounded accent-purple-600">
                Active (shown as preset in work orders)
            </label>
            <div class="flex justify-end gap-3 pt-2 border-t border-gray-50">
                <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancel</button>
                <button type="submit" class="px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium">
                    <i class="fas fa-save mr-1"></i> Update Term
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const rolePrefix = '{{ $rp }}';

function openAddModal() {
    document.getElementById('addModal').style.removeProperty('display');
}
function openEditModal(id, title, content, applicableTo, sortOrder) {
    document.getElementById('editTitle').value       = title;
    document.getElementById('editContent').value     = content;
    document.getElementById('editApplicableTo').value = applicableTo;
    document.getElementById('editSortOrder').value   = sortOrder;
    // active status: check if the row has Inactive badge (simple approach: always check, user can uncheck)
    document.getElementById('editIsActive').checked = true;
    document.getElementById('editForm').action = `/${rolePrefix}/masters/terms-conditions/${id}`;
    document.getElementById('editModal').style.removeProperty('display');
}
function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

function toggleTerm(id, btn) {
    fetch(`/${rolePrefix}/masters/terms-conditions/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            const isActive = d.is_active;
            btn.className = `p-1.5 rounded-lg text-sm transition ${isActive ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100'}`;
            btn.querySelector('i').className = `fas ${isActive ? 'fa-toggle-on' : 'fa-toggle-off'}`;
            btn.title = isActive ? 'Deactivate' : 'Activate';
            // Update inactive badge in the row
            const row = document.getElementById('term_' + id);
            const badge = row.querySelector('.badge-inactive');
            if (!isActive && !badge) {
                // Could add badge but a reload is simpler
            }
        }
    });
}

// Close modals on backdrop click
['addModal','editModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});
</script>
@endsection
