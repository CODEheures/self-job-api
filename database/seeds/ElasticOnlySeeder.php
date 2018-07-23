<?php

use App\Advert;
use App\Company;
use App\Question;
use App\User;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Sleimanx2\Plastic\Facades\Plastic;

class ElasticOnlySeeder extends Seeder
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

        if($client){
            echo 'Reindexation des documents sous ElasticSearch...'.PHP_EOL;
            echo \App\Common\Elasticsearch\ElasticSearchUtils::reIndexAdverts();
        }
    }
}
