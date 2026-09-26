<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Http\Resources\ContractResource;
use App\Models\Contract;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardRequest $request): Response
    {
        $user = $request->user();
        $departmentId = $request->integer('department_id') ?: null;
        $from = $request->date('from');
        $to = $request->date('to');

        $contracts = fn (): Builder => Contract::query()
            ->visibleTo($user)
            ->when($departmentId, fn (Builder $query) => $query->where('department_id', $departmentId))
            ->overlapping($from, $to);

        $contractsPerDepartment = Contract::query()
            ->visibleTo($user)
            ->overlapping($from, $to)
            ->selectRaw('department_id, count(*) as total')
            ->groupBy('department_id')
            ->pluck('total', 'department_id');

        return Inertia::render('dashboard', [
            'filters' => [
                'department_id' => $departmentId,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
            ],
            'departments' => Department::query()->visibleTo($user)->orderBy('name')->get(['id', 'name', 'code']),
            'totals' => [
                'formalized' => $contracts()->formalized()->count(),
                'pending' => $contracts()->pending()->count(),
                'lapsed' => $contracts()->onlyTrashed()->lapsed()->count(),
                'amount' => (float) $contracts()->sum(DB::raw('COALESCE(amount, expected_amount)')),
            ],
            'upcoming' => ContractResource::collection(
                $contracts()
                    ->pending()
                    ->whereNotNull('formalization_deadline')
                    ->orderBy('formalization_deadline')
                    ->with('department')
                    ->limit(5)
                    ->get(),
            ),
            'byDepartment' => Department::query()
                ->visibleTo($user)
                ->orderBy('name')
                ->get()
                ->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'contracts' => (int) ($contractsPerDepartment[$department->id] ?? 0),
                ]),
            'contracts' => ContractResource::collection(
                $contracts()
                    ->with(['department', 'creator'])
                    ->latest()
                    ->latest('id')
                    ->paginate(15)
                    ->withQueryString(),
            ),
        ]);
    }
}
