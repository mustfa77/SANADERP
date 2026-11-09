<!-- Right Sidebar -->
<div class="right-bar">
    <div data-simplebar class="h-100">
        <div class="rightbar-title d-flex align-items-center px-3 py-4">

            <h5 class="m-0 me-2">{{ __('app.settings') }}</h5>

            <a href="javascript:void(0);" class="right-bar-toggle ms-auto">
                <i class="mdi mdi-close noti-icon"></i>
            </a>
        </div>

        <!-- Settings -->
        <hr class="mt-0" />
        <h6 class="text-center mb-0">{{ __('app.choose_layouts') }}</h6>

        <div class="p-4">
            <div class="mb-2">
                <img src="{{ asset('assets/images/layouts/layout-1.png') }}" class="img-thumbnail" alt="layout-1">
            </div>

            <div class="form-check form-switch mb-3">
                <input class="form-check-input theme-choice" type="checkbox" id="light-mode-switch" checked>
                <label class="form-check-label" for="light-mode-switch">{{ __('app.light_mode') }}</label>
            </div>

            <div class="mb-2">
                <img src="{{ asset('assets/images/layouts/layout-2.png') }}" class="img-thumbnail" alt="layout-2">
            </div>
            <div class="form-check form-switch mb-3">
                <input class="form-check-input theme-choice" type="checkbox" id="dark-mode-switch">
                <label class="form-check-label" for="dark-mode-switch">{{ __('app.dark_mode') }}</label>
            </div>

            {{-- Direction (RTL/LTR) follows the selected language --}}
            <h6 class="text-center mt-4 mb-3">{{ __('app.language') }}</h6>
            <div class="d-grid gap-2">
                @foreach (config('app.available_locales') as $code => $locale)
                    <a href="{{ route('lang.switch', $code) }}"
                        class="btn btn-sm {{ app()->getLocale() === $code ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $locale['name'] }}
                    </a>
                @endforeach
            </div>
        </div>

    </div> <!-- end slimscroll-menu-->
</div>
<!-- /Right-bar -->

<!-- Right bar overlay-->
<div class="rightbar-overlay"></div>
