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

$factory->define(App\User::class, function (Faker $faker) {
    static $password;

    $langages = config('app.availableLocales');
    $language = $langages[array_rand($langages, 1)];

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'company' => $faker->company,
        'contact' => $faker->safeEmail,
        'password' => $password ?: $password = bcrypt('123456'),
        'pref_language' => $language
    ];
});
