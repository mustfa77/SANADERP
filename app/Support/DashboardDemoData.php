<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Fixed sample figures for the dashboard (display only) until the
 * recruitment, rental and finance modules exist. Each method mirrors a
 * future real query so it can be replaced one by one.
 */
class DashboardDemoData
{
    /**
     * Headline cards. "financial" cards need reports.view_financial.
     *
     * @return list<array{key: string, icon: string, color: string, value: int|float, change: float, financial: bool}>
     */
    public function kpis(): array
    {
        return [
            ['key' => 'workers_on_duty', 'icon' => 'ri-team-line', 'color' => 'primary', 'value' => 342, 'change' => 4.2, 'financial' => false],
            ['key' => 'active_rentals', 'icon' => 'ri-file-paper-2-line', 'color' => 'success', 'value' => 186, 'change' => 2.8, 'financial' => false],
            ['key' => 'recruitment_in_progress', 'icon' => 'ri-flight-takeoff-line', 'color' => 'info', 'value' => 73, 'change' => -1.5, 'financial' => false],
            ['key' => 'month_revenue', 'icon' => 'ri-money-dollar-circle-line', 'color' => 'warning', 'value' => 485200, 'change' => 6.4, 'financial' => true],
        ];
    }

    /**
     * Last 12 months: rental and recruitment revenue (SAR) and new contracts.
     *
     * @return array{labels: list<string>, rental: list<int>, recruitment: list<int>, contracts: list<int>}
     */
    public function monthlyOverview(): array
    {
        $labels = collect(range(11, 0))
            ->map(fn (int $monthsAgo): string => Carbon::now()->startOfMonth()->subMonths($monthsAgo)->locale(app()->getLocale())->translatedFormat('M'))
            ->all();

        return [
            'labels' => $labels,
            'rental' => [212000, 228500, 219400, 241800, 256300, 249900, 268700, 275400, 281200, 296800, 304500, 318900],
            'recruitment' => [98000, 112500, 104300, 125600, 131200, 118700, 142300, 151800, 139400, 158600, 162100, 166300],
            'contracts' => [38, 42, 40, 47, 51, 46, 55, 58, 54, 61, 63, 67],
        ];
    }

    /**
     * Totals under the overview chart (SAR) with change vs. last month.
     *
     * @return list<array{key: string, color: string, value: int, change: float}>
     */
    public function overviewTotals(): array
    {
        return [
            ['key' => 'rental_revenue', 'color' => 'primary', 'value' => 318900, 'change' => 4.8],
            ['key' => 'recruitment_fees', 'color' => 'info', 'value' => 166300, 'change' => 2.6],
            ['key' => 'refunds', 'color' => 'danger', 'value' => 12400, 'change' => -8.1],
        ];
    }

    /**
     * Workers on duty by nationality.
     *
     * @return list<array{key: string, count: int}>
     */
    public function workersByNationality(): array
    {
        return [
            ['key' => 'philippines', 'count' => 98],
            ['key' => 'indonesia', 'count' => 71],
            ['key' => 'kenya', 'count' => 64],
            ['key' => 'uganda', 'count' => 47],
            ['key' => 'ethiopia', 'count' => 38],
            ['key' => 'sri_lanka', 'count' => 24],
        ];
    }

    /**
     * Open recruitment requests per stage.
     *
     * @return list<array{key: string, icon: string, color: string, count: int}>
     */
    public function recruitmentPipeline(): array
    {
        return [
            ['key' => 'new_request', 'icon' => 'ri-file-add-line', 'color' => 'secondary', 'count' => 18],
            ['key' => 'candidate_selected', 'icon' => 'ri-user-search-line', 'color' => 'info', 'count' => 21],
            ['key' => 'visa_issued', 'icon' => 'ri-passport-line', 'color' => 'primary', 'count' => 15],
            ['key' => 'ticket_booked', 'icon' => 'ri-flight-takeoff-line', 'color' => 'warning', 'count' => 11],
            ['key' => 'arrived', 'icon' => 'ri-map-pin-user-line', 'color' => 'success', 'count' => 8],
        ];
    }

    /**
     * Items needing follow-up.
     *
     * @return list<array{key: string, icon: string, color: string, count: int}>
     */
    public function alerts(): array
    {
        return [
            ['key' => 'iqama_expiring', 'icon' => 'ri-id-card-line', 'color' => 'danger', 'count' => 12],
            ['key' => 'passport_expiring', 'icon' => 'ri-passport-line', 'color' => 'warning', 'count' => 5],
            ['key' => 'rentals_ending', 'icon' => 'ri-calendar-event-line', 'color' => 'info', 'count' => 9],
            ['key' => 'workers_in_camp', 'icon' => 'ri-home-4-line', 'color' => 'primary', 'count' => 14],
            ['key' => 'overdue_invoices', 'icon' => 'ri-bill-line', 'color' => 'danger', 'count' => 7],
            ['key' => 'open_cases', 'icon' => 'ri-briefcase-4-line', 'color' => 'secondary', 'count' => 3],
        ];
    }

    /**
     * Sample figures for the given (real) branches, stable per branch code.
     *
     * @param  Collection<int, Branch>  $branches
     * @return list<array{name: string, code: string, workers: int, contracts: int, revenue: int}>
     */
    public function byBranch(Collection $branches): array
    {
        return $branches->map(function (Branch $branch): array {
            $seed = crc32($branch->code);

            return [
                'name' => $branch->trans('name'),
                'code' => $branch->code,
                'workers' => 80 + $seed % 180,
                'contracts' => 40 + $seed % 110,
                'revenue' => (120 + $seed % 260) * 1000,
            ];
        })->values()->all();
    }

    /**
     * Latest contracts table.
     *
     * @return list<array{number: string, client: string, worker: string, nationality: string, type: string, start: string, amount: int, status: string}>
     */
    public function latestContracts(): array
    {
        $isArabic = app()->getLocale() === 'ar';

        $rows = [
            ['RC-2026-0418', 'عبدالله بن سعد القحطاني', 'Abdullah Al-Qahtani', 'Maria Santos', 'philippines', 'monthly', 3, 2400, 'active'],
            ['RC-2026-0417', 'نورة محمد العتيبي', 'Noura Al-Otaibi', 'Siti Rahayu', 'indonesia', 'hourly', 3, 360, 'active'],
            ['RQ-2026-0129', 'فهد عبدالرحمن الدوسري', 'Fahad Al-Dosari', 'Grace Wanjiru', 'kenya', 'recruitment', 4, 14500, 'pending'],
            ['RC-2026-0416', 'سارة خالد الشهري', 'Sarah Al-Shehri', 'Aster Bekele', 'ethiopia', 'monthly', 5, 2100, 'active'],
            ['RC-2026-0415', 'محمد ناصر الحربي', 'Mohammed Al-Harbi', 'Nakato Sarah', 'uganda', 'monthly', 6, 1950, 'ended'],
            ['RQ-2026-0128', 'ريم عبدالعزيز المطيري', 'Reem Al-Mutairi', 'Dilani Perera', 'sri_lanka', 'recruitment', 7, 13800, 'cancelled'],
        ];

        return array_map(fn (array $row): array => [
            'number' => $row[0],
            'client' => $isArabic ? $row[1] : $row[2],
            'worker' => $row[3],
            'nationality' => $row[4],
            'type' => $row[5],
            'start' => Carbon::today()->subDays($row[6])->format('Y-m-d'),
            'amount' => $row[7],
            'status' => $row[8],
        ], $rows);
    }
}
