<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\Country;
use App\Models\DocumentType;
use App\Models\Profession;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Document checklist configuration. Types of a country are managed from the
 * country page ("Documents" tab); types for all countries from the global
 * list. Order changes apply immediately to upload screens and printing.
 */
class DocumentTypeController extends Controller
{
    private const AUDITED_FIELDS = [
        'country_id', 'profession_id', 'authority_id', 'name_ar', 'name_en',
        'is_required', 'include_in_authority_file', 'is_active',
    ];

    public function __construct(private AuditLogger $audit) {}

    /**
     * Global document types (no country).
     */
    public function index(): View
    {
        return view('admin.reference.document-types.index', [
            'documentTypes' => DocumentType::query()->whereNull('country_id')->with(['profession', 'authority'])->ordered()->get(),
        ]);
    }

    public function create(Request $request): View
    {
        $country = $request->filled('country') ? Country::query()->findOrFail($request->integer('country')) : null;

        return view('admin.reference.document-types.form', [
            'documentType' => new DocumentType(['country_id' => $country?->id, 'is_active' => true]),
            ...$this->formOptions($country),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $documentType = DocumentType::query()->create($this->validated($request, null));
        $this->audit->log('document_type.created', $documentType, $this->params($documentType), new: $documentType->only(self::AUDITED_FIELDS));

        return $this->backToList($documentType, 'reference.flash.created');
    }

    public function edit(DocumentType $documentType): View
    {
        return view('admin.reference.document-types.form', [
            'documentType' => $documentType,
            ...$this->formOptions($documentType->country),
        ]);
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $old = $documentType->only(self::AUDITED_FIELDS);
        $documentType->update($this->validated($request, $documentType));
        $changes = array_intersect_key($documentType->getChanges(), array_flip(self::AUDITED_FIELDS));

        if ($changes !== []) {
            $this->audit->log('document_type.updated', $documentType, $this->params($documentType), array_intersect_key($old, $changes), $changes);
        }

        return $this->backToList($documentType, 'reference.flash.updated');
    }

    public function move(Request $request, DocumentType $documentType): RedirectResponse
    {
        $documentType->move($request->input('direction') === 'up' ? -1 : 1);

        return $this->backToList($documentType);
    }

    /**
     * Deactivated types are hidden for new owners; uploaded files stay.
     */
    public function toggle(DocumentType $documentType): RedirectResponse
    {
        $documentType->update(['is_active' => ! $documentType->is_active]);
        $this->audit->log(
            $documentType->is_active ? 'document_type.activated' : 'document_type.deactivated',
            $documentType,
            $this->params($documentType),
            ['is_active' => ! $documentType->is_active],
            ['is_active' => $documentType->is_active],
        );

        return $this->backToList($documentType, $documentType->is_active ? 'reference.flash.activated' : 'reference.flash.deactivated');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->isInUse()) {
            return back()->with('error', __('reference.flash.in_use', ['item' => __('reference.resources.document_types.singular')]));
        }

        $documentType->delete();
        $this->audit->log('document_type.deleted', $documentType, $this->params($documentType), $documentType->only(self::AUDITED_FIELDS));

        return $this->backToList($documentType, 'reference.flash.deleted');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?Country $country): array
    {
        return [
            'country' => $country,
            'professions' => Profession::query()->ordered()->get(),
            'authorities' => Authority::query()
                ->when($country, fn ($query) => $query->where('country_id', $country->id))
                ->orderBy('name_'.app()->getLocale())
                ->get(),
        ];
    }

    /**
     * The country cannot be changed after creation (it defines the ordering list).
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?DocumentType $documentType): array
    {
        $validated = $request->validate([
            'country_id' => [$documentType ? 'prohibited' : 'nullable', Rule::exists('countries', 'id')->whereNull('deleted_at')],
            'profession_id' => ['nullable', Rule::exists('professions', 'id')->whereNull('deleted_at')],
            'authority_id' => ['nullable', Rule::exists('authorities', 'id')->whereNull('deleted_at')],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ], [], [
            'country_id' => __('reference.fields.country_id'),
            'profession_id' => __('reference.fields.profession_id'),
            'authority_id' => __('reference.fields.authority_id'),
            'name_ar' => __('reference.field_in_language.ar', ['field' => __('reference.fields.name')]),
            'name_en' => __('reference.field_in_language.en', ['field' => __('reference.fields.name')]),
        ]);

        return [
            ...$validated,
            'is_required' => $request->boolean('is_required'),
            'include_in_authority_file' => $request->boolean('include_in_authority_file'),
            'is_active' => $request->boolean('is_active'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function params(DocumentType $documentType): array
    {
        return [
            'name' => $documentType->translations('name'),
            'country' => $documentType->country?->translations('name') ?? ['ar' => __('reference.all_countries', [], 'ar'), 'en' => __('reference.all_countries', [], 'en')],
        ];
    }

    private function backToList(DocumentType $documentType, ?string $message = null): RedirectResponse
    {
        $redirect = $documentType->country_id
            ? redirect()->route('admin.countries.show', [$documentType->country_id, 'tab' => 'documents'])
            : redirect()->route('admin.document-types.index');

        return $message ? $redirect->with('success', __($message, ['item' => __('reference.resources.document_types.singular')])) : $redirect;
    }
}
