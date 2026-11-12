<?php

namespace App\Models;

use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasTranslations;
use App\Models\Concerns\Sortable;
use Database\Factories\DocumentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A document in the configurable checklist. Null country / profession =
 * applies to all. Ordered per country (global types form their own list).
 */
#[Fillable([
    'applies_to', 'country_id', 'profession_id', 'authority_id', 'name_ar', 'name_en', 'sort_order',
    'is_required', 'include_in_authority_file', 'is_active',
])]
class DocumentType extends Model
{
    /** @use HasFactory<DocumentTypeFactory> */
    use HasActiveFlag, HasFactory, HasTranslations, SoftDeletes, Sortable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'include_in_authority_file' => 'boolean',
        ];
    }

    /**
     * Who the type is asked from: workers or clients.
     */
    public const SUBJECTS = ['worker', 'client'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['applies_to' => 'worker'];

    /**
     * Ordered per subject and country (global types form their own list).
     *
     * @return list<string>
     */
    public function sortGroupColumns(): array
    {
        return ['applies_to', 'country_id'];
    }

    /**
     * Types that apply to the given country and profession: the global ones
     * plus the country / profession specific ones.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeApplicableTo(Builder $query, ?int $countryId, ?int $professionId): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('country_id')->when($countryId, fn (Builder $q) => $q->orWhere('country_id', $countryId)))
            ->where(fn (Builder $q) => $q->whereNull('profession_id')->when($professionId, fn (Builder $q) => $q->orWhere('profession_id', $professionId)));
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }

    /**
     * @return BelongsTo<Authority, $this>
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function isInUse(): bool
    {
        return $this->documents()->exists();
    }
}
