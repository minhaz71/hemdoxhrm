<x-app-layout>
    <x-slot name="title">Create Holiday</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Create Holiday</h5>
            <small class="text-muted">Select the holiday year before saving a holiday.</small>
        </div>
        <a href="{{ route('holidays.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form method="POST" action="{{ route('holidays.store') }}">
        @include('holidays._form')
    </form>
</x-app-layout>
