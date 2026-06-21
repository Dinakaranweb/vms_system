<div>
    <label class="block text-xs font-semibold text-gray-600 mb-1">Committee Role *</label>
    <select name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
        <option value="member">Member</option>
        <option value="chairman">Chairman</option>
        <option value="observer">Observer</option>
        <option value="technical_expert">Technical Expert</option>
        <option value="financial_expert">Financial Expert</option>
    </select>
</div>
<div>
    <label class="block text-xs font-semibold text-gray-600 mb-1">Designation</label>
    <input type="text" name="designation" placeholder="e.g., Senior Engineer"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
</div>
<div>
    <label class="block text-xs font-semibold text-gray-600 mb-1">Permissions</label>
    <div class="space-y-1.5 bg-gray-50 rounded-lg p-3 border border-gray-200">
        @foreach([
            'view_applications' => 'View Applications',
            'shortlist_applications' => 'Shortlist Applications',
            'reject_applications' => 'Reject Applications',
            'award_applications' => 'Award Applications',
            'view_reports' => 'View Reports',
        ] as $val => $label)
        <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
            <input type="checkbox" name="permissions[]" value="{{ $val }}" class="rounded text-purple-600 focus:ring-purple-500">
            {{ $label }}
        </label>
        @endforeach
    </div>
</div>
