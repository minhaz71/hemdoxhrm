<x-app-layout>
    <x-slot name="title">Edit Holiday</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Edit Holiday</h5>
            <small class="text-muted">{{ $holiday->title }}</small>
        </div>
        <a href="{{ route('holidays.show', $holiday) }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form method="POST" action="{{ route('holidays.update', $holiday) }}">
        @include('holidays._form')
    </form>
</x-app-layout>
