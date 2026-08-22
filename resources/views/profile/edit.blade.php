@extends('layouts.pos')
@section('title', 'Profile')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="p-6 bg-surface border border-surface-muted rounded-lg">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="p-6 bg-surface border border-surface-muted rounded-lg">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="p-6 bg-surface border border-surface-muted rounded-lg">
        <div class="max-w-xl">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</div>
@endsection

