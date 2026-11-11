<?php

namespace App\Models\Concerns;

use App\Models\Document;
use App\Models\DocumentPrint;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Owner of documents in the documents engine (candidate, worker, ...).
 */
trait HasDocuments
{
    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    /**
     * @return MorphMany<DocumentPrint, $this>
     */
    public function documentPrints(): MorphMany
    {
        return $this->morphMany(DocumentPrint::class, 'documentable')->latest('printed_at');
    }
}
