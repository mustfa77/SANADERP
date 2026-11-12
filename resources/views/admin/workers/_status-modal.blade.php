{{-- Manual status change: only allowed transitions that are in workflow.manual. --}}
<div class="modal fade" id="change-status-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.workers.status', $worker) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('workers.change_status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ __('workers.current_status') }}: <x-worker-status :status="$worker->status" /></p>
                <div class="mb-3">
                    <label class="form-label" for="new_status">{{ __('workers.new_status') }} <span class="text-danger">*</span></label>
                    <select name="status" id="new_status" required class="form-select @error('status') is-invalid @enderror">
                        <option value="">{{ __('common.select') }}</option>
                        @foreach ($manualTransitions as $transition)
                            <option value="{{ $transition->value }}" @selected(old('status') === $transition->value)>{{ $transition->label() }}</option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-0">
                    <label class="form-label" for="status_reason">{{ __('workers.fields.status_reason') }} <span class="text-danger">*</span></label>
                    <textarea name="reason" id="status_reason" rows="3" required maxlength="1000" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                    @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
            </div>
        </form>
    </div>
</div>

@if ($errors->hasAny(['status', 'reason']))
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('change-status-modal')).show();
            });
        </script>
    @endpush
@endif
