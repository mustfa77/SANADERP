<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\UserAccessManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Fields compared for the "user updated" audit entry.
     */
    private const AUDITED_FIELDS = [
        'name_ar', 'name_en', 'email', 'phone', 'user_type', 'agency_id', 'preferred_language', 'must_change_password',
    ];

    public function __construct(
        private AuditLogger $audit,
        private UserAccessManager $access,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'role', 'branch', 'status', 'type']);

        $users = User::query()
            ->with(['roles', 'branches', 'permissions'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(fn ($q) => $q
                    ->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%"));
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->role($role))
            ->when($filters['branch'] ?? null, fn ($query, string $branch) => $query->whereHas('branches', fn ($q) => $q->whereKey($branch)))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('user_type', $type))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $filters,
            ...$this->formOptions($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.create', [
            'user' => new User(['user_type' => UserType::Internal, 'preferred_language' => 'ar', 'must_change_password' => true]),
            ...$this->formOptions($request->user()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create([
                ...$request->safe()->only([...self::AUDITED_FIELDS, 'password']),
                'status' => UserStatus::Active,
                'created_by' => $request->user()->getKey(),
                'avatar' => $request->file('avatar')?->store('avatars', 'public'),
            ]);

            $this->audit->log('user.created', $user, ['name' => $user->translations('name')], new: $user->only(self::AUDITED_FIELDS));
            $this->syncAccess($request, $user);

            return $user;
        });

        return redirect()->route('admin.users.show', $user)->with('success', __('users.flash.created'));
    }

    public function show(User $user): View
    {
        $user->load(['roles', 'branches', 'agency', 'creator', 'permissions']);

        return view('admin.users.show', [
            'user' => $user,
            'loginAttempts' => $user->loginAttempts()->latest('created_at')->limit(20)->get(),
            'recentActions' => $user->auditLogs()->latest('created_at')->limit(20)->get(),
            'changes' => AuditLog::query()
                ->with('user')
                ->where('target_type', $user->getMorphClass())
                ->where('target_id', $user->getKey())
                ->latest('created_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        $user->load(['roles', 'branches', 'permissions']);

        return view('admin.users.edit', [
            'user' => $user,
            ...$this->formOptions($request->user()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $old = $this->auditedValues($user);

            $user->fill($request->safe()->only(self::AUDITED_FIELDS));

            if ($request->filled('password')) {
                $user->password = $request->string('password');
            }

            if ($request->hasFile('avatar')) {
                $user->avatar = $request->file('avatar')->store('avatars', 'public');
            }

            $passwordChanged = $user->isDirty('password');
            $user->save();

            $new = $this->auditedValues($user);
            $changed = array_keys(array_diff_assoc(array_map('strval', $new), array_map('strval', $old)));

            if ($changed !== [] || $passwordChanged) {
                $this->audit->log(
                    'user.updated',
                    $user,
                    ['name' => $user->translations('name')],
                    array_intersect_key($old, array_flip($changed)) + ($passwordChanged ? ['password' => '••••'] : []),
                    array_intersect_key($new, array_flip($changed)) + ($passwordChanged ? ['password' => '••••'] : []),
                );
            }

            $this->syncAccess($request, $user);
        });

        return redirect()->route('admin.users.show', $user)->with('success', __('users.flash.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        EnsureUserIsActive::endSessions($user);
        $user->delete();

        $this->audit->log('user.deleted', $user, ['name' => $user->translations('name')]);

        return redirect()->route('admin.users.index')->with('success', __('users.flash.deleted'));
    }

    public function activate(User $user): RedirectResponse
    {
        $this->changeStatus($user, UserStatus::Active, 'user.activated');

        return back()->with('success', __('users.flash.activated'));
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->changeStatus($user, UserStatus::Inactive, 'user.deactivated');
        EnsureUserIsActive::endSessions($user);

        return back()->with('success', __('users.flash.deactivated'));
    }

    /**
     * Email a password reset link and, optionally, force a change on next login.
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if ($request->boolean('force_change')) {
            $user->forceFill(['must_change_password' => true])->save();
        }

        $status = Password::sendResetLink(['email' => $user->email]);

        $this->audit->log('user.reset_link_sent', $user, ['name' => $user->translations('name')]);

        return $status === Password::ResetLinkSent
            ? back()->with('success', __('users.flash.reset_link_sent'))
            : back()->with('error', __($status));
    }

    private function syncAccess(UserRequest $request, User $user): void
    {
        if (! $request->canAssignAccess()) {
            return;
        }

        $this->access->sync(
            $user,
            $request->has('roles') || $request->has('access_submitted') ? $request->array('roles') : null,
            $request->has('branches') || $request->has('access_submitted') ? $request->array('branches') : null,
            $request->has('access_submitted') ? $request->boolean('all_branches') : null,
        );
    }

    private function changeStatus(User $user, UserStatus $status, string $action): void
    {
        $old = $user->status;
        $user->forceFill(['status' => $status])->save();

        $this->audit->log($action, $user, ['name' => $user->translations('name')], ['status' => $old->value], ['status' => $status->value]);
    }

    /**
     * @return array<string, scalar|null>
     */
    private function auditedValues(User $user): array
    {
        return collect($user->only(self::AUDITED_FIELDS))
            ->map(fn ($value) => $value instanceof \BackedEnum ? $value->value : $value)
            ->all();
    }

    /**
     * Options for the filter bar and the user form.
     *
     * @return array<string, mixed>
     */
    private function formOptions(User $actor): array
    {
        return [
            'roles' => Role::query()
                ->when(! $actor->isSuperAdmin(), fn ($q) => $q->where('name', '!=', config('permissions.super_admin_role')))
                ->orderBy('id')
                ->get(),
            'branches' => Branch::query()->orderBy('code')->get(),
            'agencies' => Agency::query()->orderBy('name_en')->get(),
            'statuses' => UserStatus::cases(),
            'types' => UserType::cases(),
        ];
    }
}
