<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Models\Concerns\HasActiveFlag;
use App\Models\Concerns\HasDocuments;
use App\Models\Concerns\HasTranslations;
use App\Services\Documents\DocumentOwner;
use App\Support\IdentifierHash;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Client file (sponsor / renter). Branch scoped. The national ID is
 * encrypted and its HMAC lookup is kept in step automatically. visa_balance
 * is managed by the recruitment module, not by the client form.
 */
#[Fillable([
    'name_ar', 'name_en', 'national_id', 'phone', 'phone2', 'email', 'city', 'district',
    'national_address', 'branch_id', 'notes', 'is_active',
])]
class Client extends Model implements DocumentOwner
{
    /** @use HasFactory<ClientFactory> */
    use BelongsToBranch, HasActiveFlag, HasDocuments, HasFactory, HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'national_id' => 'encrypted',
            'visa_balance' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $client): void {
            if ($client->isDirty('national_id')) {
                $client->national_id_lookup = IdentifierHash::make($client->national_id);
            }
        });
    }

    /**
     * Exact match on the national ID (any spacing).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeWhereNationalId(Builder $query, ?string $value): Builder
    {
        return $query->where('national_id_lookup', IdentifierHash::make($value) ?? '');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Reserved for the optional client portal.
     *
     * @return BelongsTo<User, $this>
     */
    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documentCountryId(): ?int
    {
        return null;
    }

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

    public function documentSubject(): string
    {
        return 'client';
    }
}
