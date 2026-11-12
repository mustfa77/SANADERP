<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LookupType;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Rental\PaymentService;
use App\Services\Rental\RentalException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Payments of contracts and bookings, and their receipts (private disk).
 * The payable is loaded with its branch scope, so another branch's
 * contract / booking is a 404.
 */
class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    public function store(Request $request, string $payableType, int $payableId): RedirectResponse
    {
        $payable = $this->payable($request, $payableType, $payableId);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'payment_method_id' => ['required', Rule::exists('lookups', 'id')->where('type', LookupType::PaymentMethod->value)->where('is_active', true)->whereNull('deleted_at')],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.(Setting::integer('document_max_upload_mb') * 1024)],
        ], [], collect(['amount', 'paid_at', 'payment_method_id', 'reference', 'notes', 'receipt'])
            ->mapWithKeys(fn (string $field): array => [$field => __("payments.fields.{$field}")])->all());

        try {
            $this->payments->record($payable, $validated, $request->file('receipt'));
        } catch (RentalException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('payments.flash.recorded'));
    }

    public function receipt(Request $request, Payment $payment): StreamedResponse
    {
        $this->authorizePayable($request, $payment);
        abort_unless($this->payments->receiptExists($payment), 404);

        return Storage::disk('local')->response($payment->receipt_path, $payment->receipt_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizePayable($request, $payment);

        try {
            $this->payments->verify($payment);
        } catch (RentalException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('payments.flash.verified'));
    }

    public function destroy(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorizePayable($request, $payment);

        try {
            $this->payments->delete($payment);
        } catch (RentalException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('payments.flash.deleted'));
    }

    private function payable(Request $request, string $type, int $id): Model
    {
        $config = PaymentService::PAYABLES[$type] ?? abort(404);
        abort_unless($request->user()->can($config['permission']), 403);

        return $config['model']::query()->findOrFail($id);
    }

    /**
     * The payment's contract / booking must be visible to the user too.
     */
    private function authorizePayable(Request $request, Payment $payment): void
    {
        $type = collect(PaymentService::PAYABLES)->search(fn (array $config): bool => $config['model'] === $payment->payable_type);
        abort_if($type === false, 404);
        $this->payable($request, $type, $payment->payable_id);
    }
}
