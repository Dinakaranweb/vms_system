@extends('layouts.dashboard')

@section('title', 'Menu Builder')
@section('header', 'Menu Builder')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">

    <a href="{{ route('super-admin.roles.index') }}" class="text-sm text-indigo-600 hover:underline inline-flex items-center gap-1">
        <i class="fas fa-arrow-left text-xs"></i> Back to Roles
    </a>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <i class="fas fa-check-circle mr-1"></i>{{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('super-admin.menu-builder.update', $role->id) }}" class="space-y-4">
        @csrf
        @method('PUT')

        @if($sections->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-slate-700 to-slate-600">
                <h3 class="text-lg font-semibold text-white"><i class="fas fa-bars mr-2"></i>{{ \Illuminate\Support\Str::headline($role->name) }} — Sidebar Sections</h3>
                <p class="text-slate-300 text-xs mt-0.5">Show or hide whole nav sections already built into this role's sidebar.</p>
            </div>
            <div class="p-6 space-y-2">
                @foreach($sections as $section)
                    @php $isApplicable = $visibility->has($section->id); @endphp
                    @if($isApplicable)
                    <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="sections[]" value="{{ $section->id }}"
                            {{ $visibility->get($section->id) ? 'checked' : '' }}
                            class="rounded text-slate-600">
                        <span class="text-sm text-gray-700">{{ $section->label }}</span>
                    </label>
                    @endif
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 text-sm text-gray-400">
            This role has no built-in sidebar sections to toggle — it's a custom role using the Portal menu below instead.
        </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                <h3 class="text-lg font-semibold text-white"><i class="fas fa-th mr-2"></i>Portal Menu (Custom Role Links)</h3>
                <p class="text-indigo-100 text-xs mt-0.5">These items make up this role's dynamic sidebar menu — used automatically by any role without a built-in section above.</p>
            </div>
            <div class="p-6 space-y-2">
                @foreach($portalItems as $item)
                <label class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="portal_items[]" value="{{ $item->id }}"
                        {{ in_array($item->id, $portalAssigned) ? 'checked' : '' }}
                        class="rounded text-indigo-600">
                    <i class="fas {{ $item->icon ?? 'fa-circle' }} text-gray-400 text-xs w-4"></i>
                    <span class="text-sm text-gray-700">{{ $item->label }}</span>
                </label>
                @endforeach
            </div>
        </div>

        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 text-sm font-medium">
            <i class="fas fa-save mr-1"></i> Save Menu
        </button>
    </form>
</div>
@endsection
