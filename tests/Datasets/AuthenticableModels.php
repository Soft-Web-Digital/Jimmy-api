<?php

use App\Models\Admin;
use App\Models\User;

dataset('authenticable_models', function () {
    return [
        'user' => fn () => User::factory()->create()->refresh(),
        'admin' => fn () => Admin::factory()->create()->refresh(),
    ];
});
