@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-6">

    <div class="bg-gradient-to-r from-slate-800 to-slate-700 rounded-2xl px-6 py-5 text-white shadow-md">
        <p class="text-slate-300 text-sm mb-1">Welcome back,</p>
        <h2 class="text-2xl font-bold">{{ auth()->user()->name }}</h2>
        <p class="text-slate-300 text-sm mt-1">{{ auth()->user()->roles->first()?->name ? \Illuminate\Support\Str::headline(auth()->user()->roles->first()->name) : '' }} &nbsp;·&nbsp; {{ now()->format('d M, Y') }}</p>
    </div>

    @include('partials.dashboard-widgets', ['widgetData' => $widgetData, 'hideEmptyState' => false])
</div>
@endsection
