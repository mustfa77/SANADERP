<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BranchRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BranchController extends Controller
{
    private const AUDITED_FIELDS = [
        'code', 'name_ar', 'name_en', 'city_ar', 'city_en', 'address_ar', 'address_en',
        'phone', 'email', 'manager_id', 'is_active',
    ];

    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'status']);

        $branches = $this->filtered($request->user(), $filters)
            ->with('manager')
            ->withCount(['users', 'workers'])
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('admin.branches.index', ['branches' => $branches, 'filters' => $filters]);
    }

    public function create(): View
    {
        return view('admin.branches.create', ['branch' => new Branch(['is_active' => true]), 'managers' => $this->managers()]);
    }

    public function store(BranchRequest $request): RedirectResponse
    {
        $branch = Branch::query()->create([...$request->validated(), 'is_active' => $request->boolean('is_active')]);

        $this->audit->log('branch.created', $branch, ['branch' => $branch->translations('name')], new: $branch->only(self::AUDITED_FIELDS));

        return redirect()->route('admin.branches.show', $branch)->with('success', __('branches.flash.created'));
    }

    public function show(Branch $branch): View
    {
        $branch->load('manager')->loadCount(['users', 'workers', 'trips']);

        return view('admin.branches.show', [
            'branch' => $branch,
            'users' => $branch->users()->with('roles')->orderBy('name_'.app()->getLocale())->limit(50)->get(),
        ]);
    }

    public function edit(Branch $branch): View
    {
        return view('admin.branches.edit', ['branch' => $branch, 'managers' => $this->managers($branch->manager_id)]);
    }

    public function update(BranchRequest $request, Branch $branch): RedirectResponse
    {
        $isActive = $request->boolean('is_active');

        if ($branch->is_active && ! $isActive && $this->isLastActiveBranch($branch)) {
            return back()->withInput()->with('error', __('branches.flash.last_active'));
        }

        $old = $branch->only(self::AUDITED_FIELDS);
        $branch->update([...$request->validated(), 'is_active' => $isActive]);
        $changes = array_intersect_key($branch->getChanges(), array_flip(self::AUDITED_FIELDS));

        if ($changes !== []) {
            $this->audit->log('branch.updated', $branch, ['branch' => $branch->translations('name')], array_intersect_key($old, $changes), $changes);
        }

        return redirect()->route('admin.branches.show', $branch)->with('success', __('branches.flash.updated'));
    }

    public function activate(Branch $branch): RedirectResponse
    {
        $branch->update(['is_active' => true]);
        $this->audit->log('branch.activated', $branch, ['branch' => $branch->translations('name')], ['is_active' => false], ['is_active' => true]);

        return back()->with('success', __('branches.flash.activated'));
    }

    public function deactivate(Branch $branch): RedirectResponse
    {
        if ($this->isLastActiveBranch($branch)) {
            return back()->with('error', __('branches.flash.last_active'));
        }

        $branch->update(['is_active' => false]);
        $this->audit->log('branch.deactivated', $branch, ['branch' => $branch->translations('name')], ['is_active' => true], ['is_active' => false]);

        return back()->with('success', __('branches.flash.deactivated'));
    }

    /**
     * Refuses to delete a branch that still has users, workers or trips,
     * or the last active branch.
     */
    public function destroy(Branch $branch): RedirectResponse
    {
        $branch->loadCount(['users', 'workers', 'trips']);

        if ($branch->users_count + $branch->workers_count + $branch->trips_count > 0) {
            return back()->with('error', __('branches.flash.in_use', [
                'users' => $branch->users_count,
                'workers' => $branch->workers_count,
                'trips' => $branch->trips_count,
            ]));
        }

        if ($branch->is_active && $this->isLastActiveBranch($branch)) {
            return back()->with('error', __('branches.flash.last_active'));
        }

        $branch->delete();
        $this->audit->log('branch.deleted', $branch, ['branch' => $branch->translations('name')], $branch->only(self::AUDITED_FIELDS));

        return redirect()->route('admin.branches.index')->with('success', __('branches.flash.deleted'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'branches').'.xlsx';
        $writer = SimpleExcelWriter::create($path);

        $this->filtered($request->user(), $request->only(['search', 'status']))
            ->with('manager')
            ->withCount(['users', 'workers'])
            ->orderBy('code')
            ->lazy(200)
            ->each(function (Branch $branch) use ($writer): void {
                $writer->addRow([
                    __('branches.fields.code') => $branch->code,
                    __('branches.fields.name') => $branch->trans('name'),
                    __('branches.fields.city') => $branch->trans('city'),
                    __('branches.fields.address') => $branch->trans('address'),
                    __('branches.fields.phone') => $branch->phone,
                    __('branches.fields.email') => $branch->email,
                    __('branches.fields.manager') => $branch->manager?->displayName(),
                    __('branches.fields.users_count') => $branch->users_count,
                    __('branches.fields.workers_count') => $branch->workers_count,
                    __('branches.fields.status') => $branch->is_active ? __('branches.active') : __('branches.inactive'),
                ]);
            });

        $writer->close();

        return response()->download($path, 'branches-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * Branches the user may see, with the list filters applied.
     *
     * @param  array<string, string|null>  $filters
     * @return Builder<Branch>
     */
    private function filtered(User $user, array $filters): Builder
    {
        return Branch::query()
            ->when(! $user->hasAllBranches(), fn (Builder $query) => $query->whereKey($user->allowedBranchIds()))
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $q) => $q
                    ->where('code', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('city_ar', 'like', "%{$search}%")
                    ->orWhere('city_en', 'like', "%{$search}%"));
            })
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false));
    }

    /**
     * Active internal users, plus the current manager if no longer eligible.
     *
     * @return Collection<int, User>
     */
    private function managers(?int $currentManagerId = null): Collection
    {
        return User::query()
            ->where(fn (Builder $query) => $query
                ->where('user_type', UserType::Internal)
                ->where('status', UserStatus::Active)
                ->when($currentManagerId, fn (Builder $q) => $q->orWhereKey($currentManagerId)))
            ->orderBy('name_'.app()->getLocale())
            ->get();
    }

    private function isLastActiveBranch(Branch $branch): bool
    {
        return $branch->is_active && ! Branch::query()->whereKeyNot($branch->getKey())->where('is_active', true)->exists();
    }
}
