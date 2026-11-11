<?php

namespace App\Models;

use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasTranslations;
use App\Services\Documents\DocumentOwner;
use Database\Factories\CandidateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['agency_id', 'name_ar', 'name_en', 'nationality', 'passport_number', 'status'])]
class Candidate extends Model implements DocumentOwner
{
    /** @use HasFactory<CandidateFactory> */
    use HasDocuments, HasFactory, HasTranslations, SoftDeletes;

    /**
     * Country from the 2-letter nationality code.
     */
    public function documentCountryId(): ?int
    {
        return $this->nationality
            ? Country::query()->where('code', strtoupper($this->nationality))->value('id')
            : null;
    }

    /**
     * Candidates have no profession yet (added with recruitment).
     */
    public function documentProfessionId(): ?int
    {
        return null;
    }

    /**
     * @return array{ar: ?string, en: ?string}
     */
    public function documentOwnerName(): array
    {
        return $this->translations('name');
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
