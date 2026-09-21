<?php

namespace Database\Seeders;

use App\Models\Contract;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ContractSeeder extends Seeder
{
    /**
     * Seeds contracts through the model so the observer builds a realistic movement history.
     */
    public function run(): void
    {
        Department::query()
            ->where('code', '!=', 'ADM')
            ->with('employees.user')
            ->get()
            ->each(fn (Department $department) => $this->seedDepartment($department));

        Auth::forgetUser();
    }

    private function seedDepartment(Department $department): void
    {
        /** @var Collection<int, User> $users */
        $users = $department->employees
            ->map(fn (Employee $employee): ?User => $employee->user)
            ->filter()
            ->values();

        $contracts = collect(range(1, 8))->map(function () use ($department, $users): Contract {
            $user = $users->random();
            Auth::setUser($user);

            return Contract::factory()->for($department)->for($user, 'creator')->create();
        });

        $contracts->take(2)->each(function (Contract $contract): void {
            Auth::setUser($contract->creator);
            $contract->update([
                'amount' => $contract->expected_amount,
                'start_date' => now()->addDays(fake()->numberBetween(10, 40)),
                'end_date' => now()->addYears(fake()->numberBetween(1, 4)),
                'responsible' => fake()->company(),
            ]);
        });

        $contracts->slice(2, 2)->each(function (Contract $contract): void {
            Auth::setUser($contract->creator);
            $contract->update(['expected_amount' => round((float) $contract->expected_amount * 1.1, 2)]);
        });

        $expired = $contracts->get(4);
        Auth::setUser($expired->creator);
        $expired->update(['formalization_deadline' => now()->subDays(10)]);
        Auth::forgetUser();
        $expired->delete();

        $deleted = $contracts->get(5);
        Auth::setUser($deleted->creator);
        $deleted->delete();
    }
}
