@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-900 via-purple-900 to-pink-800 flex items-center justify-center p-4">
    <div class="max-w-3xl w-full">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 backdrop-blur-lg rounded-2xl mb-4">
                <i class="fas fa-user-plus text-white text-3xl"></i>
            </div>
            <h2 class="text-3xl font-bold text-white">Vendor Registration</h2>
            <p class="text-gray-300 mt-2">Register your company to participate in tenders</p>
        </div>
        
        <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-xl p-8 border border-white/20">
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-500/20 border border-red-400/50 rounded-lg text-red-200">
                    @foreach($errors->all() as $error)
                        <p>• {{ $error }}</p>
                    @endforeach
                </div>
            @endif
            
            <form method="POST" action="/register" enctype="multipart/form-data">
                @csrf
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-white font-semibold mb-4 pb-2 border-b border-white/20">
                            <i class="fas fa-user mr-2"></i>Personal Information
                        </h3>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Full Name *</label>
                        <input type="text" name="name" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Email *</label>
                        <input type="email" name="email" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Password *</label>
                        <input type="password" name="password" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Confirm Password *</label>
                        <input type="password" name="password_confirmation" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <!-- Company Information -->
                    <div class="md:col-span-2">
                        <h3 class="text-white font-semibold mb-4 pt-4 pb-2 border-b border-white/20">
                            <i class="fas fa-building mr-2"></i>Company Information
                        </h3>
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-300 text-sm font-medium mb-2">Company Name *</label>
                        <input type="text" name="company_name" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Phone *</label>
                        <input type="text" name="phone" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">PAN Number *</label>
                        <input type="text" name="pan_number" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">GST Number</label>
                        <input type="text" name="gst_number" class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">City *</label>
                        <input type="text" name="city" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">State *</label>
                        <input type="text" name="state" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Pincode *</label>
                        <input type="text" name="pincode" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-gray-300 text-sm font-medium mb-2">Address *</label>
                        <textarea name="address" rows="2" required class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white"></textarea>
                    </div>
                    
                    <!-- Documents -->
                    <div class="md:col-span-2">
                        <h3 class="text-white font-semibold mb-4 pt-4 pb-2 border-b border-white/20">
                            <i class="fas fa-file-alt mr-2"></i>Supporting Documents
                        </h3>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">Registration Certificate (PDF/Image) *</label>
                        <input type="file" name="registration_certificate" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-white">
                        <p class="text-gray-400 text-xs mt-1">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                    </div>

                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2">PAN Document (PDF/Image) *</label>
                        <input type="file" name="pan_document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-white">
                        <p class="text-gray-400 text-xs mt-1">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                    </div>
                </div>
                
                <button type="submit" class="w-full mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Registration
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-300">
                    Already have an account?
                    <a href="/login" class="text-blue-400 hover:text-blue-300">Sign In</a>
                </p>
            </div>
        </div>
        <div class="text-center mt-8">
            <p class="text-gray-400 text-sm">&copy; 2026 VMS Portal. All rights reserved. | Designed & Developed by <a href="https://vmrfdu.edu.in" class="text-blue-400 hover:text-blue-300">VMRF(DU) IT Team</a></p>
        </div>
    </div>
    
</div>
@endsection