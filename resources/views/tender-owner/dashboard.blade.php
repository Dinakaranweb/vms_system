@extends('layouts.dashboard')

@section('title', 'Tender Owner Dashboard')
@section('header', 'My Tenders Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">My Tenders</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <i class="fas fa-file-alt text-3xl text-blue-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Published</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['published'] }}</p>
                </div>
                <i class="fas fa-check-circle text-3xl text-green-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Draft</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['draft'] }}</p>
                </div>
                <i class="fas fa-pencil-alt text-3xl text-yellow-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Closed</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['closed'] }}</p>
                </div>
                <i class="fas fa-times-circle text-3xl text-red-500"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex justify-between items-center">
                <div>
                    <p class="text-gray-500 text-sm">Applications</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_applications'] }}</p>
                </div>
                <i class="fas fa-users text-3xl text-purple-500"></i>
            </div>
        </div>
    </div>

    <!-- My Tenders Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-white">
                <i class="fas fa-list mr-2"></i>My Tenders
            </h3>
            <a href="{{ route('tender-owner.tenders.create') }}" class="bg-white text-blue-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition">
                <i class="fas fa-plus mr-2"></i>Create New Tender
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tender No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Committee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applications</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($tenders as $tender)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-mono font-medium text-gray-900">{{ $tender->tender_number }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ Str::limit($tender->title, 40) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <a href="{{ route('tender-owner.committee', $tender->id) }}" class="text-purple-600 hover:text-purple-800">
                                <i class="fas fa-users mr-1"></i>{{ $tender->committeeMembers->count() }} Members
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $tender->applications_count }}</td>
                        <td class="px-6 py-4">
                            @if($tender->status == 'published')
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Published</span>
                            @elseif($tender->status == 'draft')
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Draft</span>
                            @else
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Closed</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $tender->created_at->format('d M, Y') }}</td>
                        <td class="px-6 py-4">
                            <div class="flex space-x-2">
                                <a href="{{ route('tender-owner.tenders.show', $tender->id) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($tender->status == 'draft')
                                <a href="{{ route('tender-owner.tenders.edit', $tender->id) }}" class="text-green-600 hover:text-green-800" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="publishTender({{ $tender->id }})" class="text-purple-600 hover:text-purple-800" title="Publish">
                                    <i class="fas fa-rocket"></i>
                                </button>
                                @endif
                                @if($tender->status == 'published')
                                <button onclick="closeTender({{ $tender->id }})" class="text-orange-600 hover:text-orange-800" title="Close">
                                    <i class="fas fa-lock"></i>
                                </button>
                                @endif
                                <button onclick="deleteTender({{ $tender->id }})" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <a href="{{ route('tender-owner.committee', $tender->id) }}" class="text-purple-600 hover:text-purple-800" title="Committee">
                                    <i class="fas fa-users"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-2"></i>
                            <p>No tenders found</p>
                            <a href="{{ route('tender-owner.tenders.create') }}" class="text-blue-600 hover:underline mt-2 inline-block">Create your first tender</a>
                         </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $tenders->links() }}
        </div>
    </div>
</div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';

function publishTender(id) {
    if (confirm('Are you sure you want to publish this tender? Once published, vendors can start applying.')) {
        fetch(`/tender-owner/tenders/${id}/publish`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Tender published successfully!');
                  location.reload();
              } else {
                  alert('Failed to publish tender: ' + data.message);
              }
          });
    }
}

function closeTender(id) {
    if (confirm('Are you sure you want to close this tender? No more applications will be accepted.')) {
        fetch(`/tender-owner/tenders/${id}/close`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Tender closed successfully!');
                  location.reload();
              } else {
                  alert('Failed to close tender: ' + data.message);
              }
          });
    }
}

function deleteTender(id) {
    if (confirm('Are you sure you want to delete this tender? This action cannot be undone.')) {
        fetch(`/tender-owner/tenders/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).then(response => response.json())
          .then(data => {
              if (data.success) {
                  alert('Tender deleted successfully!');
                  location.reload();
              } else {
                  alert('Failed to delete tender: ' + data.message);
              }
          });
    }
}
</script>
@endsection