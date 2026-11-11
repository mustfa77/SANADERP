<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An uploaded document of any owner (candidate, worker...). Typed documents
 * follow a DocumentType; extra documents have only a title. Every upload or
 * replacement is a new DocumentFile version; old versions are kept.
 */
#[Fillable([
    'documentable_type', 'documentable_id', 'document_type_id', 'title',
    'current_version', 'uploaded_by', 'uploaded_at',
])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
            'uploaded_at' => 'datetime',
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
     * Includes inactive / soft-deleted types so existing files keep their name.
     *
     * @return BelongsTo<DocumentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * All versions, newest first.
     *
     * @return HasMany<DocumentFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(DocumentFile::class)->orderByDesc('version');
    }

    /**
     * @return HasOne<DocumentFile, $this>
     */
    public function latestFile(): HasOne
    {
        return $this->hasOne(DocumentFile::class)->latestOfMany('version');
    }

    /**
     * Type name in the current locale, or the title of an extra document.
     */
    public function displayName(): string
    {
        return $this->type?->trans('name') ?? (string) $this->title;
    }

    public function isExtra(): bool
    {
        return $this->document_type_id === null;
    }
}
