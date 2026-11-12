{{-- Candidate review (candidates.approve): workflow.review transitions only. Rejection needs a reason. --}}
<div class="modal fade" id="review-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('admin.workers.review', $worker) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('workers.review.title') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ __('workers.current_status') }}: <x-worker-status :status="$worker->status" /></p>
                <div class="d-flex flex-column gap-2 mb-3">
                    @foreach ($reviewTransitions as $transition)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" id="review-{{ $transition->value }}" value="{{ $transition->value }}" required>
                            <label class="form-check-label" for="review-{{ $transition->value }}">{{ __('workers.review.actions.'.$transition->value) }}</label>
                        </div>
                    @endforeach
                </div>
                <label class="form-label" for="review_reason">{{ __('workers.fields.status_reason') }}</label>
                <textarea name="reason" id="review_reason" rows="2" maxlength="1000" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                <div class="form-text">{{ __('workers.review.reason_hint') }}</div>
                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('common.save') }}</button>
            </div>
        </form>
    </div>
</div>
