<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

class AdvertController extends Controller
{
    public function getAdverts(Request $request) {

        $adverts = [];

        if($request->filled('language') && in_array($request->language, config('app.availableLocales'))) {
            App::setlocale($request->language);
        }



        if($request->filled('searchs') && is_array($request->searchs) && count($request->searchs) > 0){

            // Searchs combine on a string for multimatch
            $search = trim(array_reduce($request->searchs,
                function ($carry, $item) {
                    $carry .= (' ' . $item);
                    return $carry;
                }
            ));


            // Set or not Location
            $location = null;
            if($request->filled('location')
                && is_array($request->location)
                && in_array('lat', $request->location)
                && in_array('lon', $request->location)
                && is_float(filter_var($request->location['lat'], FILTER_VALIDATE_FLOAT))
                && is_float(filter_var($request->location['lon'], FILTER_VALIDATE_FLOAT))
            ){
                $location = $request->location;
            }

            //Set or not mileage
            $mileage = null;
            if ($request->filled('mileage')
                && is_array($request->mileage)
                && in_array('min', $request->mileage)
                && in_array('max', $request->mileage)
                && in_array('stop', $request->mileage)
                && is_int(filter_var ($request->mileage['min'], FILTER_VALIDATE_INT))
                && is_int(filter_var($request->mileage['max'], FILTER_VALIDATE_INT))
                && is_int(filter_var($request->mileage['stop'], FILTER_VALIDATE_INT))
                && (filter_var ($request->mileage['min'], FILTER_VALIDATE_INT))>=0
                && (filter_var ($request->mileage['max'], FILTER_VALIDATE_INT))>=0
                && (filter_var ($request->mileage['stop'], FILTER_VALIDATE_INT))>=0
            ){
                $mileage = [
                    'min' => filter_var ($request->mileage['min'], FILTER_VALIDATE_INT),
                    'max' => filter_var ($request->mileage['max'], FILTER_VALIDATE_INT),
                    'stop' => filter_var ($request->mileage['stop'], FILTER_VALIDATE_INT)
                ];
            }

            // Determine if a max distance is requested
            $isStopMileage = $mileage['max'] >= $mileage['stop'];

            // Get results
            if ($location && $mileage & !$isStopMileage){
                $results = Advert::search()
                    ->index(Advert::rootElasticIndex . App::getLocale())
                    ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed'], $search, ['fuzziness'=>'AUTO'])
                    ->geoDistance('location', $request->mileage['max'].'km', $request->location)
                    ->get()->hits();
            } else {
                $results = Advert::search()
                    ->index(Advert::rootElasticIndex . App::getLocale())
                    ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed'], $search, ['fuzziness'=>'AUTO'])
                    ->get()->hits();
            }


            // Transform in a new collection and get users informations

            $adverts = new Collection($results);
            $adverts->load(['user' => function ($query) {
                $query->select(['id','company','contact']);
            }]);

            // Set the mileage and unset minimum mileage request
            if ($location && $mileage) {
                foreach ($adverts as $key => $advert) {
                    $advert->setMileage($location['lat'], $location['lon']);
                    if($advert->mileage < $mileage['min']) {
                        unset($adverts[$key]);
                    }
                }
            }

        }

        return response()->json($adverts);
    }
}
