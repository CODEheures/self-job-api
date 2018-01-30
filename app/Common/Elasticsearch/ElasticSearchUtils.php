<?php

namespace App\Common\Elasticsearch;

use App\Advert;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use Sleimanx2\Plastic\Facades\Plastic;

trait ElasticSearchUtils {

    /**
     * @param $name
     * @return mixed|\Psr\Http\Message\ResponseInterface
     */
    public static function deleteIndex($name) {
        $client = new GuzzleClient();
        $response = $client->request(
            'DELETE',
            env('PLASTIC_HOST'). '/' . $name,
            [
                'http_errors' => false,
            ]
        );

        return $response;
    }

    /**
     * @param string $name
     * @param int $shards
     * @param int $replicas
     * @param string $analyser
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public static function createIndex(string $name, int $shards, int $replicas, string $analyser) {

        $jsonAnalysisFile = __DIR__ .'/' . $analyser .'/analysis.json';
        if(!file_exists($jsonAnalysisFile)) {
            return response('analysis file not found', 500);
        }
        $analysisSettings = json_decode(file_get_contents($jsonAnalysisFile), true);


        $settings = [
            'index' => [
                'number_of_shards' => $shards,
                'number_of_replicas' => $replicas,
            ],
            'analysis'=> $analysisSettings
        ];

        $client = new GuzzleClient();
        $response = $client->request(
            'PUT',
            env('PLASTIC_HOST'). '/' . $name,
            [
                'http_errors' => false,
                'json' => $settings
            ]
        );
        return response($response->getBody()->getContents(), $response->getStatusCode());
    }

    /**
     * @param string $name
     * @param string $analyser
     * @param string $mappingName
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public static function createMapping(string $name, string $analyser, string $mappingName) {


        $jsonMappingFile = __DIR__ .'/' . $analyser .'/mappings/' . $mappingName . '.json';
        if(!file_exists($jsonMappingFile)) {
            return response('mapping file not found', 500);
        }
        $mappingSettings = json_decode(file_get_contents($jsonMappingFile), true);

        $client = new GuzzleClient();
        $response = $client->request(
            'PUT',
            env('PLASTIC_HOST'). '/' . $name . '/_mapping/' . $mappingName,
            [
                'http_errors' => false,
                'json' => $mappingSettings
            ]
        );
        return response($response->getBody()->getContents(), $response->getStatusCode());
    }

    /**
     * @param string $name
     * @param int $shards
     * @param int $replicas
     * @param string $analyser
     * @param string|null $mappingName
     * @param Builder $documents
     * @return bool|\Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public static function reIndex(string $name, int $shards, int $replicas, string $analyser, string $mappingName = null, Builder $documents) {

        //1 DELETE INDEX
        self::deleteIndex($name);

        //2 CREATE INDEX
        $response = self::createIndex($name, $shards, $replicas, $analyser);

        //3 CREATE MAPPING
        if ($response->getStatusCode() == 200 && $mappingName) {
            $response = self::createMapping($name, $analyser, $mappingName);
        }

        //4 BULK SAVE FOR REINDEXING
        if ($response->getStatusCode() == 200 && $documents->count() > 0) {
            $response = Plastic::persist()->bulkSave($documents->get());
            if ($response['errors']) {
                return response('persist errors', 500);
            } else {
                return true;
            }
        }
        return true;
    }

    /**
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public static function reIndexAdverts() {
        set_time_limit(3600);
        foreach (config('app.availableLocales') as $locale) {
            //Options
            $index = Advert::rootElasticIndex . $locale;
            $shards = 3;
            $replicas = 1;
            $analyser = $locale;
            $mappingName = 'adverts';
            $documents = Advert::where('documentIndex', $index);

            $response = ElasticSearchUtils::reIndex($index, $shards, $replicas, $analyser, $mappingName, $documents);
            if($response !== true) {
                return response($response->getContent(), $response->getStatusCode());
            }
        }
        return response('ok',200);
    }
}

