{{-- Worker documents tab: checklist for the worker's country and profession, and one print button per active authority of the country. --}}
@can('documents.print_authority_file')
    @if ($authorities->isNotEmpty())
        <div class="d-flex flex-wrap gap-2 mb-3">
            @foreach ($authorities as $authority)
                <x-documents.print-button :owner="$worker" :authority="$authority" />
            @endforeach
        </div>
    @endif
@endcan

<x-documents.checklist :owner="$worker" :country="$worker->country_id" :profession="$worker->profession_id" />
