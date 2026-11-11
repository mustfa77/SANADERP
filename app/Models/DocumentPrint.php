<?php

namespace App\Models;

use Database\Factories\DocumentPrintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * A generated authority file (merged PDF) for an owner.
 */
#[Fillable(['documentable_type', 'documentable_id', 'authority_id', 'file_path', 'printed_by', 'printed_at'])]
class DocumentPrint extends Model
{
    /** @use HasFactory<DocumentPrintFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'printed_at' => 'datetime',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Authority, $this>
     */
    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function printer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
}
