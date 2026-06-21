@extends('layouts.dashboard')

@section('title', 'Company Profile')
@section('header', 'Company Profile')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Company Information -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
                <h3 class="text-lg font-semibold text-white">Company Information</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('vendor.profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                            <input type="text" name="company_name" value="{{ old('company_name', $vendor->company_name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person Name *</label>
                            <input type="text" name="user_name" value="{{ old('user_name', auth()->user()->name) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Vendor Code</label>
                            <input type="text" value="{{ $vendor->vendor_code }}" class="w-full px-3 py-2 bg-gray-100 rounded-lg" readonly disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" value="{{ $vendor->email }}" class="w-full px-3 py-2 bg-gray-100 rounded-lg" readonly disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                            <input type="text" name="phone" value="{{ old('phone', $vendor->phone) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                            <textarea name="address" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('address', $vendor->address) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                            <input type="text" name="city" value="{{ old('city', $vendor->city) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">State *</label>
                            <input type="text" name="state" value="{{ old('state', $vendor->state) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Pincode *</label>
                            <input type="text" name="pincode" value="{{ old('pincode', $vendor->pincode) }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">PAN Number</label>
                            <input type="text" value="{{ $vendor->pan_number }}" class="w-full px-3 py-2 bg-gray-100 rounded-lg" readonly disabled>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">GST Number</label>
                            <input type="text" value="{{ $vendor->gst_number ?? 'N/A' }}" class="w-full px-3 py-2 bg-gray-100 rounded-lg" readonly disabled>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Bank Details -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600">
                <h3 class="text-lg font-semibold text-white">Bank Details</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('vendor.profile.bank-details') }}" method="POST">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Name</label>
                            <input type="text" name="bank_name" value="{{ old('bank_name', $vendor->bank_details['bank_name'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Number</label>
                            <input type="text" name="account_number" value="{{ old('account_number', $vendor->bank_details['account_number'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">IFSC Code</label>
                            <input type="text" name="ifsc_code" value="{{ old('ifsc_code', $vendor->bank_details['ifsc_code'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name</label>
                            <input type="text" name="account_holder_name" value="{{ old('account_holder_name', $vendor->bank_details['account_holder_name'] ?? '') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                            Save Bank Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Documents Section -->
    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-600 to-pink-600">
                <h3 class="text-lg font-semibold text-white">Upload Documents</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('vendor.profile.upload-document') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Document Type</label>
                        <select name="type" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="registration_certificate">Registration Certificate</option>
                            <option value="pan_document">PAN Document</option>
                            <option value="gst_certificate">GST Certificate</option>
                            <option value="msme_certificate">MSME Certificate</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upload Document</label>
                        <input type="file" name="document" required accept=".pdf,.jpg,.jpeg,.png" class="w-full">
                        <p class="text-xs text-gray-500 mt-1">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                    </div>
                    <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                        Upload Document
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Uploaded Documents List -->
        @if($vendor->registration_documents && count($vendor->registration_documents) > 0)
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="font-semibold text-gray-800">Uploaded Documents</h3>
            </div>
            <div class="divide-y">
                @foreach($vendor->registration_documents as $type => $path)
                <div class="p-4 flex justify-between items-center">
                    <div>
                        <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                        <span class="text-gray-700">{{ str_replace('_', ' ', ucfirst($type)) }}</span>
                    </div>
                    <div class="space-x-2">
                        <a href="{{ Storage::url($path) }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-download"></i>
                        </a>
                        <button onclick="deleteDocument('{{ $type }}')" class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<script>
function deleteDocument(type) {
    if (confirm('Are you sure you want to delete this document?')) {
        fetch(`/vendor/profile/delete-document/${type}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        }).then(() => location.reload());
    }
}
</script>
@endsection