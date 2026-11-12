<!-- JAVASCRIPT -->
<script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
<script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
<script src="{{ asset('assets/libs/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('assets/libs/select2/js/select2.min.js') }}"></script>

<script>
    // Multi-selects (roles, branches) use Select2, in the page direction.
    $('.select2').each(function () {
        $(this).select2({ dir: document.documentElement.dir || 'ltr', width: '100%' });
    });

    // Forms with data-confirm ask for confirmation (SweetAlert2) before submitting.
    $(document).on('submit', 'form[data-confirm]', function (event) {
        const form = this;

        if (form.dataset.confirmed) {
            return;
        }

        event.preventDefault();

        Swal.fire({
            title: form.dataset.confirm,
            text: form.dataset.confirmText,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#0f9cf3',
            cancelButtonColor: '#f32f53',
            confirmButtonText: @json(__('common.confirm')),
            cancelButtonText: @json(__('common.cancel')),
        }).then(function (result) {
            if (result.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });
</script>

@stack('scripts')

<script src="{{ asset('assets/js/app.js') }}"></script>
