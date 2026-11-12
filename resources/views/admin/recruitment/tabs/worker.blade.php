{{-- Worker profile: recruitment orders of the worker. --}}
@include('admin.recruitment.tabs._orders-table', [
    'orders' => $worker->recruitmentOrders()->with(['client', 'country'])->get(),
    'column' => 'client',
])
