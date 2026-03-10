<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sender_id'   => User::factory(),
            'receiver_id' => User::factory(),
            'body'        => $this->faker->sentence(),
            'is_read'     => false,
        ];
    }
}
