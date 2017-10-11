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

    $tags = collect(['Batiment', 'Peinture', 'Informatique', 'php', 'sql', 'facebook', 'voitures', 'ordinateur', 'bac+2', 'licence', 'bac pro', 'débutant', 'confirmé', 'disponible', 'mécanique', 'autonome', 'caces', 'permis A', 'carreleur', 'formation'])
    ->random(3)->values()->all();

    $lat = $faker->latitude;
    $lon = $faker->longitude;

    return [
        'title' => $faker->sentence(),
        'description' => $faker->paragraph(3),
        'location' => ['lat' => $lat, 'lon' => $lon],
        'locality' => $faker->city,
        'postal_code' => $faker->postcode,
        'administrative_area_level_2' => 'Indre-et-Loire',
        'administrative_area_level_1' => 'Centre-Val de Loire',
        'country' => 'FR',
        'geoloc' => '37300 Joué-lès-Tours, France',
        'tags' => $tags
    ];
});