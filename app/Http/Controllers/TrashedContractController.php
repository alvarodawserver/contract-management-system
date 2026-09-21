<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContractIndexRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TrashedContractController extends Controller
{
    public function index(ContractIndexRequest $request): Response
    {
        Gate::authorize('viewTrashed', Contract::class);

        $user = $request->user();
        $filters = $request->validated();
        $departmentId = $request->integer('department_id') ?: null;

        $contracts = Contract::onlyTrashed()
            ->visibleTo($user)
            ->when($departmentId, fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['search'] ?? null, fn (Builder $query, string $term) => $query->matching($term))
            ->with(['department', 'creator'])
            ->latest('deleted_at')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('contracts/trash', [
            'contracts' => ContractResource::collection($contracts),
            'filters' => [
                'department_id' => $departmentId,
                'search' => $filters['search'] ?? null,
            ],
            'departments' => Department::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function restore(Contract $contract): RedirectResponse
    {
        Gate::authorize('restore', $contract);

        $contract->restore();

        return to_route('contracts.show', $contract);
    }

    public function forceDestroy(Contract $contract): RedirectResponse
    {
        Gate::authorize('forceDelete', $contract);

        $contract->forceDelete();

        return to_route('contracts.trash.index');
    }
}
