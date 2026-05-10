<x-app-layout>
    <x-slot name="title">Edit Weekly Off</x-slot>
    <x-alert />

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Edit Weekly Off</h5>
            <small class="text-muted">{{ $weeklyOff->employee->full_name }} - {{ ucfirst($weeklyOff->day_of_week) }}</small>
        </div>
        <a href="{{ route('weekly-offs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <form method="POST" action="{{ route('weekly-offs.update', $weeklyOff) }}">
        @include('weekly-offs._form')
    </form>
</x-app-layout>
