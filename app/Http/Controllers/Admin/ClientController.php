<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClientRequest;
use App\Models\Branch;
use App\Models\Client;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SensitiveData;
use App\Support\ProfileTabs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClientController extends Controller
{
    private const FILTERS = ['search', 'national_id', 'branch_id', 'status'];

    private const AUDITED_FIELDS = [
        'name_ar', 'name_en', 'phone', 'phone2', 'email', 'city', 'district', 'national_address', 'branch_id', 'notes', 'is_active',
    ];

    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $filters = $request->only(self::FILTERS);

        return view('admin.clients.index', [
            'clients' => $this->filtered($filters)->with('branch')->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'branches' => $this->branches($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.clients.create', ['client' => new Client(['is_active' => true]), 'branches' => $this->branches($request->user())]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = new Client([...$request->validated(), 'is_active' => $request->boolean('is_active', true)]);
        $client->created_by = $request->user()->id;
        $client->save();

        $this->audit->log('client.created', $client, ['name' => $client->translations('name')], new: $this->auditable($client));

        return redirect()->route('admin.clients.show', $client)->with('success', __('clients.flash.created'));
    }

    public function show(Request $request, Client $client): View
    {
        $client->load(['branch', 'creator']);
        $tabs = ProfileTabs::for('client', $request->user());

        return view('admin.clients.show', [
            'client' => $client,
            'tabs' => $tabs,
            'activeTab' => ProfileTabs::active($tabs, $request->query('tab')),
        ]);
    }

    public function edit(Request $request, Client $client): View
    {
        return view('admin.clients.edit', ['client' => $client, 'branches' => $this->branches($request->user())]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $old = $this->auditable($client);
        $client->update([...$request->validated(), 'is_active' => $request->boolean('is_active')]);
        $new = $this->auditable($client);
        $changed = array_keys(array_diff_assoc(array_map('strval', $new), array_map('strval', $old)));

        if ($changed !== []) {
            $this->audit->log('client.updated', $client, ['name' => $client->translations('name')], array_intersect_key($old, array_flip($changed)), array_intersect_key($new, array_flip($changed)));
        }

        return redirect()->route('admin.clients.show', $client)->with('success', __('clients.flash.updated'));
    }

    public function export(Request $request, SensitiveData $sensitive): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'clients').'.xlsx';
        $writer = SimpleExcelWriter::create($path);

        $this->filtered($request->only(self::FILTERS))->with('branch')->latest()->lazy(200)
            ->each(function (Client $client) use ($writer, $sensitive): void {
                $writer->addRow([
                    __('clients.fields.name_ar') => $client->name_ar,
                    __('clients.fields.name_en') => $client->name_en,
                    __('clients.fields.national_id') => $sensitive->display($client->national_id, 'clients.view_sensitive', 'national_id'),
                    __('clients.fields.phone') => $client->phone,
                    __('clients.fields.phone2') => $client->phone2,
                    __('clients.fields.email') => $client->email,
                    __('clients.fields.city') => $client->city,
                    __('clients.fields.branch_id') => $client->branch?->trans('name'),
                    __('clients.fields.visa_balance') => $client->visa_balance,
                    __('clients.fields.is_active') => $client->is_active ? __('common.active') : __('common.inactive'),
                ]);
            });

        $writer->close();

        return response()->download($path, 'clients-'.now()->format('Y-m-d').'.xlsx')->deleteFileAfterSend();
    }

    /**
     * BranchScope limits the query to the user's branches. The national ID
     * search is exact, through the HMAC lookup column.
     *
     * @param  array<string, string|null>  $filters
     * @return Builder<Client>
     */
    private function filtered(array $filters): Builder
    {
        return Client::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                ->where('name_ar', 'like', "%{$search}%")
                ->orWhere('name_en', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('phone2', 'like', "%{$search}%")))
            ->when($filters['national_id'] ?? null, fn (Builder $query, string $id) => $query->whereNationalId($id))
            ->when($filters['branch_id'] ?? null, fn (Builder $query, string $branch) => $query->where('branch_id', $branch))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query) => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query) => $query->where('is_active', false));
    }

    /**
     * @return Collection<int, Branch>
     */
    private function branches(User $user): Collection
    {
        return Branch::query()->whereKey($user->allowedBranchIds())->orderBy('code')->get();
    }

    /**
     * The national ID is never written in clear: only a short prefix of its
     * HMAC, which shows that it changed.
     *
     * @return array<string, mixed>
     */
    private function auditable(Client $client): array
    {
        return [
            ...array_map(fn ($value) => is_bool($value) ? (int) $value : $value, $client->only(self::AUDITED_FIELDS)),
            'national_id' => $client->national_id_lookup ? substr($client->national_id_lookup, 0, 8) : null,
        ];
    }
}
