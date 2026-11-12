<?php

namespace App\Services\Documents;

use RuntimeException;

/**
 * A document could not be merged into the authority file (e.g. a PDF using
 * compression the bundled parser cannot read, with no qpdf available).
 */
class AuthorityFileException extends RuntimeException
{
    public function __construct(public readonly string $documentName)
    {
        parent::__construct(__('documents.flash.cannot_merge', ['name' => $documentName]));
    }
}
