<?php

use Faker\Generator as Faker;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(App\Question::class, function (Faker $faker) {

    return [
        'type' => 0,
        'order' => 0,
        'data' => [
            'label' => 'Vous préférez',
            'options' => [
                ['name' => 'Les pommes', 'value' => '2'],
                ['name' => 'Les poires', 'value' => '1'],
                ['name' => 'Les kiwis', 'value' => '3'],
            ],
        ],
    ];
});