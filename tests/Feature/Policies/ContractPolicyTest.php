<?php

use App\Models\Contract;
use App\Models\User;

/**
 * A delegated employee and a head of the same department, an admin, and one contract for each case.
 *
 * @return array{actors: array<string, User>, contracts: array<string, Contract>}
 */
function contractPolicyScenario(): array
{
    $delegate = User::factory()->delegatedEmployee()->create();
    $department = $delegate->employee->department;
    $head = User::factory()->departmentHead($department)->create();
    $admin = User::factory()->admin()->create();

    return [
        'actors' => ['admin' => $admin, 'head' => $head, 'delegate' => $delegate],
        'contracts' => [
            'own' => Contract::factory()->create(['department_id' => $department->id, 'created_by' => $delegate->id]),
            'department' => Contract::factory()->create(['department_id' => $department->id, 'created_by' => $head->id]),
            'other' => Contract::factory()->create(),
        ],
    ];
}

test('applies the permission matrix to a contract', function (string $actor, string $contract, array $expected) {
    $scenario = contractPolicyScenario();
    $user = $scenario['actors'][$actor];
    $model = $scenario['contracts'][$contract];

    $actual = collect($expected)
        ->map(fn (bool $allowed, string $ability): bool => $user->can($ability, $model))
        ->all();

    expect($actual)->toBe($expected);
})->with([
    'admin, contract created by the delegate' => ['admin', 'own', ['view' => true, 'update' => true, 'delete' => true, 'restore' => true, 'forceDelete' => true]],
    'admin, contract of the department' => ['admin', 'department', ['view' => true, 'update' => true, 'delete' => true, 'restore' => true, 'forceDelete' => true]],
    'admin, contract of another department' => ['admin', 'other', ['view' => true, 'update' => true, 'delete' => true, 'restore' => true, 'forceDelete' => true]],
    'head, contract created by the delegate' => ['head', 'own', ['view' => true, 'update' => true, 'delete' => true, 'restore' => true, 'forceDelete' => false]],
    'head, contract of the department' => ['head', 'department', ['view' => true, 'update' => true, 'delete' => true, 'restore' => true, 'forceDelete' => false]],
    'head, contract of another department' => ['head', 'other', ['view' => false, 'update' => false, 'delete' => false, 'restore' => false, 'forceDelete' => false]],
    'delegate, contract they created' => ['delegate', 'own', ['view' => true, 'update' => true, 'delete' => true, 'restore' => false, 'forceDelete' => false]],
    'delegate, contract created by the head' => ['delegate', 'department', ['view' => true, 'update' => false, 'delete' => false, 'restore' => false, 'forceDelete' => false]],
    'delegate, contract of another department' => ['delegate', 'other', ['view' => false, 'update' => false, 'delete' => false, 'restore' => false, 'forceDelete' => false]],
]);

test('applies the permission matrix to actions that involve no single contract', function (string $actor, array $expected) {
    $user = contractPolicyScenario()['actors'][$actor];

    $actual = collect($expected)
        ->map(fn (bool $allowed, string $ability): bool => $user->can($ability, Contract::class))
        ->all();

    expect($actual)->toBe($expected);
})->with([
    'admin' => ['admin', ['viewAny' => true, 'create' => true, 'viewTrashed' => true]],
    'head' => ['head', ['viewAny' => true, 'create' => true, 'viewTrashed' => true]],
    'delegate' => ['delegate', ['viewAny' => true, 'create' => true, 'viewTrashed' => false]],
]);

test('a user without a role or department can do nothing', function () {
    $user = User::factory()->create();
    $contract = Contract::factory()->create();

    expect($user->can('viewAny', Contract::class))->toBeFalse();
    expect($user->can('create', Contract::class))->toBeFalse();
    expect($user->can('viewTrashed', Contract::class))->toBeFalse();
    expect($user->can('view', $contract))->toBeFalse();
});
