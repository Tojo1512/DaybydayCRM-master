<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Task;
use App\Models\User;
use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {
    // Récupérer tous les IDs de clients existants
    $clientIds = \App\Models\Client::pluck('id')->toArray();
    
    // S'il n'y a pas de clients, en créer un nouveau
    $clientId = !empty($clientIds) 
        ? $faker->randomElement($clientIds) 
        : factory(\App\Models\Client::class)->create()->id;
    
    return [
        'title' => $faker->sentence,
        'external_id' => $faker->uuid,
        'description' => $faker->paragraph,
        'user_created_id' => factory(User::class),
        'user_assigned_id' => factory(User::class),
        'client_id' => $clientId,
        'status_id' => $faker->numberBetween($min = 1, $max = 4),
        'deadline' => $faker->dateTimeThisYear($max = 'now'),
        'created_at' => $faker->dateTimeThisYear($max = 'now'),
        'updated_at' => $faker->dateTimeThisYear($max = 'now'),
    ];
});
