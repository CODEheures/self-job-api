<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Events\UpdateAdvertEvent;
use App\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class AdvertController extends Controller
{
    const _ext = 'png';


    public function postAdvert (Request $request) {
        if ($request->filled('advert') && $request->filled('questions') && $request->filled('language') && in_array($request->language, config('app.availableLocales'))) {
            $advert = is_array($request->advert) ? $request->advert :  json_decode($request->advert, true);
            $questions = is_array($request->questions) ? $request->questions : json_decode($request->questions, true);
            $language = $request->language;

            if (Advert::testStructure($advert) && $this->testQuestionsStructure($questions)) {
                DB::beginTransaction();
                try {
                    $newAdvert = new Advert();
                    $newAdvert->documentIndex = Advert::rootElasticIndex . $language;
                    $newAdvert->title = $advert['title'];
                    $newAdvert->description = $advert['description'];
                    $newAdvert->user_id = auth()->id();
                    $newAdvert->company_id = auth()->user()->company_id;
                    $newAdvert->location = ['lat' => $advert['place']['lat'], 'lon' => $advert['place']['lon']];
                    $newAdvert->formatted_address = $advert['place']['formatted_address'];
                    $newAdvert->tags = $advert['tags'];
                    $newAdvert->requirements = $advert['requirements'];
                    $newAdvert->contract = $advert['contract'];
                    $newAdvert->pictureUrl = auth()->user()->pictureUrl;
                    $newAdvert->is_internal_private = $advert['is_internal_private'];
                    $newAdvert->is_publish = false;
                    $newAdvert->save();

                    $user = auth()->user();
                    $user->pictureUrl = null;
                    $user->save();


                    foreach ($questions as $index => $question) {
                        $newQuestion = new Question();
                        $newQuestion->type = $question['type'];
                        $newQuestion->order = $index;
                        $newQuestion->datas = $question['datas'];
                        $newQuestion->pref_language = $language;
                        $newQuestion->advert_id = $newAdvert->id;
                        $newQuestion->user_id = auth()->id();
                        $newQuestion->company_id = auth()->user()->id;
                        $newQuestion->save();
                    }

                    DB::commit();

                    // Broadcast the new advert
                    $newAdvertEvent = new UpdateAdvertEvent($user->company_id);
                    broadcast($newAdvertEvent);

                } catch (\Exception $e) {
                    DB::rollback();
                    return response('ko', 422);
                }

                return response('ok', 200);
            } else {
                return response('ko', 422);
            }

        } else {
            return response('ko', 422);
        }


    }

    public function getAdverts(Request $request) {
        $adverts = [];

        $size = 4;
        $from = 0;

        if($request->filled('language') && in_array($request->language, config('app.availableLocales'))) {
            App::setlocale($request->language);
        }

        if($request->filled('from') && is_int(filter_var($request->from, FILTER_VALIDATE_INT)) && filter_var($request->from, FILTER_VALIDATE_INT)>0) {
            $from = filter_var($request->from, FILTER_VALIDATE_INT);
        }

        //Set search
        $search = null;
        if($request->filled('searchs') && is_array($request->searchs) && count($request->searchs) > 0) {

            // Searchs combine on a string for multimatch
            $search = trim(array_reduce($request->searchs,
                function ($carry, $item) {
                    $carry .= (' ' . $item);
                    return $carry;
                }
            ));
        }


        // Set or not Location
        $location = null;
        if($request->filled('location')
            && is_array($request->location)
            && array_key_exists('lat', $request->location)
            && array_key_exists('lon', $request->location)
            && is_float(filter_var($request->location['lat'], FILTER_VALIDATE_FLOAT))
            && is_float(filter_var($request->location['lon'], FILTER_VALIDATE_FLOAT))
        ){
            $location = $request->location;
        }

        //Set or not mileage
        $mileage = null;
        if ($request->filled('mileage')
            && is_array($request->mileage)
            && array_key_exists('min', $request->mileage)
            && array_key_exists('max', $request->mileage)
            && array_key_exists('stop', $request->mileage)
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

        //***************** Get results***********************//

        //CASES WITH SEARCH (NO SORTBY DISTANCE)
        // CASE 1 search in a limit distance
        if ($search && $location && $mileage & !$isStopMileage){
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
                ->must()->term('is_publish', true)
                ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed', 'requirements', 'requirements.stemmed', 'contract', 'contract.stemmed'], $search, ['fuzziness'=>'AUTO'])
                ->geoDistance('location', $request->mileage['max'].'km', $request->location)
                ->from($from)
                ->size($size)
                ->get();
        }

        // CASE 1b search but no geodistance limit
        if ($search && $location && $mileage & $isStopMileage){
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
                ->must()->term('is_publish', true)
                ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed', 'requirements', 'requirements.stemmed', 'contract', 'contract.stemmed'], $search, ['fuzziness'=>'AUTO'])
                ->from($from)
                ->size($size)
                ->get();
        }

        // CASE 1c Search without Location informations
        if ($search && (is_null($location) || is_null($mileage))){
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
                ->must()->term('is_publish', true)
                ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed', 'requirements', 'requirements.stemmed', 'contract', 'contract.stemmed'], $search, ['fuzziness' => 'AUTO'])
                ->from($from)
                ->size($size)
                ->get();
        }


        // CASE 2 WITHOUT SEARCH
        //CASE 2a in a limit distance
        if (is_null($search) && $location && $mileage & !$isStopMileage){
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
                ->matchAll()
                ->must()->term('is_publish', true)
                ->geoDistance('location', $request->mileage['max'].'km', $request->location)
                ->sortBy('_geo_distance', 'asc', ['location' => $request->location])
                ->from($from)
                ->size($size)
                ->get();
        }

        //CASE 2b without a limit distance
        if (is_null($search) && $location && $mileage & $isStopMileage){
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
                ->matchAll()
                ->must()->term('is_publish', true)
                ->sortBy('_geo_distance', 'asc', ['location' => $request->location])
                ->from($from)
                ->size($size)
                ->get();
        }

        //CASE 2c without location informations
        if (is_null($search) && (is_null($location) || is_null($mileage))) {
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
                ->matchAll()
                ->must()->term('is_publish', true)
                ->from($from)
                ->size($size)
                ->get();
        }



        // Transform in a new collection and get users informations
        $adverts = new Collection($results->hits());
        $adverts->load(['company' => function ($query) {
            $query->select(['id','name']);
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



        return response()->json(['adverts' =>$adverts, 'totalHits' => $results->totalHits()]);
    }

    public function show($id) {

        $advert = Advert::find($id);

        if($advert) {
            $advert->load(['company' => function ($query) {
                $query->select(['id','name']);
            }]);
            return response()->json($advert);
        } else {
            return response('Not found', 404);
        }


    }

    public function getMyAdverts() {

        $adverts = Advert::with('user')->where('user_id', auth()->id())->orWhere(function ($query) {
            $query->where('company_id', auth()->user()->company_id)->where('is_internal_private', false);
        })->get();

        foreach ($adverts as $advert) {
            $advert->setResponsesCount();
            $advert->setIsUpdatable();
        }
        return response()->json($adverts);


    }

    public function getAdvertAnswers($id) {

        $answers = null;
        $advert = Advert::with('answers')->find($id);
        if ($advert && $advert->isAccessibleByAuth()){
            $answers = $advert->answers()->orderBy('score', 'DESC')->oldest()->get();
        }
        return response()->json($answers);
    }

    public function publishAdvert(Request $request) {
        if ($request->filled('id') && $request->filled('publish') && is_int(filter_var($request->id, FILTER_VALIDATE_INT)) && is_bool($request->publish)) {
            $advert = Advert::find($request->id);
            if ($advert && $advert->isAccessibleByAuth()) {
                $advert->is_publish = $request->publish;
                $advert->save();
            }
            // Broadcast the new advert
            $newAdvertEvent = new UpdateAdvertEvent($advert->company_id);
            broadcast($newAdvertEvent);
            return response()->json('ok', 200);
        }
//        $proxy = Request::create(route('getMyAdverts',[],false), 'GET');
//        $response = Route::dispatch($proxy);
        return response()->json('ko', 409);

    }

    public function deleteAdvert(Request $request) {

        if ($request->filled('id') && is_int(filter_var($request->id, FILTER_VALIDATE_INT))){
            $advert = Advert::find($request->id);
            if ($advert && auth()->user()->id == $advert->user->id) {
                //TODO delete picture
                $advert->delete();
            }
            // Broadcast the new advert
            $newAdvertEvent = new UpdateAdvertEvent(auth()->user()->company_id);
            broadcast($newAdvertEvent);
            return response()->json('ok', 200);
        }
//        $proxy = Request::create(route('getMyAdverts',[],false), 'GET');
//        $response = Route::dispatch($proxy);
        return response()->json('ko', 409);


    }

    private function testQuestionsStructure ($questions) {

        if (!is_array($questions)) {
            return false;
        }

        foreach ($questions as $question) {
            $result  = Question::testStructure($question);
            if (!$result) {
                return false;
            }
        }

        return true;

    }
}
