<?php

namespace Database\Factories;

use App\Enums\AlertChannel;
use App\Enums\AlertStatus;
use App\Enums\AlertTargetUser;
use App\Models\Admin;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Alert>
 */
class AlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'title' => fake()->sentence(),
            'body' => fake()->realText(),
            'target_user' => AlertTargetUser::random()->value,
            'dispatched_at' => fake()->dateTimeInInterval('now'),
            'channels' => array_unique([
                AlertChannel::random()->value,
                AlertChannel::random()->value,
            ]),
            'creator_id' => Admin::factory(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (Alert $alert) {
            //
        })->afterCreating(function (Alert $alert) {
            if ($alert->target_user === AlertTargetUser::SPECIFIC) {
                $alert->users()->sync(User::factory()->create());
            }
        });
    }

    /**
     * Indicate that the alert has the specified status.
     *
     * @param \App\Enums\AlertStatus $status
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function status(AlertStatus $status)
    {
        return $this->state(function (array $attributes) use ($status) {
            return [
                'status' => $status,
            ];
        });
    }

    /**
     * Indicate that the alert is deleted.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function deleted()
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
            ];
        });
    }
}
