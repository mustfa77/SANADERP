<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\DocumentPrint;
use App\Models\DocumentType;
use App\Services\Documents\AuthorityFileException;
use App\Services\Documents\AuthorityFilePrintService;
use App\Services\Documents\DocumentChecklistService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Documents engine endpoints. Files live on the private disk and are only
 * streamed through here, after checking the documents.* permission, the
 * owner's permission (config/documents.php) and the owner's scopes (an
 * owner outside the user's branches is a 404).
 */
class DocumentController extends Controller
{
    public function __construct(private DocumentChecklistService $checklist) {}

    /**
     * Upload a typed document; replacing an existing one needs documents.replace.
     */
    public function upload(Request $request, string $ownerType, int $ownerId, DocumentType $documentType): RedirectResponse
    {
        $owner = $this->owner($request, $ownerType, $ownerId);
        $exists = $owner->documents()->where('document_type_id', $documentType->id)->exists();

        abort_if($exists && ! $request->user()->can('documents.replace'), 403);

        $errorKey = 'document_'.$documentType->id;
        $validator = Validator::make($request->all(), $this->fileRules(), [], ['file' => $documentType->trans('name')]);

        if ($validator->fails()) {
            return back()->withErrors([$errorKey => $validator->errors()->first('file')]);
        }

        $this->checklist->upload($owner, $documentType->id, $request->file('file'));

        return back()->with('success', __($exists ? 'documents.flash.replaced' : 'documents.flash.uploaded', ['name' => $documentType->trans('name')]));
    }

    public function extra(Request $request, string $ownerType, int $ownerId): RedirectResponse
    {
        $owner = $this->owner($request, $ownerType, $ownerId);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            ...$this->fileRules(),
        ], [], ['title' => __('documents.fields.title'), 'file' => __('documents.fields.file')]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'extraDocument')->withInput();
        }

        $this->checklist->addExtra($owner, $request->string('title')->trim()->toString(), $request->file('file'));

        return back()->with('success', __('documents.flash.uploaded', ['name' => $request->input('title')]));
    }

    /**
     * Stream one version inline (PDF / image preview in the browser).
     */
    public function show(Request $request, DocumentFile $documentFile): StreamedResponse
    {
        $this->authorizeOwnerOf($request, $documentFile->document);

        return Storage::disk(DocumentFile::DISK)->response(
            $documentFile->file_path,
            $documentFile->original_name,
            ['Content-Type' => $documentFile->mime, 'X-Content-Type-Options' => 'nosniff'],
            'inline',
        );
    }

    public function destroyVersion(Request $request, DocumentFile $documentFile): RedirectResponse
    {
        $this->authorizeOwnerOf($request, $documentFile->document);

        try {
            $this->checklist->deleteVersion($documentFile);
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('documents.flash.version_deleted', ['version' => $documentFile->version]));
    }

    /**
     * Build the authority file. Missing required documents are flashed for
     * the print button's modal instead of building.
     */
    public function print(Request $request, AuthorityFilePrintService $printer, string $ownerType, int $ownerId, Authority $authority): RedirectResponse|StreamedResponse
    {
        $owner = $this->owner($request, $ownerType, $ownerId);

        try {
            $result = $printer->build($owner, $authority);
        } catch (AuthorityFileException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($result->isBlocked()) {
            return back()->with('authority_file_missing', ['authority_id' => $authority->id, 'names' => $result->missing]);
        }

        return $this->downloadPrint($result->print);
    }

    public function downloadStoredPrint(Request $request, DocumentPrint $documentPrint): StreamedResponse
    {
        $owner = $documentPrint->documentable()->first();
        abort_if($owner === null, 404);
        $this->owner($request, $this->aliasOf($owner), (int) $owner->getKey());

        return $this->downloadPrint($documentPrint);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function fileRules(): array
    {
        return ['file' => ['required', 'file', 'mimes:'.implode(',', config('documents.mimes')), 'max:'.DocumentChecklistService::maxUploadKilobytes()]];
    }

    private function downloadPrint(DocumentPrint $print): StreamedResponse
    {
        $name = 'authority-file-'.$print->authority_id.'-'.$print->printed_at->format('Y-m-d-His').'.pdf';

        return Storage::disk(DocumentFile::DISK)->download($print->file_path, $name, ['Content-Type' => 'application/pdf']);
    }

    /**
     * Resolve the owner from its alias, checking the owner permission. The
     * query keeps global scopes, so out-of-branch owners are a 404.
     */
    private function owner(Request $request, string $alias, int $id): Model
    {
        $definition = config("documents.owners.{$alias}");
        abort_unless(is_array($definition), 404);
        abort_unless($request->user()->can($definition['permission']), 403);

        return $definition['model']::query()->findOrFail($id);
    }

    private function authorizeOwnerOf(Request $request, Document $document): void
    {
        $owner = $document->documentable()->first();
        abort_if($owner === null, 404);
        $this->owner($request, $this->aliasOf($owner), (int) $owner->getKey());
    }

    private function aliasOf(Model $owner): string
    {
        foreach (config('documents.owners') as $alias => $definition) {
            if ($owner instanceof $definition['model']) {
                return $alias;
            }
        }

        abort(404);
    }
}
