<x-app-layout>
    <x-slot name="title">Assign Weekly Off</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Assign Weekly Off</h5>
            <small class="text-muted">Employee-specific weekly off rule</small>
        </div>
        <a href="{{ route('weekly-offs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form method="POST" action="{{ route('weekly-offs.store') }}">
        @include('weekly-offs._form')
    </form>
</x-app-layout>
