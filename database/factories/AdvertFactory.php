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

$factory->define(App\Advert::class, function (Faker $faker) {

    $tags = collect(['Batiment', 'Peinture', 'Informatique', 'php', 'sql', 'facebook', 'voitures', 'ordinateur', 'disponible', 'mécanique', 'autonome', 'carreleur', 'formation'])
    ->random(3)->values()->all();

    $requirements = collect(['CACES 1', 'CAP Peinture', 'BAC+2', 'Permis A', 'Permis B', 'Permis Poids Lours', 'licence LPATC', 'bac pro Génie civil', 'débutant', 'confirmé', '5 ans d\'experience'])
        ->random(3)->values()->all();

    $contract = ['cdi', 'cdd 6 mois', 'interim', 'mi-temps'][random_int(0,3)];

    $lat = $faker->latitude;
    $lon = $faker->longitude;

    return [
        'title' => $faker->sentence(),
        'description' => $faker->paragraph(3),
        'location' => ['lat' => $lat, 'lon' => $lon],
        'formatted_address' => '37300 Joué-lès-Tours, France',
        'tags' => $tags,
        'requirements' => $requirements,
        'contract' => $contract
    ];
});