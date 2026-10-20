@extends('layouts.dashboard')

@section('title', 'Manage Vendors')
@section('header', 'Vendor Management')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Vendors</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $totalVendors ?? 0 }}</p>
                </div>
                <i class="fas fa-building text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Pending</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $pendingVendors ?? 0 }}</p>
                </div>
                <i class="fas fa-clock text-3xl text-yellow-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Approved</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $approvedVendors ?? 0 }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Rejected</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $rejectedVendors ?? 0 }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
    </div>

    <!-- Vendors Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-users mr-2"></i>All Vendors
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PAN Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($vendors as $vendor)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $vendor->vendor_code }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $vendor->company_name }}</div>
                            <div class="text-xs text-gray-500">{{ $vendor->city ?? 'N/A' }}, {{ $vendor->state ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $vendor->email }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $vendor->phone }}</td>
                        <td class="px-6 py-4 text-sm font-mono text-gray-600">{{ $vendor->pan_number }}</td>
                        <td class="px-6 py-4">
                            @if($vendor->status == 'pending')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            @elseif($vendor->status == 'approved')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
                                    <i class="fas fa-check-circle mr-1"></i>Approved
                                </span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
                                    <i class="fas fa-times-circle mr-1"></i>Rejected
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $vendor->created_at->format('d M, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button type="button" onclick="window.viewVendor({{ $vendor->id }})" class="text-blue-600 hover:text-blue-800" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if($vendor->status == 'pending')
                                <button type="button" onclick="window.showApproveModal({{ $vendor->id }}, '{{ addslashes($vendor->company_name) }}')" class="text-green-600 hover:text-green-800" title="Approve">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button type="button" onclick="window.showRejectModal({{ $vendor->id }}, '{{ addslashes($vendor->company_name) }}')" class="text-red-600 hover:text-red-800" title="Reject">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                                @endif
                                @if($vendor->status == 'approved')
                                <button type="button" onclick="window.showCategoriesModal({{ $vendor->id }}, '{{ addslashes($vendor->company_name) }}')" class="text-indigo-600 hover:text-indigo-800" title="Manage Categories">
                                    <i class="fas fa-tags"></i>
                                </button>
                                @endif
                                <button type="button" onclick="window.showDeleteModal({{ $vendor->id }}, '{{ addslashes($vendor->company_name) }}')" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-building text-4xl mb-2"></i>
                            <p>No vendors found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $vendors->links() }}
        </div>
    </div>
</div>

<!-- ==================== VIEW VENDOR MODAL ==================== -->
<div id="viewModal" class="fixed inset-0 bg-black/60 hidden items-center justify-center z-50 p-4" style="display:none; backdrop-filter:blur(3px);">
    <div class="bg-white rounded-2xl shadow-2xl w-full flex flex-col" style="max-width:1100px; max-height:92vh;">

        {{-- Header --}}
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center rounded-t-2xl flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-white/20 flex items-center justify-center">
                    <i class="fas fa-user-tie text-white"></i>
                </div>
                <div>
                    <h3 id="viewModalTitle" class="text-base font-bold text-white leading-tight">Vendor Details</h3>
                    <p class="text-xs text-white/60">Full application information</p>
                </div>
            </div>
            <button onclick="window.closeViewModal()" class="text-white/70 hover:text-white transition-colors">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        {{-- Two-column body: details + document viewer --}}
        <div class="flex flex-1 min-h-0">

            {{-- Left: vendor info --}}
            <div id="vendorDetails" class="overflow-y-auto flex-1 border-r border-gray-100">
                <div class="text-center py-10">
                    <i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i>
                    <p class="mt-3 text-gray-400 text-sm">Loading vendor details...</p>
                </div>
            </div>

            {{-- Right: document viewer (hidden until a doc is clicked) --}}
            <div id="docViewerPanel" style="display:none; width:480px; flex-shrink:0; display:none;" class="flex flex-col">
                <div class="px-4 py-3 border-b border-gray-100 bg-gray-50 flex items-center justify-between flex-shrink-0">
                    <span id="docViewerName" class="text-sm font-semibold text-gray-700 truncate flex-1 mr-2"></span>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a id="docViewerDownload" href="#" download target="_blank"
                           class="text-xs px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button onclick="closeDocViewer()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div id="docViewerContent" class="flex-1 overflow-hidden bg-gray-100">
                    {{-- iframe or img injected by JS --}}
                </div>
            </div>

        </div>

        {{-- Footer --}}
        <div class="px-6 py-3 border-t border-gray-100 flex justify-end flex-shrink-0 bg-gray-50 rounded-b-2xl">
            <button onclick="window.closeViewModal()" class="px-5 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-times mr-1.5"></i> Close
            </button>
        </div>
    </div>
