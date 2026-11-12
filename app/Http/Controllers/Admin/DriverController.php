<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TripStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DriverProfile;
use App\Models\Trip;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Drivers: users of type "driver" with a vehicle profile (branch scoped
 * through the profile). Each driver logs in to the driver portal.
 */
class DriverController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $profiles = DriverProfile::query()->with(['user', 'branch'])->orderByDesc('is_active')->get();
        $today = Trip::query()->whereDate('scheduled_at', today())->where('status', '!=', TripStatus::Cancelled)->whereNotNull('driver_id')
            ->selectRaw('driver_id, count(*) as total')->groupBy('driver_id')->pluck('total', 'driver_id');

        return view('admin.drivers.index', [
            'profiles' => $profiles,
            'today' => $today,
            'branches' => Branch::query()->whereKey($request->user()->allowedBranchIds())->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20', 'regex:/^[0-9+\s-]+$/'],
            'password' => ['required', Password::defaults()],
            'branch_id' => ['required', 'integer', Rule::in($request->user()->allowedBranchIds())],
            ...$this->profileRules(),
        ], [], $this->attributes());

        DB::transaction(function () use ($validated, $request): void {
            $user = User::query()->create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
                'user_type' => UserType::Driver,
                'status' => UserStatus::Active,
                'must_change_password' => true,
                'created_by' => $request->user()->getKey(),
            ]);
            $user->branches()->sync([$validated['branch_id']]);

            $profile = new DriverProfile($this->profileData($validated));
            $profile->forceFill(['user_id' => $user->getKey()])->save();

            $this->audit->log('driver.created', $user, ['name' => $user->translations('name')], new: $profile->only(['vehicle_model', 'vehicle_plate']));
        });

        return redirect()->route('admin.drivers.index')->with('success', __('transport.flash.driver_created'));
    }

    public function update(Request $request, DriverProfile $driver): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::in($request->user()->allowedBranchIds())],
            'is_active' => ['nullable', 'boolean'],
            ...$this->profileRules(),
        ], [], $this->attributes());

        $old = $driver->only(['branch_id', 'vehicle_model', 'vehicle_plate', 'is_active']);
        $driver->fill([...$this->profileData($validated), 'is_active' => $request->boolean('is_active')])->save();
        $driver->user->branches()->sync([$validated['branch_id']]);

        $this->audit->log('driver.updated', $driver->user, ['name' => $driver->user->translations('name')], $old, $driver->only(array_keys($old)));

        return redirect()->route('admin.drivers.index')->with('success', __('transport.flash.driver_updated'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'license_number' => ['nullable', 'string', 'max:30'],
            'license_expiry' => ['nullable', 'date'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'vehicle_plate' => ['nullable', 'string', 'max:20'],
            'seats' => ['nullable', 'integer', 'min:1', 'max:60'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function profileData(array $validated): array
    {
        return collect($validated)->only(['branch_id', 'license_number', 'license_expiry', 'vehicle_model', 'vehicle_plate', 'seats'])->all();
    }

    /**
     * @return array<string, string>
     */
    private function attributes(): array
    {
        return collect(['name_ar', 'name_en', 'email', 'phone', 'password', 'branch_id', 'license_number', 'license_expiry', 'vehicle_model', 'vehicle_plate', 'seats'])
            ->mapWithKeys(fn (string $field): array => [$field => __("transport.driver_fields.{$field}")])->all();
    }
}
