<div class="space-y-4">
    <div class="flex items-center space-x-4 pb-4 border-b">
        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center text-white text-2xl font-bold">
            {{ substr($user->name, 0, 2) }}
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $user->name }}</h2>
            <p class="text-gray-500">{{ $user->email }}</p>
            <div class="flex items-center space-x-2 mt-1">
                @foreach($user->getRoleNames() as $role)
                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded-full text-xs">{{ ucfirst($role) }}</span>
                @endforeach
                @if($user->is_active)
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                @else
                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Inactive</span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">User ID</label>
            <p class="font-semibold text-gray-900">{{ $user->id }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Registered On</label>
            <p class="text-gray-900">{{ $user->created_at->format('d M, Y H:i') }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Last Updated</label>
            <p class="text-gray-900">{{ $user->updated_at->format('d M, Y H:i') }}</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <label class="text-xs text-gray-500 uppercase">Last Login</label>
            <p class="text-gray-900">{{ $user->last_login_at ? $user->last_login_at->format('d M, Y H:i') : 'Never' }}</p>
        </div>
    </div>
    
    @if($user->vendor)
    <div class="bg-gray-50 rounded-lg p-3">
        <label class="text-xs text-gray-500 uppercase">Associated Vendor</label>
        <p class="font-semibold text-gray-900">{{ $user->vendor->company_name }}</p>
        <p class="text-sm text-gray-500">Vendor Code: {{ $user->vendor->vendor_code }}</p>
    </div>
    @endif
</div>