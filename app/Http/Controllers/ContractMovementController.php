<?php

namespace App\Http\Controllers;

use App\Enums\RoleSlug;
use App\Http\Resources\ContractMovementResource;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\ContractMovement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ContractMovementController extends Controller
{
    /**
     * Every movement across every contract the user can see: a management view, like the
     * trash. A permanently deleted contract has no department to scope by anymore, so only
     * an admin (who needs no scoping) still sees its trace here.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewMovementLog', Contract::class);

        $user = $request->user();

        $movements = ContractMovement::query()
            ->when(
                ! $user->hasRole(RoleSlug::Admin),
                fn (Builder $query) => $query->whereIn(
                    'contract_id',
                    Contract::withTrashed()->visibleTo($user)->select('id'),
                ),
            )
            ->with(['contract', 'user'])
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('movements/index', [
            'movements' => ContractMovementResource::collection($movements),
        ]);
    }

    /**
     * The movement history of a single contract, reached from its "View movements" button.
     */
    public function forContract(Contract $contract): Response
    {
        Gate::authorize('view', $contract);

        $movements = $contract->movements()
            ->with('user')
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('contracts/movements', [
            'contract' => ContractResource::make($contract),
            'movements' => ContractMovementResource::collection($movements),
        ]);
    }
}
