<?php

namespace App\Services\Recruitment;

use App\Models\Worker;
use App\Services\Documents\ChecklistItem;
use App\Services\Documents\DocumentChecklistService;

/**
 * Document completeness of a recruitment worker (her country and
 * profession checklist): required documents uploaded and missing.
 */
class RecruitmentDocumentProgress
{
    public function __construct(private DocumentChecklistService $checklist) {}

    /**
     * @return array{required: int, uploaded: int, missing: list<string>, percent: int, complete: bool}
     */
    public function for(Worker $worker): array
    {
        $required = $this->checklist->required($worker, $worker->country_id, $worker->profession_id)
            ->filter(fn (ChecklistItem $item): bool => $item->isRequired());

        $missing = $required->reject(fn (ChecklistItem $item): bool => $item->isUploaded())
            ->map(fn (ChecklistItem $item): string => $item->name())
            ->values()
            ->all();

        $total = $required->count();
        $uploaded = $total - count($missing);

        return [
            'required' => $total,
            'uploaded' => $uploaded,
            'missing' => $missing,
            'percent' => $total === 0 ? 100 : (int) floor($uploaded * 100 / $total),
            'complete' => $missing === [],
        ];
    }
}
