@extends('layouts.dashboard')

@section('title', 'Audit Logs')
@section('header', 'Audit Logs')

@section('content')
<div class="space-y-6">

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Module</label>
                <select name="module" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                    <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>{{ ucfirst($mod) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Action</label>
                <select name="action" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All Actions</option>
                    @foreach($actions as $act)
                    <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ ucfirst($act) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">From</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">To</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            @if(request()->anyFilled(['module','action','date_from','date_to']))
            <a href="{{ route('super-admin.audit-logs') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Clear</a>
            @endif
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-700 to-slate-900">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-history mr-2"></i>System Activity Trail</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">When</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Module</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d M Y, H:i') }}</td>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $log->user_name ?? 'System' }}</td>
                        <td class="px-6 py-4">
                            @php
                                $colors = ['create'=>'green','update'=>'blue','delete'=>'red','approve'=>'green','reject'=>'red','login'=>'gray','logout'=>'gray'];
                                $c = $colors[$log->action] ?? 'gray';
                            @endphp
                            <span class="px-2 py-1 bg-{{ $c }}-100 text-{{ $c }}-800 rounded-full text-xs">{{ $log->getFormattedAction() }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ ucfirst($log->module) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($log->description, 60) }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('super-admin.audit-logs.show', $log->id) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center text-gray-400">
                            <i class="fas fa-history text-4xl mb-3 block"></i>
                            No activity has been recorded yet.
                            <p class="text-xs mt-1">Actions taken across the portal will appear here as they happen.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
