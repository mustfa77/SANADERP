<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                {{ date('Y') }} © {{ $site->brand() }}. {{ __('app.rights') }}
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end d-none d-sm-block">
                    {{ __('app.crafted_by') }} <i class="mdi mdi-heart text-danger"></i> {{ __('app.crafted_by_team') }}
                </div>
            </div>
        </div>
    </div>
</footer>
