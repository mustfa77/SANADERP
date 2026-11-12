<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\DocumentType;

/**
 * One row of an owner's document checklist.
 */
final readonly class ChecklistItem
{
    public function __construct(
        public DocumentType $type,
        public ?Document $document,
    ) {}

    public function isUploaded(): bool
    {
        return $this->document?->latestFile !== null;
    }

    public function isRequired(): bool
    {
        return $this->type->is_required && $this->type->is_active && ! $this->type->trashed();
    }

    public function latestFile(): ?DocumentFile
    {
        return $this->document?->latestFile;
    }

    public function name(): string
    {
        return (string) $this->type->trans('name');
    }
}
