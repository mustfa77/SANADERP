<?php

namespace App\View\Components\Documents;

use App\Models\Authority;
use App\Services\Documents\AuthorityFilePrintService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/**
 * <x-documents.print-button :owner="$model" :authority="$id" />
 *
 * "Print authority file" button. When required documents are missing it
 * opens a modal listing them instead of printing.
 */
class PrintButton extends Component
{
    public Authority $authorityModel;

    /**
     * @var list<string>
     */
    public array $missing;

    public string $ownerAlias;

    public function __construct(public Model $owner, Authority|int $authority)
    {
        $this->authorityModel = $authority instanceof Authority ? $authority : Authority::query()->findOrFail($authority);
        $this->ownerAlias = Checklist::aliasOf($owner);

        $flashed = session('authority_file_missing');
        $this->missing = ($flashed['authority_id'] ?? null) === $this->authorityModel->id
            ? $flashed['names']
            : app(AuthorityFilePrintService::class)->missing($owner, $this->authorityModel);
    }

    public function modalId(): string
    {
        return 'authority-file-missing-'.$this->authorityModel->id;
    }

    public function shouldOpenModal(): bool
    {
        return (session('authority_file_missing')['authority_id'] ?? null) === $this->authorityModel->id;
    }

    public function render(): View|Closure|string
    {
        return view('components.documents.print-button');
    }
}
