<?php

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\DocumentType;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Builds an owner's ordered document checklist from the configured
 * document types and stores uploads as versioned files on the private disk.
 *
 * Order: global types (no country) first, then the country's types, each
 * by sort_order. Inactive or deleted types are hidden unless the owner
 * already uploaded that document, so existing files stay visible.
 */
class DocumentChecklistService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @return Collection<int, ChecklistItem>
     */
    public function required(Model $owner, ?int $countryId = null, ?int $professionId = null): Collection
    {
        [$countryId, $professionId] = $this->criteria($owner, $countryId, $professionId);

        $documents = $this->ownerDocuments($owner)->whereNotNull('document_type_id')->get()->keyBy('document_type_id');

        $types = DocumentType::query()
            ->withTrashed()
            ->applicableTo($countryId, $professionId)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $active) => $active->where('is_active', true)->whereNull('deleted_at'))
                ->orWhereIn('id', $documents->keys()))
            ->orderByRaw('case when country_id is null then 0 else 1 end')
            ->ordered()
            ->get();

        return $types->map(fn (DocumentType $type): ChecklistItem => new ChecklistItem($type, $documents->get($type->id)))->values();
    }

    /**
     * Extra documents (no type), newest first.
     *
     * @return Collection<int, Document>
     */
    public function extras(Model $owner): Collection
    {
        return $this->ownerDocuments($owner)->whereNull('document_type_id')->latest('uploaded_at')->get();
    }

    /**
     * Names of required documents not uploaded yet.
     *
     * @return list<string>
     */
    public function missingRequired(Model $owner, ?int $countryId = null, ?int $professionId = null): array
    {
        return $this->required($owner, $countryId, $professionId)
            ->filter(fn (ChecklistItem $item): bool => $item->isRequired() && ! $item->isUploaded())
            ->map(fn (ChecklistItem $item): string => $item->name())
            ->values()
            ->all();
    }

    /**
     * Upload a typed document. If it already exists a new version is added
     * and the previous versions are kept.
     */
    public function upload(Model $owner, int $documentTypeId, UploadedFile $file): Document
    {
        $type = DocumentType::query()->withTrashed()->findOrFail($documentTypeId);

        return DB::transaction(function () use ($owner, $type, $file): Document {
            $document = $this->ownerDocuments($owner)->where('document_type_id', $type->id)->lockForUpdate()->first();
            $isReplacement = $document !== null;

            $document ??= $owner->documents()->create(['document_type_id' => $type->id, 'current_version' => 0]);
            $stored = $this->storeVersion($document, $file);

            $this->audit->log(
                $isReplacement ? 'document.replaced' : 'document.uploaded',
                $document,
                ['name' => $type->translations('name'), 'version' => $stored->version],
                new: ['file' => $stored->original_name, 'version' => $stored->version],
            );

            return $document->fresh(['latestFile', 'type']);
        });
    }

    /**
     * Add a document that is not in the checklist (title + file).
     */
    public function addExtra(Model $owner, string $title, UploadedFile $file): Document
    {
        return DB::transaction(function () use ($owner, $title, $file): Document {
            $document = $owner->documents()->create(['title' => $title, 'current_version' => 0]);
            $stored = $this->storeVersion($document, $file);

            $this->audit->log('document.extra_added', $document, ['name' => $title], new: ['file' => $stored->original_name]);

            return $document->fresh(['latestFile']);
        });
    }

    /**
     * Delete one version. The last remaining version cannot be deleted.
     *
     * @throws RuntimeException
     */
    public function deleteVersion(DocumentFile $file): void
    {
        $document = $file->document;

        if ($document->files()->count() <= 1) {
            throw new RuntimeException(__('documents.flash.last_version'));
        }

        DB::transaction(function () use ($file, $document): void {
            Storage::disk(DocumentFile::DISK)->delete($file->file_path);
            $file->delete();

            $document->update(['current_version' => (int) $document->files()->max('version')]);

            $this->audit->log('document.version_deleted', $document, ['name' => $document->type?->translations('name') ?? $document->title, 'version' => $file->version], ['version' => $file->version, 'file' => $file->original_name]);
        });
    }

    public static function maxUploadKilobytes(): int
    {
        return Setting::integer('document_max_upload_mb') * 1024;
    }

    private function storeVersion(Document $document, UploadedFile $file): DocumentFile
    {
        $version = (int) $document->files()->max('version') + 1;
        $directory = config('documents.directory').'/'.$document->getKey();
        $path = $file->storeAs($directory, "v{$version}-".$file->hashName(), DocumentFile::DISK);

        if ($path === false) {
            throw new RuntimeException('The file could not be stored.');
        }

        $stored = $document->files()->create([
            'version' => $version,
            'file_path' => $path,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime' => (string) $file->getMimeType(),
            'size' => (int) $file->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $document->update(['current_version' => $version, 'uploaded_by' => Auth::id(), 'uploaded_at' => now()]);

        return $stored;
    }

    /**
     * @return MorphMany<Document, Model>
     */
    private function ownerDocuments(Model $owner): MorphMany
    {
        return $owner->documents()->with(['latestFile.uploader', 'uploader', 'files.uploader']);
    }

    /**
     * Explicit criteria win; otherwise the owner tells which checklist applies.
     *
     * @return array{0: ?int, 1: ?int}
     */
    private function criteria(Model $owner, ?int $countryId, ?int $professionId): array
    {
        if ($owner instanceof DocumentOwner) {
            $countryId ??= $owner->documentCountryId();
            $professionId ??= $owner->documentProfessionId();
        }

        return [$countryId, $professionId];
    }
}
