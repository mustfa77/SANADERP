<?php

namespace App\Http\Requests\Admin;

use App\Enums\WorkerStatus;
use App\Services\Workers\WorkerStatusService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Manual status change: only targets in workflow.manual that are allowed
 * from the current status, with a required reason.
 */
class ChangeWorkerStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('worker'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $allowed = array_map(
            fn (WorkerStatus $status): string => $status->value,
            app(WorkerStatusService::class)->manualTransitions($this->route('worker')),
        );

        return [
            'status' => ['required', Rule::in($allowed)],
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['status' => __('workers.fields.status'), 'reason' => __('workers.fields.status_reason')];
    }
}