</div>

<!-- ==================== APPROVE VENDOR MODAL ==================== -->
<div id="approveModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="bg-gradient-to-r from-green-600 to-green-500 px-6 py-4">
            <h3 class="text-xl font-bold text-white">
                <i class="fas fa-check-circle mr-2"></i>Approve Vendor
            </h3>
        </div>
        <div class="p-6">
            <div class="text-center mb-4">
                <i class="fas fa-building text-6xl text-green-500 mb-3"></i>
                <p class="text-gray-600">Are you sure you want to approve this vendor?</p>
                <p class="font-semibold text-gray-800 mt-2" id="approveVendorName"></p>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="window.closeApproveModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    Cancel
                </button>
                <button onclick="window.confirmApprove()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-check mr-2"></i>Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== REJECT VENDOR MODAL ==================== -->
<div id="rejectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
            <h3 class="text-xl font-bold text-white">
                <i class="fas fa-times-circle mr-2"></i>Reject Vendor
            </h3>
        </div>
        <div class="p-6">
            <div class="text-center mb-4">
                <i class="fas fa-building text-6xl text-red-500 mb-3"></i>
                <p class="text-gray-600">Please provide a reason for rejection</p>
                <p class="font-semibold text-gray-800 mt-2" id="rejectVendorName"></p>
            </div>
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                <textarea id="rejectionReason" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="Enter detailed reason for rejection..."></textarea>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="window.closeRejectModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    Cancel
                </button>
                <button onclick="window.confirmReject()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-times mr-2"></i>Reject
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== DELETE VENDOR MODAL ==================== -->
<div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="bg-gradient-to-r from-red-600 to-red-500 px-6 py-4">
            <h3 class="text-xl font-bold text-white">
                <i class="fas fa-trash-alt mr-2"></i>Delete Vendor
            </h3>
        </div>
        <div class="p-6">
            <div class="text-center mb-4">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-3"></i>
                <p class="text-gray-600">Are you sure you want to delete this vendor?</p>
                <p class="font-semibold text-gray-800 mt-2" id="deleteVendorName"></p>
                <p class="text-sm text-red-600 mt-2 font-semibold">⚠️ This action cannot be undone!</p>
            </div>
            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="window.closeDeleteModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    Cancel
                </button>
                <button onclick="window.confirmDelete()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MANAGE CATEGORIES MODAL ==================== -->
<div id="categoriesModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg m-4">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">
                <i class="fas fa-tags mr-2"></i>Manage Categories
            </h3>
            <button onclick="window.closeCategoriesModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-500 mb-1">Vendor</p>
            <p class="font-semibold text-gray-800 mb-4" id="categoriesVendorName"></p>

            <div id="categoriesLoadingState" class="text-center py-6 text-gray-400">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p class="text-sm">Loading categories...</p>
            </div>

            <div id="categoriesListWrap" class="hidden">
                <p class="text-sm font-medium text-gray-700 mb-3">Select categories to assign:</p>
                <div id="categoriesList" class="grid grid-cols-1 gap-2 max-h-64 overflow-y-auto pr-1"></div>
                <p id="noCategoriesMsg" class="hidden text-sm text-gray-400 text-center py-4">No active categories available.</p>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
                <button onclick="window.closeCategoriesModal()" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    Cancel
                </button>
                <button onclick="window.confirmUpdateCategories()" id="saveCategoriesBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    <i class="fas fa-save mr-2"></i>Save Categories
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 hidden transform transition-all duration-300 z-50" style="display: none;">
    <div class="bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Success!</span>
    </div>
