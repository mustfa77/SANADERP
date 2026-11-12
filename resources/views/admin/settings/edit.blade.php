@extends('layouts.app')

@section('title', __('settings.title'))

@section('content')
    @include('layouts.partials.page-title', ['title' => __('settings.title')])

    <x-alerts :flash="false" />

    @php
        $canEdit = auth()->user()->can('settings.edit');
        $errorKeys = array_keys($errors->getMessages());
        $tabFromErrors = match (true) {
            collect($errorKeys)->contains(fn (string $key): bool => str_starts_with($key, 'mail_')) => 'mail',
            collect($errorKeys)->contains(fn (string $key): bool => array_key_exists($key, $securityDefinitions)) => 'security',
            collect($errorKeys)->contains(fn (string $key): bool => array_key_exists($key, $appearance)) => 'appearance',
            collect($errorKeys)->contains(fn (string $key): bool => array_key_exists($key, $maintenance)) => 'maintenance',
            collect($errorKeys)->contains(fn (string $key): bool => array_key_exists($key, $alertDefinitions)) => 'alerts',
            collect($errorKeys)->contains(fn (string $key): bool => array_key_exists($key, $recruitmentDefinitions)) => 'recruitment',
            $errorKeys !== [] => 'general',
            default => null,
        };
        $tabs = ['general' => 'ri-global-line', 'appearance' => 'ri-palette-line', 'mail' => 'ri-mail-settings-line', 'security' => 'ri-shield-keyhole-line', 'maintenance' => 'ri-database-2-line', 'alerts' => 'ri-alarm-warning-line', 'recruitment' => 'ri-flight-land-line'];
        $canManageAlerts = auth()->user()->can('settings.manage');
        $activeTab = $tabFromErrors ?? (array_key_exists((string) request('tab'), $tabs) ? request('tab') : 'general');
        $presetColors = ['#0bb197', '#3b5de7', '#6f42c1', '#e83e8c', '#f46a6a', '#fd7e14', '#f1b44c', '#1f9d55', '#0f6e59', '#343a40'];
    @endphp

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs nav-tabs-custom nav-justified" role="tablist">
                @foreach ($tabs as $tab => $icon)
                    <li class="nav-item">
                        <a class="nav-link @if ($activeTab === $tab) active @endif" data-bs-toggle="tab" href="#tab-{{ $tab }}" role="tab">
                            <i class="{{ $icon }} me-1 align-middle"></i> <span class="d-none d-md-inline-block">{{ __('settings.tabs.'.$tab) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content p-3">
                {{-- Site --}}
                <div class="tab-pane @if ($activeTab === 'general') active @endif" id="tab-general" role="tabpanel">
                    <form method="POST" action="{{ route('admin.settings.general') }}" enctype="multipart/form-data" novalidate>
                        @csrf
                        @method('PUT')

                        <h5 class="font-size-15 mb-1">{{ __('settings.general.site_name') }}</h5>
                        <p class="text-muted">{{ __('settings.general.site_name_hint') }}</p>
                        <div class="row">
                            @foreach (['site_name_ar' => 'rtl', 'site_name_en' => 'ltr'] as $key => $direction)
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.general.'.$key) }}</label>
                                    <input type="text" name="{{ $key }}" id="{{ $key }}" dir="{{ $direction }}" maxlength="100"
                                        value="{{ old($key, $siteNames[$key]) }}" @disabled(! $canEdit)
                                        class="form-control @error($key) is-invalid @enderror">
                                    @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>

                        <h5 class="font-size-15 mb-1">{{ __('settings.general.images') }}</h5>
                        <p class="text-muted">{{ __('settings.general.images_hint') }}</p>
                        <div class="row">
                            @foreach (array_keys(\App\Services\SiteSettings::IMAGES) as $key)
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.general.'.$key) }}</label>
                                    <div class="d-flex align-items-center gap-3 mb-2 p-2 border rounded @if ($key === 'logo_light') bg-dark @else bg-light @endif">
                                        <img src="{{ $site->imageUrl($key) }}" alt="" style="max-height: 40px; max-width: 200px;">
                                        <span class="badge {{ $site->hasCustomImage($key) ? 'bg-success' : 'bg-secondary' }} ms-auto">
                                            {{ $site->hasCustomImage($key) ? __('settings.general.current') : __('settings.general.default') }}
                                        </span>
                                    </div>
                                    @if ($canEdit)
                                        <input type="file" name="{{ $key }}" id="{{ $key }}"
                                            accept="{{ $key === 'favicon' ? '.ico,.png' : '.png,.jpg,.jpeg,.webp' }}"
                                            class="form-control @error($key) is-invalid @enderror">
                                        @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        @if ($site->hasCustomImage($key))
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="remove_{{ $key }}" id="remove_{{ $key }}" value="1">
                                                <label class="form-check-label" for="remove_{{ $key }}">{{ __('settings.general.remove') }}</label>
                                            </div>
                                        @endif
                                    @endif
                                    <small class="text-muted d-block mt-1">{{ __('settings.general.'.$key.'_hint') }}</small>
                                </div>
                            @endforeach
                        </div>

                        @if ($canEdit)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>
                </div>

                {{-- Appearance --}}
                <div class="tab-pane @if ($activeTab === 'appearance') active @endif" id="tab-appearance" role="tabpanel">
                    <p class="text-muted">{{ __('settings.appearance.hint') }}</p>

                    <form method="POST" action="{{ route('admin.settings.appearance') }}" novalidate>
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-lg-6 mb-4">
                                <label class="form-label" for="primary_color">{{ __('settings.appearance.primary_color') }}</label>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <input type="color" name="primary_color" id="primary_color" @disabled(! $canEdit)
                                        value="{{ old('primary_color', $appearance['primary_color']) }}"
                                        class="form-control form-control-color @error('primary_color') is-invalid @enderror">
                                    <code id="primary_color_value" dir="ltr">{{ old('primary_color', $appearance['primary_color']) }}</code>
                                </div>
                                @error('primary_color') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                                @if ($canEdit)
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach ($presetColors as $color)
                                            <button type="button" class="btn p-0 border rounded-circle js-preset-color" data-color="{{ $color }}"
                                                style="width: 28px; height: 28px; background-color: {{ $color }};" title="{{ $color }}"></button>
                                        @endforeach
                                    </div>
                                @endif
                                <small class="text-muted d-block mt-2">{{ __('settings.appearance.primary_color_hint') }}</small>
                            </div>

                            <div class="col-lg-6 mb-4">
                                <label class="form-label">{{ __('settings.appearance.preview') }}</label>
                                <div class="border rounded p-3 d-flex flex-wrap gap-2 align-items-center" id="appearance-preview">
                                    <span class="btn btn-sm js-preview-solid" style="color: #fff;">{{ __('settings.appearance.sample_button') }}</span>
                                    <span class="btn btn-sm js-preview-outline">{{ __('settings.appearance.sample_outline') }}</span>
                                    <span class="badge rounded-pill js-preview-solid" style="color: #fff;">{{ __('settings.appearance.sample_badge') }}</span>
                                    <a href="javascript:void(0);" class="js-preview-text">{{ __('settings.appearance.sample_link') }}</a>
                                </div>
                            </div>

                            @foreach (\App\Services\SiteSettings::APPEARANCE as $key => $definition)
                                <div class="col-lg-6 mb-4">
                                    <label class="form-label d-block">{{ __('settings.appearance.'.$key) }}</label>
                                    <div class="btn-group flex-wrap" role="group">
                                        @foreach ($definition['options'] as $option)
                                            <input type="radio" class="btn-check" name="{{ $key }}" id="{{ $key }}_{{ $option }}" value="{{ $option }}" autocomplete="off"
                                                @checked(old($key, $appearance[$key]) === $option) @disabled(! $canEdit)>
                                            <label class="btn btn-outline-primary" for="{{ $key }}_{{ $option }}">{{ __('settings.appearance.options.'.$key.'.'.$option) }}</label>
                                        @endforeach
                                    </div>
                                    @error($key) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>

                        @if ($canEdit)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>

                    @if ($canEdit)
                        <form method="POST" action="{{ route('admin.settings.appearance.reset') }}" class="mt-3"
                            data-confirm="{{ __('settings.appearance.reset_confirm') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-link text-danger p-0">
                                <i class="ri-refresh-line me-1"></i>{{ __('settings.appearance.reset') }}
                            </button>
                        </form>
                    @endif
                </div>

                {{-- Mail --}}
                <div class="tab-pane @if ($activeTab === 'mail') active @endif" id="tab-mail" role="tabpanel">
                    <p class="text-muted">{{ __('settings.mail.hint') }}</p>

                    @if ($mail['mail_mailer'] !== 'smtp')
                        <div class="alert alert-warning"><i class="ri-error-warning-line me-1 align-middle"></i>{{ __('settings.mail.log_warning') }}</div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.mail') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="mail_mailer">{{ __('settings.mail.mailer') }}</label>
                                <select name="mail_mailer" id="mail_mailer" class="form-select @error('mail_mailer') is-invalid @enderror" @disabled(! $canEdit)>
                                    @foreach (\App\Services\SiteSettings::MAILERS as $mailer)
                                        <option value="{{ $mailer }}" @selected(old('mail_mailer', $mail['mail_mailer']) === $mailer)>{{ __('settings.mail.mailers.'.$mailer) }}</option>
                                    @endforeach
                                </select>
                                @error('mail_mailer') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="mail_encryption">{{ __('settings.mail.encryption') }}</label>
                                <select name="mail_encryption" id="mail_encryption" class="form-select @error('mail_encryption') is-invalid @enderror" @disabled(! $canEdit)>
                                    @foreach (\App\Services\SiteSettings::ENCRYPTIONS as $encryption)
                                        <option value="{{ $encryption }}" @selected(old('mail_encryption', $mail['mail_encryption']) === $encryption)>{{ __('settings.mail.encryptions.'.$encryption) }}</option>
                                    @endforeach
                                </select>
                                @error('mail_encryption') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @foreach ([
                                'mail_host' => ['host', 'text', 'col-md-8', null],
                                'mail_port' => ['port', 'number', 'col-md-4', null],
                                'mail_username' => ['username', 'text', 'col-md-6', 'off'],
                                'mail_from_address' => ['from_address', 'email', 'col-md-6', null],
                                'mail_from_name' => ['from_name', 'text', 'col-md-6', null],
                            ] as $key => [$label, $type, $column, $autocomplete])
                                <div class="{{ $column }} mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.mail.'.$label) }}</label>
                                    <input type="{{ $type }}" name="{{ $key }}" id="{{ $key }}" @if ($key !== 'mail_from_name') dir="ltr" @endif
                                        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                                        value="{{ old($key, $mail[$key]) }}" @disabled(! $canEdit)
                                        class="form-control @error($key) is-invalid @enderror">
                                    @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach

                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="mail_password">{{ __('settings.mail.password') }}</label>
                                <input type="password" name="mail_password" id="mail_password" dir="ltr" autocomplete="new-password" @disabled(! $canEdit)
                                    class="form-control @error('mail_password') is-invalid @enderror">
                                @error('mail_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if ($hasMailPassword)
                                    <small class="text-muted d-block mt-1">{{ __('settings.mail.password_saved') }}</small>
                                @endif
                            </div>
                        </div>

                        @if ($canEdit)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>

                    @if ($canEdit)
                        <hr class="my-4">
                        <form method="POST" action="{{ route('admin.settings.mail.test') }}" class="d-flex flex-wrap align-items-center gap-3">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">
                                <i class="ri-send-plane-line me-1"></i>{{ __('settings.mail.test') }}
                            </button>
                            <span class="text-muted">{{ __('settings.mail.test_hint', ['email' => auth()->user()->email]) }}</span>
                        </form>
                    @endif
                </div>

                {{-- Security --}}
                <div class="tab-pane @if ($activeTab === 'security') active @endif" id="tab-security" role="tabpanel">
                    <p class="text-muted">{{ __('settings.security.hint') }}</p>
                    <form method="POST" action="{{ route('admin.settings.security') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row">
                            @foreach ($securityDefinitions as $key => $definition)
                                <div class="col-lg-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.fields.'.$key.'.label') }}</label>
                                    <div class="input-group">
                                        <input type="number" name="{{ $key }}" id="{{ $key }}" dir="ltr"
                                            min="{{ $definition['min'] }}" max="{{ $definition['max'] }}"
                                            value="{{ old($key, $security[$key]) }}" @disabled(! $canEdit)
                                            class="form-control @error($key) is-invalid @enderror">
                                        <span class="input-group-text">{{ $key === 'login_max_attempts' ? __('settings.security.attempts') : __('settings.security.minutes') }}</span>
                                        @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <small class="text-muted d-block mt-1">
                                        {{ __('settings.fields.'.$key.'.help') }}
                                        ({{ __('settings.security.range', ['min' => $definition['min'], 'max' => $definition['max']]) }})
                                    </small>
                                </div>
                            @endforeach
                        </div>

                        @if ($canEdit)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>
                </div>

                {{-- Alerts & documents --}}
                <div class="tab-pane @if ($activeTab === 'alerts') active @endif" id="tab-alerts" role="tabpanel">
                    <p class="text-muted">{{ __('settings.alerts.hint') }}</p>
                    <form method="POST" action="{{ route('admin.settings.alerts') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row">
                            @foreach ($alertDefinitions as $key => $definition)
                                <div class="col-lg-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.alerts.fields.'.$key.'.label') }}</label>
                                    <div class="input-group">
                                        <input type="number" name="{{ $key }}" id="{{ $key }}" dir="ltr"
                                            min="{{ $definition['min'] }}" max="{{ $definition['max'] }}"
                                            value="{{ old($key, $alerts[$key]) }}" @disabled(! $canManageAlerts)
                                            class="form-control @error($key) is-invalid @enderror">
                                        <span class="input-group-text">{{ __('settings.alerts.units.'.match (true) { str_ends_with($key, '_hours') => 'hours', str_ends_with($key, '_mb') => 'mb', default => 'days' }) }}</span>
                                        @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <small class="text-muted d-block mt-1">{{ __('settings.alerts.fields.'.$key.'.help') }}</small>
                                </div>
                            @endforeach
                        </div>
                        @if ($canManageAlerts)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>
                </div>

                {{-- Recruitment delivery fees and periods --}}
                <div class="tab-pane @if ($activeTab === 'recruitment') active @endif" id="tab-recruitment" role="tabpanel">
                    <p class="text-muted">{{ __('settings.recruitment.hint') }}</p>
                    <form method="POST" action="{{ route('admin.settings.recruitment') }}" novalidate>
                        @csrf
                        @method('PUT')
                        <div class="row">
                            @foreach ($recruitmentDefinitions as $key => $definition)
                                <div class="col-lg-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.recruitment.fields.'.$key.'.label') }}</label>
                                    <div class="input-group">
                                        <input type="number" name="{{ $key }}" id="{{ $key }}" dir="ltr"
                                            min="{{ $definition['min'] }}" max="{{ $definition['max'] }}"
                                            value="{{ old($key, $recruitment[$key]) }}" @disabled(! $canManageAlerts)
                                            class="form-control @error($key) is-invalid @enderror">
                                        <span class="input-group-text">{{ __('settings.recruitment.units.'.(str_ends_with($key, '_fee') ? 'sar' : 'days')) }}</span>
                                        @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <small class="text-muted d-block mt-1">{{ __('settings.recruitment.fields.'.$key.'.help') }}</small>
                                </div>
                            @endforeach
                        </div>
                        @if ($canManageAlerts)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>
                </div>

                {{-- Backup & maintenance --}}
                <div class="tab-pane @if ($activeTab === 'maintenance') active @endif" id="tab-maintenance" role="tabpanel">
                    {{-- Backups list --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <div>
                            <h5 class="font-size-15 mb-1">{{ __('settings.backup.title') }}</h5>
                            <p class="text-muted mb-0">{{ __('settings.backup.hint') }}</p>
                        </div>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('admin.settings.backups.store') }}">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="ri-download-cloud-2-line me-1"></i>{{ __('settings.backup.create') }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('settings.backup.file') }}</th>
                                    <th>{{ __('settings.backup.type') }}</th>
                                    <th>{{ __('settings.backup.size') }}</th>
                                    <th>{{ __('settings.backup.date') }}</th>
                                    @if ($canEdit)
                                        <th class="text-end">{{ __('common.actions') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($backups as $backup)
                                    <tr>
                                        <td dir="ltr" class="text-start"><code>{{ $backup['name'] }}</code></td>
                                        <td>
                                            <span class="badge {{ $backup['type'] === 'auto' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary' }}">
                                                {{ __('settings.backup.types.'.$backup['type']) }}
                                            </span>
                                        </td>
                                        <td dir="ltr">{{ \Illuminate\Support\Number::fileSize($backup['size'], 1) }}</td>
                                        <td dir="ltr">{{ $backup['created_at']->format('Y-m-d H:i') }}</td>
                                        @if ($canEdit)
                                            <td class="text-end text-nowrap">
                                                <a href="{{ route('admin.settings.backups.download', $backup['name']) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="ri-download-2-line"></i> {{ __('settings.backup.download') }}
                                                </a>
                                                <form method="POST" action="{{ route('admin.settings.backups.destroy', $backup['name']) }}" class="d-inline"
                                                    data-confirm="{{ __('common.confirm_delete') }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="ri-delete-bin-line"></i></button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('settings.backup.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.maintenance') }}" novalidate>
                        @csrf
                        @method('PUT')

                        {{-- Automatic backup --}}
                        <h5 class="font-size-15 mb-3">{{ __('settings.backup.auto_title') }}</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <input type="hidden" name="backup_auto_enabled" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="backup_auto_enabled" id="backup_auto_enabled" value="1"
                                        @checked(old('backup_auto_enabled', $maintenance['backup_auto_enabled'])) @disabled(! $canEdit)>
                                    <label class="form-check-label" for="backup_auto_enabled">{{ __('settings.backup.auto_enabled') }}</label>
                                </div>
                                <small class="text-muted">{{ __('settings.backup.auto_hint') }}</small>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="backup_hour">{{ __('settings.backup.hour') }}</label>
                                <select name="backup_hour" id="backup_hour" class="form-select @error('backup_hour') is-invalid @enderror" @disabled(! $canEdit)>
                                    @foreach (range(0, 23) as $hour)
                                        <option value="{{ $hour }}" @selected((int) old('backup_hour', $maintenance['backup_hour']) === $hour)>{{ sprintf('%02d:00', $hour) }}</option>
                                    @endforeach
                                </select>
                                @error('backup_hour') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-4">
                                <label class="form-label" for="backup_keep">{{ __('settings.backup.keep') }}</label>
                                <div class="input-group">
                                    <input type="number" name="backup_keep" id="backup_keep" dir="ltr" min="1" max="90" @disabled(! $canEdit)
                                        value="{{ old('backup_keep', $maintenance['backup_keep']) }}"
                                        class="form-control @error('backup_keep') is-invalid @enderror">
                                    <span class="input-group-text">{{ __('settings.backup.copies') }}</span>
                                    @error('backup_keep') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <small class="text-muted">{{ __('settings.backup.keep_hint') }}</small>
                            </div>
                        </div>

                        {{-- Retention --}}
                        <h5 class="font-size-15 mb-3">{{ __('settings.retention.title') }}</h5>
                        <div class="row">
                            @foreach (['audit_retention_days', 'login_attempts_retention_days'] as $key)
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.retention.'.$key) }}</label>
                                    <div class="input-group">
                                        <input type="number" name="{{ $key }}" id="{{ $key }}" dir="ltr" min="0" max="3650" @disabled(! $canEdit)
                                            value="{{ old($key, $maintenance[$key]) }}"
                                            class="form-control @error($key) is-invalid @enderror">
                                        <span class="input-group-text">{{ __('settings.retention.days') }}</span>
                                        @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <small class="text-muted">{{ __('settings.retention.hint') }}</small>
                                </div>
                            @endforeach
                        </div>

                        {{-- Maintenance mode --}}
                        <h5 class="font-size-15 mb-3">{{ __('settings.maintenance.title') }}</h5>
                        <div class="row">
                            <div class="col-12 mb-3">
                                <input type="hidden" name="maintenance_enabled" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="maintenance_enabled" id="maintenance_enabled" value="1"
                                        @checked(old('maintenance_enabled', $maintenance['maintenance_enabled'])) @disabled(! $canEdit)>
                                    <label class="form-check-label" for="maintenance_enabled">{{ __('settings.maintenance.enabled') }}</label>
                                </div>
                                <small class="text-muted">{{ __('settings.maintenance.hint') }}</small>
                            </div>
                            @foreach (['maintenance_message_ar' => 'rtl', 'maintenance_message_en' => 'ltr'] as $key => $direction)
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" for="{{ $key }}">{{ __('settings.maintenance.'.$key) }}</label>
                                    <textarea name="{{ $key }}" id="{{ $key }}" rows="3" dir="{{ $direction }}" maxlength="1000" @disabled(! $canEdit)
                                        placeholder="{{ __('errors.503.message', [], $direction === 'rtl' ? 'ar' : 'en') }}"
                                        class="form-control @error($key) is-invalid @enderror">{{ old($key, $maintenance[$key]) }}</textarea>
                                    @error($key) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            @endforeach
                        </div>

                        @if ($canEdit)
                            <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            var input = document.getElementById('primary_color');

            if (! input) {
                return;
            }

            function preview(color) {
                document.getElementById('primary_color_value').textContent = color;
                document.querySelectorAll('#appearance-preview .js-preview-solid').forEach(function (element) {
                    element.style.backgroundColor = color;
                    element.style.borderColor = color;
                });
                document.querySelectorAll('#appearance-preview .js-preview-outline').forEach(function (element) {
                    element.style.color = color;
                    element.style.borderColor = color;
                });
                document.querySelectorAll('#appearance-preview .js-preview-text').forEach(function (element) {
                    element.style.color = color;
                });
            }

            input.addEventListener('input', function () { preview(input.value); });
            document.querySelectorAll('.js-preset-color').forEach(function (button) {
                button.addEventListener('click', function () {
                    input.value = button.dataset.color;
                    preview(input.value);
                });
            });
            preview(input.value);
        })();
    </script>
@endpush
