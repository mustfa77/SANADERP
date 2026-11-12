<?php

namespace App\Services\Recruitment;

use App\Enums\RecruitmentStatus;
use App\Models\Authority;
use App\Models\Country;
use App\Models\RecruitmentOrder;
use App\Services\Documents\CompletedVisasProvider;

/**
 * Latest completed visas for the authority file: recruitment orders of the
 * country whose worker has arrived (including delivered, completed and
 * returned orders), newest first.
 */
class RecruitmentCompletedVisas implements CompletedVisasProvider
{
    public function latest(Authority $authority, Country $country, int $count): array
    {
        return RecruitmentOrder::query()
            ->withoutGlobalScopes()
            ->with('worker')
            ->where('country_id', $country->id)
            ->whereIn('status', array_filter(RecruitmentStatus::cases(), fn (RecruitmentStatus $status): bool => $status->hasArrived()))
            ->whereNotNull('visa_number')
            ->orderByDesc('arrived_at')
            ->orderByDesc('id')
            ->limit($count)
            ->get()
            ->map(fn (RecruitmentOrder $order): array => [
                'visa_number' => (string) $order->visa_number,
                'name' => (string) ($order->worker?->name_en ?: $order->worker?->trans('name')),
                'issued_at' => (string) $order->visa_issued_at?->format('Y-m-d'),
            ])
            ->all();
    }
}
