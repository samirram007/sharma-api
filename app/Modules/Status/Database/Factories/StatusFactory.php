<?php

namespace Modules\Status\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Status\Models\Status;

/**
 * @extends Factory<Status>
 */
class StatusFactory extends Factory
{
    protected $model = Status::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'code' => fake()->unique()->lexify('???'),
            'description' => fake()->sentence(),
            'color' => fake()->hexColor(),
            'status' => 'active',
        ];
    }
}
