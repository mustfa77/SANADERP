<?php

namespace App\View\Components\Documents;

use App\Models\Document;
use App\Services\Documents\ChecklistItem;
use App\Services\Documents\DocumentChecklistService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use InvalidArgumentException;

/**
 * <x-documents.checklist :owner="$model" :country="$id" :profession="$id" />
 *
 * The owner's ordered document checklist with upload / view / replace,
 * version history and extra documents.
 */
class Checklist extends Component
{
    /**
     * @var Collection<int, ChecklistItem>
     */
    public Collection $items;

    /**
     * @var Collection<int, Document>
     */
    public Collection $extras;

    public string $ownerAlias;

    public function __construct(
        public Model $owner,
        public ?int $country = null,
        public ?int $profession = null,
    ) {
        $service = app(DocumentChecklistService::class);

        $this->items = $service->required($owner, $country, $profession);
        $this->extras = $service->extras($owner);
        $this->ownerAlias = self::aliasOf($owner);
    }

    public static function aliasOf(Model $owner): string
    {
        foreach (config('documents.owners') as $alias => $definition) {
            if ($owner instanceof $definition['model']) {
                return $alias;
            }
        }

        throw new InvalidArgumentException('Model ['.$owner::class.'] is not a document owner (config/documents.php).');
    }

    public function maxUploadMb(): int
    {
        return (int) (DocumentChecklistService::maxUploadKilobytes() / 1024);
    }

    public function render(): View|Closure|string
    {
        return view('components.documents.checklist');
    }
}
