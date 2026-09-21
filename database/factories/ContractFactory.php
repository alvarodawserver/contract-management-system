<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Department;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contract>
 */
class ContractFactory extends Factory
{
    /**
     * A new contract is still being negotiated: only the expected data is known.
     * The reference and the formalization deadline are assigned when it is created.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'created_by' => User::factory(),
            'title' => fake()->randomElement([
                'Suministro de mobiliario urbano',
                'Mantenimiento de zonas verdes',
                'Servicio de limpieza de edificios municipales',
                'Obras de reforma del polideportivo',
                'Consultoría para la administración electrónica',
                'Suministro de material informático',
                'Servicio de transporte escolar',
                'Renovación del alumbrado público',
            ]),
            'description' => fake()->optional()->paragraph(),
            'type' => fake()->randomElement(['Obras', 'Servicios', 'Suministros', 'Mantenimiento', 'Consultoría']),
            'responsible' => null,
            'expected_date' => fake()->dateTimeBetween('+10 days', '+120 days'),
            'start_date' => null,
            'end_date' => null,
            'expected_amount' => fake()->randomFloat(2, 3000, 250000),
            'amount' => null,
            'last_reminder_sent_at' => null,
        ];
    }

    /**
     * Fills in the final amount, both dates and the responsible.
     */
    public function formalized(): static
    {
        return $this->state(function (): array {
            $start = CarbonImmutable::instance(fake()->dateTimeBetween('-1 month', '+3 months'));

            return [
                'amount' => fake()->randomFloat(2, 3000, 250000),
                'start_date' => $start,
                'end_date' => $start->addMonths(fake()->numberBetween(6, 48)),
                'responsible' => fake()->company(),
            ];
        });
    }

    /**
     * The formalization deadline has already passed.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'formalization_deadline' => fake()->dateTimeBetween('-60 days', '-1 day'),
        ]);
    }
}
