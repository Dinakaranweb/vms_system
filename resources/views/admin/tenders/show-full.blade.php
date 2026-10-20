@extends('layouts.dashboard')

@section('title', 'Tender Details — ' . $tender->tender_number)
@section('header', 'Tender Details')

@section('content')
<div class="max-w-5xl mx-auto">
    @include('admin.tenders.show')
</div>
@endsection