</div>

<script>
// Make functions globally available
window.viewVendor = function(id) {
    const modal = document.getElementById('viewModal');
    const detailsDiv = document.getElementById('vendorDetails');
    const modalTitle = document.getElementById('viewModalTitle');

    modal.style.display = 'flex';
    detailsDiv.innerHTML = '<div class="text-center py-10"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i><p class="mt-3 text-gray-400 text-sm">Loading vendor details...</p></div>';

    const role = '{{ auth()->user()->hasRole("super-admin") ? "super-admin" : "admin" }}';
    fetch(`/${role}/vendors/${id}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success || !data.vendor) {
            detailsDiv.innerHTML = '<div class="text-center py-10 text-red-500"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Failed to load vendor details.</p></div>';
            return;
        }

        const v = data.vendor;

        if (modalTitle) modalTitle.textContent = v.company_name || 'Vendor Details';

        /* ── documents ── */
        const docs = v.registration_documents || {};
        function renderDocEntry(label, filePath, fileName) {
            const ext  = (filePath || '').split('.').pop().toUpperCase();
            const url  = '/storage/' + filePath;
            const isImg = ['JPG','JPEG','PNG','GIF','WEBP'].includes(ext);
            const isPdf = ext === 'PDF';
            const iconCls = isPdf ? 'fa-file-pdf text-red-500' : (isImg ? 'fa-file-image text-blue-500' : 'fa-file-alt text-gray-500');
            const displayName = fileName ? escapeHtml(label + ' — ' + fileName) : escapeHtml(label);
            return `<div class="flex items-center gap-2 px-3 py-2.5 rounded-lg bg-gray-50 border border-gray-200">
                        <i class="fas ${iconCls} text-base flex-shrink-0"></i>
                        <span class="flex-1 text-sm font-medium text-gray-800">${displayName}</span>
                        <button type="button"
                                onclick="openDocViewer('${url}', '${escapeHtml(label)}.${ext.toLowerCase()}', '${isPdf ? 'pdf' : (isImg ? 'img' : 'other')}')"
                                class="text-xs px-2.5 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700 flex items-center gap-1 flex-shrink-0">
                            <i class="fas fa-eye"></i> View
                        </button>
                        <a href="${url}" download
                           class="text-xs px-2.5 py-1 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 flex items-center gap-1 flex-shrink-0">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>`;
        }
        const docRows = [];
        Object.entries(docs).forEach(([type, value]) => {
            const label = type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            if (type === 'certificates' && Array.isArray(value)) {
                value.forEach(cert => {
                    if (cert && cert.path) docRows.push(renderDocEntry(cert.label || 'Certificate', cert.path, cert.name));
                });
            } else if (value && typeof value === 'object' && value.path) {
                docRows.push(renderDocEntry(label, value.path, value.name));
            } else if (typeof value === 'string') {
                docRows.push(renderDocEntry(label, value, null));
            }
        });
        const docsHtml = docRows.length
            ? docRows.join('')
            : '<p class="text-sm text-gray-400 py-1">No documents uploaded yet.</p>';

        /* ── bank details ── */
        const bank = v.bank_details || {};
        const hasBankDetails = Object.keys(bank).some(k => bank[k]);
        const bankHtml = hasBankDetails
            ? `<div class="grid grid-cols-2 gap-3">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-400 uppercase tracking-wide">Bank Name</label>
                        <p class="text-gray-900 font-medium mt-0.5">${escapeHtml(bank.bank_name || '—')}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-400 uppercase tracking-wide">Account Holder</label>
                        <p class="text-gray-900 font-medium mt-0.5">${escapeHtml(bank.account_holder_name || '—')}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-400 uppercase tracking-wide">Account Number</label>
                        <p class="font-mono text-gray-900 mt-0.5">${escapeHtml(bank.account_number || '—')}</p>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-3">
                        <label class="text-xs text-gray-400 uppercase tracking-wide">IFSC Code</label>
                        <p class="font-mono text-gray-900 mt-0.5">${escapeHtml(bank.ifsc_code || '—')}</p>
                    </div>
               </div>`
            : '<p class="text-sm text-gray-400 py-1">No bank details provided yet.</p>';

        /* ── full address ── */
        const addr = [v.address, v.city, v.state, v.pincode ? '- ' + v.pincode : ''].filter(Boolean).join(', ');

        /* ── dates ── */
        const fmt = d => d ? new Date(d).toLocaleDateString('en-IN', {day:'2-digit', month:'short', year:'numeric'}) : '—';

        detailsDiv.innerHTML = `
            <div class="space-y-6 p-6">

                {{-- ── Company Information ── --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fas fa-building text-blue-400"></i> Company Information
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="col-span-2 bg-blue-50 rounded-lg p-3 border border-blue-100">
                            <label class="text-xs text-blue-400 uppercase tracking-wide">Company Name</label>
                            <p class="font-bold text-gray-900 text-base mt-0.5">${escapeHtml(v.company_name)}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Vendor Code</label>
                            <p class="font-mono font-semibold text-gray-800 mt-0.5">${escapeHtml(v.vendor_code)}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Status</label>
                            <div class="mt-1">${getStatusBadge(v.status)}</div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">PAN Number</label>
                            <p class="font-mono text-gray-900 mt-0.5">${escapeHtml(v.pan_number)}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">GST Number</label>
                            <p class="font-mono text-gray-900 mt-0.5">${escapeHtml(v.gst_number || '—')}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Registered On</label>
                            <p class="text-gray-900 mt-0.5">${fmt(v.created_at)}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Last Updated</label>
                            <p class="text-gray-900 mt-0.5">${fmt(v.updated_at)}</p>
                        </div>
                        ${v.rejection_reason ? `
                        <div class="col-span-2 bg-red-50 border border-red-100 rounded-lg p-3">
                            <label class="text-xs text-red-500 uppercase tracking-wide font-semibold">Rejection Reason</label>
                            <p class="text-red-700 mt-0.5 text-sm">${escapeHtml(v.rejection_reason)}</p>
                        </div>` : ''}
                    </div>
                </div>

                {{-- ── Contact Information ── --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fas fa-user text-blue-400"></i> Contact Information
                    </p>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Contact Person</label>
                            <p class="text-gray-900 font-medium mt-0.5">${escapeHtml(v.user?.name || '—')}</p>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Phone</label>
                            <p class="text-gray-900 mt-0.5">${escapeHtml(v.phone)}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Email</label>
                            <p class="text-gray-900 mt-0.5">${escapeHtml(v.email)}</p>
                        </div>
                        <div class="col-span-2 bg-gray-50 rounded-lg p-3">
                            <label class="text-xs text-gray-400 uppercase tracking-wide">Address</label>
                            <p class="text-gray-900 mt-0.5">${escapeHtml(addr) || '—'}</p>
                        </div>
                    </div>
                </div>

                {{-- ── Bank Details ── --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fas fa-university text-blue-400"></i> Bank Details
                    </p>
                    ${bankHtml}
                </div>

                {{-- ── Registration Documents ── --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3 pb-2 border-b border-gray-100 flex items-center gap-2">
                        <i class="fas fa-file-alt text-blue-400"></i> Registration Documents
                    </p>
                    <div class="space-y-2">${docsHtml}</div>
                </div>

            </div>
        `;
    })
    .catch(() => {
        detailsDiv.innerHTML = '<div class="text-center py-10 text-red-500"><i class="fas fa-exclamation-circle text-3xl mb-2"></i><p>Unable to fetch vendor details. Please try again.</p></div>';
    });
};

window.closeViewModal = function() {
    document.getElementById('viewModal').style.display = 'none';
    closeDocViewer();
    const t = document.getElementById('viewModalTitle');
    if (t) t.textContent = 'Vendor Details';
};

function openDocViewer(url, filename, type) {
    const panel   = document.getElementById('docViewerPanel');
    const content = document.getElementById('docViewerContent');
    const nameEl  = document.getElementById('docViewerName');
    const dlBtn   = document.getElementById('docViewerDownload');

    nameEl.textContent = filename;
    dlBtn.href         = url;
    dlBtn.download     = filename;

    if (type === 'pdf') {
        content.innerHTML = `<iframe src="${url}" class="w-full h-full border-none" style="min-height:500px;"></iframe>`;
    } else if (type === 'img') {
        content.innerHTML = `<div class="w-full h-full overflow-auto flex items-start justify-center p-4"><img src="${url}" alt="${filename}" class="max-w-full rounded shadow"></div>`;
    } else {
        content.innerHTML = `<div class="flex flex-col items-center justify-center h-full gap-4 p-8 text-center">
            <i class="fas fa-file-alt text-5xl text-gray-300"></i>
            <p class="text-sm text-gray-500">Preview not available for this file type.</p>
            <a href="${url}" download class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                <i class="fas fa-download mr-1"></i> Download to View
            </a>
        </div>`;
    }

    panel.style.display = 'flex';
    panel.style.flexDirection = 'column';
}

function closeDocViewer() {
    const panel = document.getElementById('docViewerPanel');
    if (panel) {
        panel.style.display = 'none';
        document.getElementById('docViewerContent').innerHTML = '';
    }
}

// Approve functions
let currentApproveId = null;

window.showApproveModal = function(id, name) {
    currentApproveId = id;
    document.getElementById('approveVendorName').innerHTML = `<i class="fas fa-building mr-1"></i> ${escapeHtml(name)}`;
    document.getElementById('approveModal').style.display = 'flex';
};

window.closeApproveModal = function() {
    document.getElementById('approveModal').style.display = 'none';
    currentApproveId = null;
};

window.confirmApprove = function() {
    if (!currentApproveId) return;
    
    const role = '{{ auth()->user()->hasRole("super-admin") ? "super-admin" : "admin" }}';
    fetch(`/${role}/vendors/${currentApproveId}/approve`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Vendor approved successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Failed to approve vendor', 'error');
        }
    })
    .catch(error => {
        showToast('Error approving vendor', 'error');
    });
    
    window.closeApproveModal();
};

// Reject functions
let currentRejectId = null;

window.showRejectModal = function(id, name) {
    currentRejectId = id;
    document.getElementById('rejectVendorName').innerHTML = `<i class="fas fa-building mr-1"></i> ${escapeHtml(name)}`;
    document.getElementById('rejectionReason').value = '';
    document.getElementById('rejectModal').style.display = 'flex';
};

window.closeRejectModal = function() {
    document.getElementById('rejectModal').style.display = 'none';
    currentRejectId = null;
};

window.confirmReject = function() {
    const reason = document.getElementById('rejectionReason').value;
    if (!reason || reason.trim() === '') {
        showToast('Please enter a rejection reason', 'error');
        return;
    }
    
    if (!currentRejectId) return;
    
    const role = '{{ auth()->user()->hasRole("super-admin") ? "super-admin" : "admin" }}';
    fetch(`/${role}/vendors/${currentRejectId}/reject`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Vendor rejected successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Failed to reject vendor', 'error');
        }
    })
    .catch(error => {
        showToast('Error rejecting vendor', 'error');
    });
    
    window.closeRejectModal();
};

// Delete functions
let currentDeleteId = null;

window.showDeleteModal = function(id, name) {
    currentDeleteId = id;
    document.getElementById('deleteVendorName').innerHTML = `<i class="fas fa-building mr-1"></i> ${escapeHtml(name)}`;
    document.getElementById('deleteModal').style.display = 'flex';
};

window.closeDeleteModal = function() {
    document.getElementById('deleteModal').style.display = 'none';
    currentDeleteId = null;
};

window.confirmDelete = function() {
    if (!currentDeleteId) return;
    
    const role = '{{ auth()->user()->hasRole("super-admin") ? "super-admin" : "admin" }}';
    fetch(`/${role}/vendors/${currentDeleteId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Vendor deleted successfully!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Failed to delete vendor', 'error');
        }
    })
    .catch(error => {
        showToast('Error deleting vendor', 'error');
    });
    
    window.closeDeleteModal();
};

// Helper functions
function getStatusBadge(status) {
    const badges = {
        'pending': '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs"><i class="fas fa-clock mr-1"></i>Pending</span>',
        'approved': '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs"><i class="fas fa-check-circle mr-1"></i>Approved</span>',
        'rejected': '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs"><i class="fas fa-times-circle mr-1"></i>Rejected</span>'
    };
    return badges[status] || status;
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastDiv = toast.querySelector('div');
    
    toastMessage.textContent = message;
    
    if (type === 'success') {
        toastDiv.className = 'bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2';
    } else {
        toastDiv.className = 'bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-2';
    }
    
    toast.style.display = 'flex';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close modals when clicking outside
document.addEventListener('click', function(event) {
    const viewModal = document.getElementById('viewModal');
    const approveModal = document.getElementById('approveModal');
    const rejectModal = document.getElementById('rejectModal');
    const deleteModal = document.getElementById('deleteModal');
    const categoriesModal = document.getElementById('categoriesModal');

    if (event.target === viewModal) window.closeViewModal();
    if (event.target === approveModal) window.closeApproveModal();
    if (event.target === rejectModal) window.closeRejectModal();
    if (event.target === deleteModal) window.closeDeleteModal();
    if (event.target === categoriesModal) window.closeCategoriesModal();
});

// ---- Manage Categories ----
const allCategories = @json($categories);
let currentCategoriesVendorId = null;

window.showCategoriesModal = function(id, name) {
    currentCategoriesVendorId = id;
    document.getElementById('categoriesVendorName').innerHTML = `<i class="fas fa-building mr-1"></i> ${escapeHtml(name)}`;
    document.getElementById('categoriesLoadingState').classList.remove('hidden');
    document.getElementById('categoriesListWrap').classList.add('hidden');
    document.getElementById('categoriesModal').style.display = 'flex';

    const role = '{{ auth()->user()->hasRole("super-admin") ? "super-admin" : "admin" }}';
    fetch(`/${role}/vendors/${id}`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('categoriesLoadingState').classList.add('hidden');
        document.getElementById('categoriesListWrap').classList.remove('hidden');

        const assigned = data.success ? (data.vendor.category_ids || []) : [];
        renderCategoriesList(assigned);
    })
    .catch(() => {
        document.getElementById('categoriesLoadingState').classList.add('hidden');
        document.getElementById('categoriesListWrap').classList.remove('hidden');
        renderCategoriesList([]);
    });
};

function renderCategoriesList(assignedIds) {
    const list = document.getElementById('categoriesList');
    const noMsg = document.getElementById('noCategoriesMsg');
    list.innerHTML = '';

    if (!allCategories || allCategories.length === 0) {
        noMsg.classList.remove('hidden');
        return;
    }
    noMsg.classList.add('hidden');

    allCategories.forEach(cat => {
        const checked = assignedIds.includes(cat.id) ? 'checked' : '';
        list.innerHTML += `
            <label class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer border border-gray-100">
                <input type="checkbox" name="category_ids[]" value="${cat.id}" ${checked}
                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                <span class="inline-flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color:${escapeHtml(cat.color)}"></span>
                    <i class="fas ${escapeHtml(cat.icon)} text-sm text-gray-500"></i>
                    <span class="text-sm font-medium text-gray-800">${escapeHtml(cat.name)}</span>
                </span>
            </label>`;
    });
}

window.closeCategoriesModal = function() {
    document.getElementById('categoriesModal').style.display = 'none';
    currentCategoriesVendorId = null;
};

window.confirmUpdateCategories = function() {
    if (!currentCategoriesVendorId) return;

    const checkboxes = document.querySelectorAll('#categoriesList input[type="checkbox"]:checked');
    const categoryIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    const btn = document.getElementById('saveCategoriesBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';

    const role = '{{ auth()->user()->hasRole("super-admin") ? "super-admin" : "admin" }}';
    fetch(`/${role}/vendors/${currentCategoriesVendorId}/categories`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ category_ids: categoryIds })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Categories';
        if (data.success) {
            showToast('Categories updated successfully!', 'success');
            window.closeCategoriesModal();
        } else {
            showToast(data.message || 'Failed to update categories', 'error');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save mr-2"></i>Save Categories';
        showToast('Error updating categories', 'error');
    });
};

</script>
@endsection