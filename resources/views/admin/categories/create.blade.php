@extends('layouts.dashboard')

@section('title', 'Create Category')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    {{-- Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.categories') }}"
           class="text-gray-400 hover:text-gray-600 transition">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Category</h1>
            <p class="text-sm text-gray-500 mt-0.5">Add a new category to group vendors and tenders.</p>
        </div>
    </div>

    {{-- Errors --}}
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <strong>Please fix the following errors:</strong>
            <ul class="mt-1 list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.categories.store') }}">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 divide-y divide-gray-50">

            {{-- Basic Details --}}
            <div class="px-6 py-5 space-y-4">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Basic Details</h2>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Category Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-400 @enderror"
                           placeholder="e.g. IT & Software">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                              placeholder="Brief description of this category...">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="color" value="{{ old('color', '#6366f1') }}" id="colorPicker"
                                   class="h-9 w-16 border border-gray-300 rounded-lg cursor-pointer p-0.5"
                                   oninput="document.getElementById('colorHex').value = this.value">
                            <input type="text" id="colorHex" value="{{ old('color', '#6366f1') }}"
                                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   oninput="document.getElementById('colorPicker').value = this.value; document.querySelector('[name=color]').value = this.value"
                                   placeholder="#6366f1">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Icon <span class="text-gray-400 text-xs">(Font Awesome)</span></label>
                        <div class="flex items-center gap-2">
                            <span class="w-9 h-9 border border-gray-300 rounded-lg flex items-center justify-center text-gray-600" id="iconPreview">
                                <i class="fas fa-tag" id="iconEl"></i>
                            </span>
                            <input type="text" name="icon" value="{{ old('icon', 'fa-tag') }}" id="iconInput"
                                   class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="fa-tag"
                                   oninput="document.getElementById('iconEl').className = 'fas ' + this.value">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Assign Vendors --}}
            <div class="px-6 py-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Assign Vendors</h2>
                    <span class="text-xs text-gray-400">Optional — select vendors belonging to this category</span>
                </div>

                @if($vendors->isEmpty())
                    <p class="text-sm text-gray-400 italic">No approved vendors found.</p>
                @else
                    <div class="mb-2">
                        <input type="text" id="vendorSearch" placeholder="Search vendors..."
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                               oninput="filterVendors(this.value)">
                    </div>
                    <div id="vendorList" class="border border-gray-200 rounded-lg divide-y divide-gray-100 max-h-60 overflow-y-auto">
                        @foreach($vendors as $vendor)
                        <label class="vendor-row flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer transition"
                               data-name="{{ strtolower($vendor->company_name) }}">
                            <input type="checkbox" name="vendor_ids[]" value="{{ $vendor->id }}"
                                   {{ in_array($vendor->id, old('vendor_ids', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium text-gray-900 block truncate">{{ $vendor->company_name }}</span>
                                <span class="text-xs text-gray-400">{{ $vendor->vendor_code }} · {{ $vendor->email }}</span>
                            </div>
                        </label>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400">
                        <span id="selectedCount">0</span> vendor(s) selected.
                        <button type="button" onclick="selectAll(true)" class="text-blue-600 hover:underline ml-1">All</button> /
                        <button type="button" onclick="selectAll(false)" class="text-blue-600 hover:underline">None</button>
                    </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 flex items-center justify-end gap-3 bg-gray-50 rounded-b-xl">
                <a href="{{ route('admin.categories') }}"
                   class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                    Create Category
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function filterVendors(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.vendor-row').forEach(row => {
        row.style.display = row.dataset.name.includes(q) ? '' : 'none';
    });
}
function selectAll(checked) {
    document.querySelectorAll('#vendorList input[type=checkbox]').forEach(cb => cb.checked = checked);
    updateCount();
}
function updateCount() {
    const n = document.querySelectorAll('#vendorList input[type=checkbox]:checked').length;
    document.getElementById('selectedCount').textContent = n;
}
document.querySelectorAll('#vendorList input[type=checkbox]').forEach(cb => cb.addEventListener('change', updateCount));
updateCount();
</script>
@endsection
