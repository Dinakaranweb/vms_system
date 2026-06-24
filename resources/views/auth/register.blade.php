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
                    
                    <div class="md:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer mb-3">
                            <input type="checkbox" name="has_gst" id="hasGstCheck" value="1"
                                   class="w-4 h-4 accent-blue-400"
                                   onchange="toggleGst(this.checked)">
                            <span class="text-gray-300 text-sm font-medium">I have a GST Number</span>
                        </label>
                        <div id="gstNumberBox" class="hidden space-y-3">
                            <div>
                                <label class="block text-gray-300 text-sm font-medium mb-1">GST Number <span class="text-red-400">*</span></label>
                                <input type="text" name="gst_number" id="gstNumberInput" placeholder="e.g. 29ABCDE1234F1Z5"
                                       class="w-full px-4 py-2 bg-white/5 border border-white/20 rounded-lg text-white">
                            </div>
                            <div>
                                <label class="block text-gray-300 text-sm font-medium mb-1">GST Certificate / Document <span class="text-red-400">*</span></label>
                                <input type="file" name="gst_document" id="gstDocInput" accept=".pdf,.jpg,.jpeg,.png"
                                       class="w-full text-white text-sm">
                                <p class="text-gray-400 text-xs mt-1">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                            </div>
                        </div>
                        <div id="certificateBox">
                            <p class="text-yellow-300 text-xs mb-2"><i class="fas fa-info-circle mr-1"></i>No GST? Please upload at least one supporting certificate (MSME, Udyam Aadhaar, etc.)</p>
                            <div id="certList">
                                <div class="cert-entry flex gap-2 mb-2">
                                    <select name="certificate_labels[0]" class="px-3 py-2 bg-gray-800 border border-white/20 rounded-lg text-white text-sm w-52">
                                        <option value="MSME Certificate">MSME Certificate</option>
                                        <option value="Udyam Aadhaar">Udyam Aadhaar</option>
                                        <option value="Trade License">Trade License</option>
                                        <option value="Registration Certificate">Registration Certificate</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <input type="file" name="certificates[0]" accept=".pdf,.jpg,.jpeg,.png"
                                           class="flex-1 text-white text-sm">
                                </div>
                            </div>
                            <button type="button" onclick="addCertRow()"
                                    class="text-blue-400 hover:text-blue-300 text-xs mt-1">
                                <i class="fas fa-plus mr-1"></i>Add another certificate
                            </button>
                        </div>
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
                        <label class="block text-gray-300 text-sm font-medium mb-2">PAN Document (PDF/Image) *</label>
                        <input type="file" name="pan_document" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-white">
                        <p class="text-gray-400 text-xs mt-1">Max size: 5MB. Allowed: PDF, JPG, PNG</p>
                    </div>
                </div>
                
                <button type="submit" class="w-full mt-8 bg-gradient-to-r from-blue-600 to-indigo-600 text-white py-3 rounded-lg font-medium hover:from-blue-700 hover:to-indigo-700 transition">
                    <i class="fas fa-paper-plane mr-2"></i>Submit Registration
                </button>
            </form>
            
            <script>
let certCount = 1;
function toggleGst(hasGst) {
    document.getElementById('gstNumberBox').classList.toggle('hidden', !hasGst);
    document.getElementById('certificateBox').classList.toggle('hidden', hasGst);
    // Toggle required on GST fields
    document.getElementById('gstNumberInput').required = hasGst;
    document.getElementById('gstDocInput').required = hasGst;
}
function addCertRow() {
    const list = document.getElementById('certList');
    const div = document.createElement('div');
    div.className = 'cert-entry flex gap-2 mb-2';
    div.innerHTML = `<select name="certificate_labels[${certCount}]" class="px-3 py-2 bg-gray-800 border border-white/20 rounded-lg text-white text-sm w-52">
        <option value="MSME Certificate">MSME Certificate</option>
        <option value="Udyam Aadhaar">Udyam Aadhaar</option>
        <option value="Trade License">Trade License</option>
        <option value="Registration Certificate">Registration Certificate</option>
        <option value="Other">Other</option>
    </select>
    <input type="file" name="certificates[${certCount}]" accept=".pdf,.jpg,.jpeg,.png" class="flex-1 text-white text-sm">
    <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300 text-xs px-2">✕</button>`;
    list.appendChild(div);
    certCount++;
}
</script>

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