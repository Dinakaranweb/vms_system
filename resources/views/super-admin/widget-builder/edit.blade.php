@extends('layouts.dashboard')

@section('title', 'Dashboard Widget Builder')
@section('header', 'Dashboard Widget Builder')

@section('content')
<div class="max-w-3xl mx-auto space-y-4">

    <a href="{{ route('super-admin.roles.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Roles
    </a>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-xs text-blue-700">
        <i class="fas fa-info-circle mr-1"></i>
        These widgets appear as an extra section at the bottom of this role's dashboard — every role's own dedicated dashboard content stays exactly as-is above it. Uncheck everything to leave the dashboard as it was before this feature existed.
    </div>

    <form method="POST" action="{{ route('super-admin.widget-builder.update', $role->id) }}" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @csrf
        @method('PUT')
        <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
            <h3 class="text-lg font-semibold text-white"><i class="fas fa-th-large mr-2"></i>{{ \Illuminate\Support\Str::headline($role->name) }} — Dashboard Widgets</h3>
        </div>
        <div class="p-6 space-y-2">
            @foreach($widgets as $widget)
            <label class="flex items-start gap-3 py-3 px-3 rounded-lg hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-100">
                <input type="checkbox" name="widgets[]" value="{{ $widget->id }}"
                    {{ in_array($widget->id, $assignedIds) ? 'checked' : '' }}
                    class="rounded text-indigo-600 mt-0.5">
                <span>
                    <span class="text-sm font-medium text-gray-800 flex items-center gap-2">
                        <i class="fas {{ $widget->icon ?? 'fa-square' }} text-indigo-400 text-xs"></i>
                        {{ $widget->label }}
                        <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 text-gray-500">{{ $widget->widget_type === 'stat_grid' ? 'Stat Cards' : 'List' }}</span>
                    </span>
                    @if($widget->description)
                    <span class="text-xs text-gray-400 block mt-0.5">{{ $widget->description }}</span>
                    @endif
                </span>
            </label>
            @endforeach
        </div>
        <div class="px-6 pb-6">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
                <i class="fas fa-save mr-1"></i> Save Dashboard
            </button>
        </div>
    </form>
</div>
@endsection
