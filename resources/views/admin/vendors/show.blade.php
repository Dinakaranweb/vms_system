<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-start">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">{{ $vendor->company_name }}</h2>
            <p class="text-gray-500">Vendor Code: {{ $vendor->vendor_code }}</p>
        </div>
        <div>
            @if($vendor->status == 'pending')
                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">Pending Approval</span>
            @elseif($vendor->status == 'approved')
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm">Approved</span>
            @elseif($vendor->status == 'rejected')
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm">Rejected</span>
            @elseif($vendor->status == 'suspended')
                <span class="px-3 py-1 bg-orange-100 text-orange-800 rounded-full text-sm">Suspended</span>
            @endif
        </div>
    </div>
    
    <!-- Company Information -->
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Company Information</h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-gray-500">Company Name</label>
                <p class="font-medium text-gray-900">{{ $vendor->company_name }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Vendor Code</label>
                <p class="font-medium text-gray-900">{{ $vendor->vendor_code }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Email</label>
                <p class="font-medium text-gray-900">{{ $vendor->email }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Phone</label>
                <p class="font-medium text-gray-900">{{ $vendor->phone }}</p>
            </div>
            <div class="col-span-2">
                <label class="text-sm text-gray-500">Address</label>
                <p class="font-medium text-gray-900">{{ $vendor->address }}, {{ $vendor->city }}, {{ $vendor->state }} - {{ $vendor->pincode }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">PAN Number</label>
                <p class="font-medium text-gray-900">{{ $vendor->pan_number }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">GST Number</label>
                <p class="font-medium text-gray-900">{{ $vendor->gst_number ?? 'N/A' }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Registered On</label>
                <p class="font-medium text-gray-900">{{ $vendor->created_at->format('d M, Y H:i') }}</p>
            </div>
            <div>
                <label class="text-sm text-gray-500">Approved On</label>
                <p class="font-medium text-gray-900">{{ $vendor->approved_at ? $vendor->approved_at->format('d M, Y H:i') : 'Not yet' }}</p>
            </div>
            @if($vendor->rejection_reason)
            <div class="col-span-2">
                <label class="text-sm text-gray-500">Rejection/Suspension Reason</label>
                <p class="font-medium text-red-600">{{ $vendor->rejection_reason }}</p>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $stats['total_applications'] }}</p>
            <p class="text-sm text-gray-600">Total Applications</p>
        </div>
        <div class="bg-yellow-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['submitted_applications'] }}</p>
            <p class="text-sm text-gray-600">Submitted</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-green-600">{{ $stats['shortlisted_applications'] }}</p>
            <p class="text-sm text-gray-600">Shortlisted</p>
        </div>
        <div class="bg-purple-50 rounded-lg p-3 text-center">
            <p class="text-2xl font-bold text-purple-600">{{ $stats['awarded_applications'] }}</p>
            <p class="text-sm text-gray-600">Awarded</p>
        </div>
    </div>
    
    <!-- Documents -->
    @if($vendor->registration_documents && count($vendor->registration_documents) > 0)
    <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Uploaded Documents</h3>
        <div class="space-y-2">
            @foreach($vendor->registration_documents as $type => $path)
            <div class="flex justify-between items-center">
                <span class="text-gray-700">{{ str_replace('_', ' ', ucfirst($type)) }}</span>
                <a href="{{ Storage::url($path) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-download mr-1"></i> Download
                </a>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>