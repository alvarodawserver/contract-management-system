<?php

namespace App\Http\Controllers;

use App\Enums\ContractStatus;
use App\Http\Requests\ContractIndexRequest;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    public function index(ContractIndexRequest $request): Response
    {
        $user = $request->user();
        $filters = $request->validated();
        $departmentId = $request->integer('department_id') ?: null;

        $contracts = Contract::query()
            ->visibleTo($user)
            ->when($departmentId, fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $status === ContractStatus::Formalized->value
                ? $query->formalized()
                : $query->pending())
            ->when($filters['search'] ?? null, fn (Builder $query, string $term) => $query->matching($term))
            ->with(['department', 'creator'])
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('contracts/index', [
            'contracts' => ContractResource::collection($contracts),
            'filters' => [
                'department_id' => $departmentId,
                'status' => $filters['status'] ?? null,
                'search' => $filters['search'] ?? null,
            ],
            'departments' => Department::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Contract::class);

        return Inertia::render('contracts/create', [
            'departments' => Department::query()->visibleTo($request->user())->orderBy('name')->get(['id', 'name', 'code']),
            'formalizationMonths' => config('contracts.formalization_months'),
        ]);
    }

    public function store(StoreContractRequest $request): RedirectResponse
    {
        $contract = Contract::create([
            ...$request->safe()->except('department_id'),
            'department_id' => $request->departmentId(),
            'created_by' => $request->user()->id,
        ]);

        return to_route('contracts.show', $contract);
    }

    public function show(Contract $contract): Response
    {
        Gate::authorize('view', $contract);

        $contract->load(['department', 'creator']);

        return Inertia::render('contracts/show', [
            'contract' => ContractResource::make($contract),
        ]);
    }

    public function edit(Contract $contract): Response
    {
        Gate::authorize('update', $contract);

        return Inertia::render('contracts/edit', [
            'contract' => ContractResource::make($contract->load('department')),
        ]);
    }

    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        $contract->update($request->validated());

        return to_route('contracts.show', $contract);
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        Gate::authorize('delete', $contract);

        $contract->delete();

        return to_route('contracts.index');
    }
}
