<?php

namespace App\Models;

use App\Enums\CvCategory;
use App\Enums\Gender;
use App\Enums\LanguageLevel;
use App\Enums\MaritalStatus;
use App\Enums\PassportType;
use App\Enums\Religion;
use App\Enums\SponsorType;
use App\Enums\WorkerRentalType;
use App\Enums\WorkerStatus;
use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasTranslations;
use App\Models\Scopes\BranchScope;
use App\Services\Documents\DocumentOwner;
use App\Support\IdentifierHash;
use Closure;
use Database\Factories\WorkerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Worker file. Branch scoped. "status" is not fillable and may only be
 * changed through App\Services\Workers\WorkerStatusService; any other change
 * throws. Passport / iqama numbers are encrypted and their HMAC lookups are
 * kept in step automatically.
 */
#[Fillable([
    'name_ar', 'name_en', 'gender', 'birth_date', 'religion', 'marital_status',
    'country_id', 'profession_id', 'agency_id', 'branch_id', 'camp_id',
    'passport_no', 'passport_type', 'passport_expiry', 'iqama_no', 'iqama_expiry', 'medical_expiry',
    'languages', 'experience_years', 'skills', 'salary', 'sponsor_type', 'rental_type', 'musaned_cv_no',
])]
class Worker extends Model implements DocumentOwner
{
    /** @use HasFactory<WorkerFactory> */
    use BelongsToBranch, HasDocuments, HasFactory, HasTranslations;

    /**
     * Identifier columns and their lookup column.
     */
    public const IDENTIFIERS = ['passport' => 'passport_no', 'iqama' => 'iqama_no'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'candidate_new',
        'rental_type' => 'none',
        'passport_type' => 'ordinary',
    ];

    private static bool $statusChangeAuthorized = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gender' => Gender::class,
            'religion' => Religion::class,
            'marital_status' => MaritalStatus::class,
            'passport_type' => PassportType::class,
            'sponsor_type' => SponsorType::class,
            'rental_type' => WorkerRentalType::class,
            'status' => WorkerStatus::class,
            'birth_date' => 'date',
            'passport_expiry' => 'date',
            'iqama_expiry' => 'date',
            'medical_expiry' => 'date',
            'status_changed_at' => 'datetime',
            'passport_no' => 'encrypted',
            'iqama_no' => 'encrypted',
            'languages' => 'array',
            'skills' => 'array',
            'experience_years' => 'integer',
            'cv_category' => CvCategory::class,
            'arabic_level' => LanguageLevel::class,
            'english_level' => LanguageLevel::class,
            'cv_skills' => 'array',
            'cv_details' => 'array',
            'children_count' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'integer',
            'cv_updated_at' => 'datetime',
            'salary' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $worker): void {
            if ($worker->status !== WorkerStatus::CandidateNew && ! self::$statusChangeAuthorized) {
                throw new LogicException('A worker starts as candidate_new; use WorkerStatusService::initialize().');
            }
        });

        static::updating(function (self $worker): void {
            if ($worker->isDirty('status') && ! self::$statusChangeAuthorized) {
                throw new LogicException('Worker status can only be changed through WorkerStatusService.');
            }
        });

        static::saving(function (self $worker): void {
            if ($worker->isDirty('passport_no')) {
                $worker->passport_lookup = IdentifierHash::make($worker->passport_no);
            }

            if ($worker->isDirty('iqama_no')) {
                $worker->iqama_lookup = IdentifierHash::make($worker->iqama_no);
            }
        });

        // Every change of housing is kept in the movements log (camps module).
        static::saved(function (self $worker): void {
            if ($worker->wasRecentlyCreated ? $worker->camp_id !== null : $worker->wasChanged('camp_id')) {
                $from = $worker->wasRecentlyCreated ? null : $worker->getOriginal('camp_id');
                CampMovement::record($worker, $from !== null ? (int) $from : null, $worker->camp_id !== null ? (int) $worker->camp_id : null);
            }
        });
    }

    /**
     * @return HasMany<CampMovement, $this>
     */
    public function campMovements(): HasMany
    {
        return $this->hasMany(CampMovement::class)->withoutGlobalScopes()->latest('moved_at')->latest('id');
    }

    /**
     * Run a status change authorised by WorkerStatusService.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function withStatusChange(Closure $callback): mixed
    {
        self::$statusChangeAuthorized = true;

        try {
            return $callback();
        } finally {
            self::$statusChangeAuthorized = false;
        }
    }

    /**
     * Exact match on a passport or iqama number (any case / spacing).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWhereIdentifier(Builder $query, string $type, ?string $value): Builder
    {
        return $query->where("{$type}_lookup", IdentifierHash::make($value) ?? '');
    }

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Agency, $this>
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Camp, $this>
     */
    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<WorkerSalaryChange, $this>
     */
    public function salaryChanges(): HasMany
    {
        return $this->hasMany(WorkerSalaryChange::class)->latest('changed_at')->latest('id');
    }

    /**
     * @return HasMany<WorkerStatusHistory, $this>
     */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(WorkerStatusHistory::class)->latest('changed_at')->latest('id');
    }

    /**
     * CV employment background, in display order.
     *
     * @return HasMany<WorkerExperience, $this>
     */
    public function experiences(): HasMany
    {
        return $this->hasMany(WorkerExperience::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * CV template of the worker's profession (config/cv_templates.php).
     *
     * @return array{skills: list<string>, fields: array<string, array<string, mixed>>}
     */
    public function cvTemplate(): array
    {
        $key = $this->profession?->cv_template ?? 'housemaid';

        return config("cv_templates.{$key}") ?? config('cv_templates.housemaid');
    }

    /**
     * Worker number shown to staff and used in searches.
     */
    public function number(): string
    {
        return 'W-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return HasMany<RecruitmentOrder, $this>
     */
    public function recruitmentOrders(): HasMany
    {
        return $this->hasMany(RecruitmentOrder::class)->withoutGlobalScope(BranchScope::class)->latest();
    }

    /**
     * @return HasMany<RentalContract, $this>
     */
    public function rentalContracts(): HasMany
    {
        return $this->hasMany(RentalContract::class)->withoutGlobalScope(BranchScope::class)->latest('starts_at');
    }

    /**
     * @return HasMany<HourlyBooking, $this>
     */
    public function hourlyBookings(): HasMany
    {
        return $this->hasMany(HourlyBooking::class)->withoutGlobalScope(BranchScope::class)->latest('starts_at');
    }

    public function documentCountryId(): ?int
    {
        return $this->country_id;
    }

    public function documentProfessionId(): ?int
    {
        return $this->profession_id;
    }

    /**
     * @return array{ar: ?string, en: ?string}
     */
    public function documentOwnerName(): array
    {
        return $this->translations('name');
    }

    public function documentSubject(): string
    {
        return 'worker';
    }
}
