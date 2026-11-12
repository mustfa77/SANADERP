<?php

namespace App\Services\Documents;

/**
 * A model that owns documents in the documents engine (use the
 * HasDocuments trait as well). Tells the engine which checklist applies.
 */
interface DocumentOwner
{
    /**
     * Country whose document types apply (null = global types only).
     */
    public function documentCountryId(): ?int;

    /**
     * Profession whose document types apply (null = all-profession types only).
     */
    public function documentProfessionId(): ?int;

    /**
     * Owner name for printed files, in both languages when available.
     *
     * @return array{ar: ?string, en: ?string}|string
     */
    public function documentOwnerName(): array|string;

    /**
     * Which document types apply: "worker" or "client" (document_types.applies_to).
     */
    public function documentSubject(): string;
}
