@extends('layouts.dashboard')

@section('title', 'User Management')
@section('header', 'User Management')

@section('content')
@php $rp = auth()->user()->hasRole('super-admin') ? 'super-admin' : 'admin'; @endphp
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Total Users</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <i class="fas fa-users text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Active</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['active'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Inactive</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['inactive'] }}</p>
                </div>
                <i class="fas fa-ban text-3xl text-red-500"></i>
            </div>
        </div>
        @if(isset($stats['super_admins']))
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Super Admins</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['super_admins'] }}</p>
                </div>
                <i class="fas fa-crown text-3xl text-purple-500"></i>
            </div>
        </div>
        @endif
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Admins</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['admins'] }}</p>
                </div>
                <i class="fas fa-user-shield text-3xl text-orange-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-teal-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Vendors</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['vendors'] }}</p>
                </div>
                <i class="fas fa-store text-3xl text-teal-500"></i>
            </div>
        </div>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <div class="flex flex-wrap gap-4 justify-between items-center">
            <div class="flex space-x-3">
                <select id="bulkAction" class="px-3 py-2 border border-gray-300 rounded-lg">
                    <option value="">Bulk Actions</option>
                    <option value="activate">Activate</option>
                    <option value="deactivate">Deactivate</option>
                    <option value="delete">Delete</option>
                </select>
                <button id="applyBulkAction" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                    Apply
                </button>
            </div>
            <a href="{{ route($rp . '.users.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Add New User
            </a>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>All Users
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <input type="checkbox" id="selectAll" class="rounded">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <input type="checkbox" class="user-checkbox rounded" value="{{ $user->id }}">
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $user->id }}</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                                    {{ substr($user->name, 0, 2) }}
                                </div>
                                <span class="font-medium text-gray-900">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $user->email }}</td>
                        <td class="px-6 py-4">
                            @foreach($user->getRoleNames() as $role)
                                @php
                                    $roleColors = [
                                        'super-admin' => 'bg-purple-100 text-purple-800',
                                        'admin' => 'bg-blue-100 text-blue-800',
                                        'vendor' => 'bg-green-100 text-green-800',
                                        'tender-owner' => 'bg-orange-100 text-orange-800',
                                    ];
                                    $color = $roleColors[$role] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2 py-1 {{ $color }} rounded-full text-xs">{{ ucfirst($role) }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            @if($user->vendor)
                                <span class="text-sm">{{ $user->vendor->company_name }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->is_active)
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Active</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <button onclick="viewUser({{ $user->id }})" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="{{ route($rp . '.users.edit', $user->id) }}" class="text-green-600 hover:text-green-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="toggleUserStatus({{ $user->id }})" class="text-yellow-600 hover:text-yellow-800" title="Toggle Status">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                @if($rp === 'super-admin')
                                <button onclick="openResetPasswordModal({{ $user->id }}, '{{ addslashes($user->name) }}')" class="text-purple-600 hover:text-purple-800" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                                @endif
                                <button onclick="deleteUser({{ $user->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-2"></i>
                            <p>No users found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- View User Modal -->
<div id="viewUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 overflow-y-auto" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto m-4">
        <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
            <h3 class="text-xl font-bold text-white">User Details</h3>
            <button onclick="closeViewModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div id="userDetails" class="p-6">Loading...</div>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPasswordModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" style="display: none;">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md m-4">
        <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-lg font-bold text-white"><i class="fas fa-key mr-2"></i>Reset Password</h3>
            <button onclick="closeResetPasswordModal()" class="text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <form id="resetPasswordForm" class="p-6 space-y-4">
            <p class="text-sm text-gray-600">Set a new password for <strong id="resetPasswordUserName"></strong>. They'll be notified by email that their password was changed.</p>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">New Password</label>
                <input type="password" name="password" required minlength="8"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Confirm New Password</label>
                <input type="password" name="password_confirmation" required minlength="8"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500">
            </div>
            <div id="resetPasswordError" class="text-xs text-red-600 hidden"></div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700">
                    <i class="fas fa-key mr-1"></i> Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const rolePrefix = '{{ $rp }}';
let selectedUsers = new Set();

// Select All
document.getElementById('selectAll')?.addEventListener('change', function(e) {
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.checked = e.target.checked;
        if (e.target.checked) {
            selectedUsers.add(cb.value);
        } else {
            selectedUsers.clear();
        }
    });
});

// Individual checkbox selection
function attachCheckboxListeners() {
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (this.checked) {
                selectedUsers.add(this.value);
            } else {
                selectedUsers.delete(this.value);
            }
        });
    });
}
attachCheckboxListeners();

// Bulk Action
document.getElementById('applyBulkAction').addEventListener('click', () => {
    const action = document.getElementById('bulkAction').value;
    if (!action) {
        alert('Please select an action');
        return;
    }
    
    if (selectedUsers.size === 0) {
        alert('Please select at least one user');
        return;
    }
    
    if (confirm(`Are you sure you want to ${action} ${selectedUsers.size} user(s)?`)) {
        fetch(`/${rolePrefix}/users/bulk-action`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                action: action,
                user_ids: Array.from(selectedUsers)
            })
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert(data.message);
                  location.reload();
              } else {
                  alert('Failed: ' + data.message);
              }
          });
    }
});

// View User
function viewUser(id) {
    const modal = document.getElementById('viewUserModal');
    const detailsDiv = document.getElementById('userDetails');
    modal.style.display = 'flex';
    detailsDiv.innerHTML = '<div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl"></i><p>Loading...</p></div>';
    
    fetch(`/${rolePrefix}/users/${id}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.text())
    .then(html => { detailsDiv.innerHTML = html; });
}

function closeViewModal() {
    document.getElementById('viewUserModal').style.display = 'none';
}

// Reset Password
let resetPasswordUserId = null;
function openResetPasswordModal(id, name) {
    resetPasswordUserId = id;
    document.getElementById('resetPasswordUserName').textContent = name;
    document.getElementById('resetPasswordForm').reset();
    document.getElementById('resetPasswordError').classList.add('hidden');
    const modal = document.getElementById('resetPasswordModal');
    modal.style.display = 'flex';
}
function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
    resetPasswordUserId = null;
}
document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const errorBox = document.getElementById('resetPasswordError');
    errorBox.classList.add('hidden');
    const formData = new FormData(this);
    fetch(`/${rolePrefix}/users/${resetPasswordUserId}/reset-password`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: formData,
    })
    .then(r => r.json().then(data => ({ status: r.status, data })))
    .then(({ status, data }) => {
        if (data.success) {
            alert(data.message);
            closeResetPasswordModal();
        } else {
            errorBox.textContent = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to reset password.');
            errorBox.classList.remove('hidden');
        }
    })
    .catch(() => {
        errorBox.textContent = 'Network error. Please try again.';
        errorBox.classList.remove('hidden');
    });
});

// Toggle User Status
function toggleUserStatus(id) {
    if (confirm('Toggle user status?')) {
        fetch(`/${rolePrefix}/users/${id}/toggle-status`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert(data.message);
                  location.reload();
              } else {
                  alert('Failed: ' + data.message);
              }
          });
    }
}

// Delete User
function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        fetch(`/${rolePrefix}/users/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert(data.message);
                  location.reload();
              } else {
                  alert('Failed: ' + data.message);
              }
          });
    }
}
</script>
@endsection