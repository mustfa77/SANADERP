<?php

namespace App\Services\Recruitment;

use App\Enums\DeliveryMethod;
use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentOrder;
use App\Support\PdfDocument;

/**
 * Handover report ("محضر تسليم") signed by the client at delivery. Before
 * delivery it shows the fees estimated for today; afterwards the recorded
 * values.
 */
class RecruitmentHandoverPdf
{
    public function __construct(private RecruitmentService $recruitment) {}

    public function render(RecruitmentOrder $order, DeliveryMethod $method = DeliveryMethod::Office): string
    {
        $order->loadMissing(['client', 'worker.country', 'profession', 'branch', 'arrivalCamp', 'deliverer']);
        $delivered = in_array($order->status, [RecruitmentStatus::Delivered, RecruitmentStatus::Completed, RecruitmentStatus::Returned], true);

        $fees = $delivered
            ? [
                'camp_days' => (int) $order->camp_days,
                'camp_fee' => (float) $order->camp_fee,
                'delivery_fee' => (float) $order->delivery_fee,
                'total' => $order->deliveryFeesTotal(),
            ]
            : $this->recruitment->deliveryQuote($order, today()->toDateString(), $method);

        return PdfDocument::render('recruitment.handover-pdf', [
            'order' => $order,
            'delivered' => $delivered,
            'method' => $delivered ? $order->delivery_method : $method,
            'fees' => $fees,
        ], $order->number);
    }
}
