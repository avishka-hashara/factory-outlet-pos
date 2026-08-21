@extends('layouts.pos')
@section('title', 'Access Denied')
@section('content')
<div class="d-flex flex-column align-items-center justify-content-center" style="min-height:60vh;">
    <div class="text-center">
        <div class="mb-4" style="font-size:5rem;opacity:0.25;"><i class="fas fa-lock"></i></div>
        <h2 class="fw-bold mb-2">Access Denied</h2>
        <p class="text-muted mb-4">{{ $message ?? 'You do not have permission to access this page.' }}</p>
        <a href="{{ route('dashboard') }}" class="btn btn-gold me-2">
            <i class="fas fa-home me-1"></i> Go to Dashboard
        </a>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Go Back
        </a>
    </div>
</div>
@endsection
