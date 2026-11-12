{{-- Client profile: recruitment orders of the client. --}}
@can('create', App\Models\RecruitmentOrder::class)
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('admin.recruitment.create', ['client' => $client->id]) }}" class="btn btn-sm btn-primary">
            <i class="ri-add-line me-1"></i>{{ __('recruitment.create') }}
        </a>
    </div>
@endcan

@include('admin.recruitment.tabs._orders-table', [
    'orders' => $client->recruitmentOrders()->with(['worker', 'country'])->get(),
    'column' => 'worker',
])
