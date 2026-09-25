<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_name' => $this->faker->name(), // Случайное ФИО
            'total_amount' => $this->faker->randomFloat(2, 10, 2000), // Сумма от 10 до 2000
            'status' => $this->faker->randomElement(['pending', 'completed', 'cancelled']), // Рандомный статус
            // Генерируем случайную дату создания за последний год
            'created_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }
}
