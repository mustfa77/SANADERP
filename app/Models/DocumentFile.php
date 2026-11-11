<?php

namespace App\Models;

use Database\Factories\DocumentFileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One stored version of a document, on the private disk.
 */
#[Fillable(['document_id', 'version', 'file_path', 'original_name', 'mime', 'size', 'uploaded_by'])]
class DocumentFile extends Model
{
    /** @use HasFactory<DocumentFileFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * Private disk holding all document files (never publicly reachable).
     */
    public const DISK = 'local';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'size' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime === 'application/pdf';
    }
}
