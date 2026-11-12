<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Support\Reference\ReferenceRegistry;
use App\Support\Reference\ReferenceResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * CRUD for every reference-data list under Settings. The list is chosen by
 * the "resource" route default; permissions are checked by the route
 * middleware (see routes/web.php) from the resource definition.
 */
class ReferenceController extends Controller
{
    public function __construct(private ReferenceRegistry $registry, private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $resource = $this->resource($request);
        $filters = $request->only(['search', 'status', ...array_keys($resource->filters)]);

        $records = $resource->query()
            ->when($filters['search'] ?? null, function (Builder $query, string $search) use ($resource): void {
                $query->where(function (Builder $q) use ($resource, $search): void {
                    foreach ($resource->search as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                });
            })
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false))
            ->tap(function (Builder $query) use ($resource, $filters): void {
                foreach (array_keys($resource->filters) as $column) {
                    if (filled($filters[$column] ?? null)) {
                        $query->where($column, $filters[$column]);
                    }
                }
            })
            ->paginate(20)
            ->withQueryString();

        return view('admin.reference.index', ['resource' => $resource, 'records' => $records, 'filters' => $filters]);
    }

    public function create(Request $request): View
    {
        $resource = $this->resource($request);

        return view('admin.reference.form', ['resource' => $resource, 'record' => new ($resource->model)(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $resource = $this->resource($request);
        $record = $resource->model::query()->create($this->validated($request, $resource, null));

        $this->audit->log('reference.created', $record, $this->params($resource, $record), new: $record->only($resource->columns()));

        return redirect()->route($resource->showRoute ?? $resource->routeName('index'), $resource->showRoute ? $record : [])
            ->with('success', __('reference.flash.created', ['item' => $resource->singular()]));
    }

    public function edit(Request $request, int $record): View
    {
        $resource = $this->resource($request);

        return view('admin.reference.form', ['resource' => $resource, 'record' => $this->find($resource, $record)]);
    }

    public function update(Request $request, int $record): RedirectResponse
    {
        $resource = $this->resource($request);
        $model = $this->find($resource, $record);

        $old = $model->only($resource->columns());
        $model->update($this->validated($request, $resource, $model));
        $changes = array_intersect_key($model->getChanges(), array_flip($resource->columns()));

        if ($changes !== []) {
            $this->audit->log('reference.updated', $model, $this->params($resource, $model), array_intersect_key($old, $changes), $changes);
        }

        return redirect()->route($resource->routeName('index'))->with('success', __('reference.flash.updated', ['item' => $resource->singular()]));
    }

    public function activate(Request $request, int $record): RedirectResponse
    {
        return $this->setActive($request, $record, true);
    }

    public function deactivate(Request $request, int $record): RedirectResponse
    {
        return $this->setActive($request, $record, false);
    }

    public function move(Request $request, int $record): RedirectResponse
    {
        $resource = $this->resource($request);
        $model = $this->find($resource, $record);
        $model->move($request->input('direction') === 'up' ? -1 : 1);

        return back();
    }

    /**
     * Records still referenced elsewhere are not deleted: the user is asked
     * to deactivate them instead.
     */
    public function destroy(Request $request, int $record): RedirectResponse
    {
        $resource = $this->resource($request);
        $model = $this->find($resource, $record);

        if ($model->isInUse()) {
            return back()->with('error', __('reference.flash.in_use', ['item' => $resource->singular()]));
        }

        $model->delete();
        $this->audit->log('reference.deleted', $model, $this->params($resource, $model), $model->only($resource->columns()));

        return redirect()->route($resource->routeName('index'))->with('success', __('reference.flash.deleted', ['item' => $resource->singular()]));
    }

    private function setActive(Request $request, int $record, bool $active): RedirectResponse
    {
        $resource = $this->resource($request);
        $model = $this->find($resource, $record);

        if ($active && $resource->extraRules) {
            $validator = Validator::make(
                ['is_active' => true] + $this->formValues($resource, $model),
                ($resource->extraRules)($this->formValues($resource, $model), $model),
            );

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first());
            }
        }

        $model->update(['is_active' => $active]);
        $this->audit->log(
            $active ? 'reference.activated' : 'reference.deactivated',
            $model,
            $this->params($resource, $model),
            ['is_active' => ! $active],
            ['is_active' => $active],
        );

        return back()->with('success', __($active ? 'reference.flash.activated' : 'reference.flash.deactivated', ['item' => $resource->singular()]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ReferenceResource $resource, ?Model $record): array
    {
        $rules = $resource->rules($record);

        if ($resource->extraRules) {
            $rules = array_merge_recursive($rules, ($resource->extraRules)($request->all(), $record));
        }

        $validated = Validator::make($request->all(), $rules, [], $resource->attributeNames())->validate();

        return [...$validated, 'is_active' => $request->boolean('is_active')];
    }

    /**
     * @return array<string, mixed>
     */
    private function formValues(ReferenceResource $resource, Model $record): array
    {
        return collect($resource->columns())->mapWithKeys(fn (string $column): array => [$column => $resource->formValue($record, $column)])->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function params(ReferenceResource $resource, Model $record): array
    {
        return [
            'resource' => ['ar' => __("reference.resources.{$resource->key}.singular", [], 'ar'), 'en' => __("reference.resources.{$resource->key}.singular", [], 'en')],
            'name' => $resource->recordName($record),
        ];
    }

    private function resource(Request $request): ReferenceResource
    {
        return $this->registry->get((string) $request->route('resource'));
    }

    /**
     * Uses the model's global scopes, so another branch's camp is a 404.
     */
    private function find(ReferenceResource $resource, int $id): Model
    {
        return $resource->model::query()->findOrFail($id);
    }
}
