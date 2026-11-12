<?php

namespace App\Services\Documents;

use App\Models\DocumentPrint;

/**
 * Outcome of AuthorityFilePrintService::build(): either the list of missing
 * required documents (nothing built) or the saved print.
 */
final readonly class AuthorityFileResult
{
    /**
     * @param  list<string>  $missing  Names of required documents not uploaded.
     * @param  list<string>  $included  Names of documents in the PDF, in order.
     */
    public function __construct(
        public array $missing = [],
        public ?DocumentPrint $print = null,
        public array $included = [],
        public int $pageCount = 0,
    ) {}

    public function isBlocked(): bool
    {
        return $this->missing !== [];
    }
}
