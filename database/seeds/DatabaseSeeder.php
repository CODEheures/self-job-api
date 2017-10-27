<?php

use App\Advert;
use App\Question;
use App\User;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Sleimanx2\Plastic\Facades\Plastic;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        echo 'Création des indexs sous ElasticSearch...'.PHP_EOL;
        $client = Plastic::getClient();

        if($client){
            echo 'ElasticSearch accessible on ' . env('PLASTIC_HOST') .PHP_EOL;
            echo \App\Common\Elasticsearch\ElasticSearchUtils::reIndexAdverts();
        } else {
            echo 'Error, ElasticSearch not accessible on ' . env('ELASTIC_HOST') . ':' . env('ELASTIC_PORT').PHP_EOL;
        }


        //Set APP FR
        \Illuminate\Support\Facades\App::setLocale('fr');

        //recreate Passport Grant Client
        $client = new ClientRepository();
        $client->createPasswordGrantClient(
            null, 'selfjob', 'http://localhost'
        );

        //Populate DB
        $user1 = new User();
        $user1->name = 'sylvain';
        $user1->email = 'test@mail.test';
        $user1->company = 'Michelin';
        $user1->contact = 's.g@m.t';
        $user1->password = bcrypt('password');
        $user1->pref_language = 'fr';
        $user1->save();

        $advert1 = new Advert();
        $advert1->documentIndex = Advert::rootElasticIndex . 'fr';
        $advert1->title = "Peintre en batiment à temps plein débutant";
        $advert1->description = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut porttitor neque quam, vel rhoncus elit pellentesque in. Maecenas vitae euismod orci, quis vulputate mauris. In nec risus risus. Proin vitae feugiat ante. Proin consectetur elementum dui. Ut volutpat mi et neque suscipit aliquet ac sed leo. In lorem orci, rhoncus eget justo efficitur, vehicula sagittis lectus. Nullam a efficitur quam.

Fusce ligula nisi, ullamcorper nec leo fermentum, commodo convallis nibh. Donec ac mi tincidunt, lacinia dolor sed, rhoncus enim. Maecenas eu volutpat augue. Sed orci risus, pretium ultricies mattis nec, pulvinar eget odio. Nunc finibus nulla eget lobortis finibus. Morbi fringilla, sem quis ultrices rutrum, arcu odio tincidunt metus, a vulputate est felis ac nulla. Aliquam tincidunt dictum diam et rutrum. Pellentesque eleifend tortor auctor velit semper pulvinar. Praesent dapibus laoreet tortor, sed convallis justo mollis a. Phasellus suscipit elit vitae finibus molestie. Vivamus sit amet magna a lectus blandit bibendum.";

        $advert1->user_id = $user1->id;
        $advert1->location = ['lat' => 47.3477, 'lon' => 0.6489845999999488];
        $advert1->locality = 'Joué-lès-Tours';
        $advert1->postal_code = '37300';
        $advert1->administrative_area_level_2 = 'Indre-et-Loire';
        $advert1->administrative_area_level_1 = 'Centre-Val de Loire';
        $advert1->country = 'FR';
        $advert1->geoloc = '37300 Joué-lès-Tours, France';
        $advert1->tags = ['Peintre', 'Bâtiment', 'Confirmé'];
        $advert1->requirements = ['CAP peinture', 'CACES 1,2 & 3', '10 ans d\'expérience', 'permis B'];
        $advert1->contract = 'cdi';

        $advert1->save();

        $question1 = new Question();
        $question1->type = 0;
        $question1->order = 0;
        $question1->datas = [
            'label' => 'Vous préférez',
            'options' => [
                ['name' => 'Les pommes', 'value' => '1'],
                ['name' => 'Les poires', 'value' => '2'],
                ['name' => 'Les kiwis', 'value' => '3'],
            ],
        ];
        $question1->expected = [2,1,3];
        $question1->advert_id = $advert1->id;

        $question1->save();

        factory(App\User::class, 10)->create();
        foreach (App\User::get() as $user) {
            $rand = random_int(1,10);
            factory(App\Advert::class, $rand)->create(['user_id' => $user->id, 'documentIndex' => Advert::rootElasticIndex . $user->pref_language]);
        }


        if($client){
            echo 'Reindexation des documents sous ElasticSearch...'.PHP_EOL;
            echo \App\Common\Elasticsearch\ElasticSearchUtils::reIndexAdverts();
        }
    }
}
