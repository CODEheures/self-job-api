<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AdvertController extends Controller
{
    const _ext = 'png';


    public function postAdvert (Request $request) {
        if ($request->filled('advert') && $request->filled('questions') && $request->filled('language') && in_array($request->language, config('app.availableLocales'))) {
            $advert = is_array($request->advert) ? $request->advert :  json_decode($request->advert, true);
            $questions = is_array($request->questions) ? $request->questions : json_decode($request->questions, true);
            $language = $request->language;

            if ($this->testAdvertRequestStructure($advert) && $this->testQuestionsRequestStructure($questions)) {

                $newAdvert = new Advert();
                $newAdvert->documentIndex = Advert::rootElasticIndex . $language;
                $newAdvert->title = $advert['title'];
                $newAdvert->description = $advert['description'];
                $newAdvert->user_id = auth()->id();
                $newAdvert->location = ['lat' => $advert['place']['lat'], 'lon' => $advert['place']['lon']];
                $newAdvert->formatted_address = $advert['place']['formatted_address'];
                $newAdvert->tags = $advert['tags'];
                $newAdvert->requirements = $advert['requirements'];
                $newAdvert->contract = $advert['contract'];
                $newAdvert->save();

                foreach ($questions as $index => $question) {
                    $newQuestion = new Question();
                    $newQuestion->type = $question['type'];
                    $newQuestion->order = $index;
                    $newQuestion->datas = $this->constructQuestionsDatas($question);
                    $newQuestion->advert_id = $newAdvert->id;
                    $newQuestion->save();
                }

                return response('ok', 200);
            } else {
                return response()->json([gettype()],422);
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
                ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed', 'requirements', 'requirements.stemmed', 'contract', 'contract.stemmed'], $search, ['fuzziness'=>'AUTO'])
                ->from($from)
                ->size($size)
                ->get();
        }

        // CASE 1c Search without Location informations
        if ($search && (is_null($location) || is_null($mileage))){
            $results = Advert::search()
                ->index(Advert::rootElasticIndex . App::getLocale())
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
                ->from($from)
                ->size($size)
                ->get();
        }



        // Transform in a new collection and get users informations
        $adverts = new Collection($results->hits());
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



        return response()->json(['adverts' =>$adverts, 'totalHits' => $results->totalHits()]);
    }

    public function show($id) {

        $advert = Advert::find($id);

        if($advert) {
            $advert->load(['user' => function ($query) {
                $query->select(['id','company','contact']);
            }]);
            return response()->json($advert);
        } else {
            return response('Not found', 404);
        }


    }

    public function getMyAdverts() {

        $adverts = Advert::with('user')->where('user_id', auth()->id())->get();
        return response()->json($adverts);


    }

    public function postImg (Request $request) {

        $size = 700;
        $ratio = 16/9;
        $back_color = '#eeeeee';
        $picture_height = round($size/$ratio);

        $sucess = $request->file('tempo')->storeAs('tempo', auth()->id() . '_original.' . $request->file('tempo')->guessExtension());
        if ($sucess) {
            $uploadedFile = Storage::disk('tempo')->get(auth()->id() . '_original.' . $request->file('tempo')->guessExtension());

            $picture = Image::make($uploadedFile);
            $picture->resize($size, $picture_height, function ($constraint) {
                $constraint->aspectRatio();
            });

            $raw = Image::canvas($size, $picture_height, $back_color);
            $raw->insert($picture, 'center');
            $raw->encode(self::_ext);
            $raw->save(storage_path('app/tempo/'. auth()->id() .'.' . self::_ext));

            Storage::disk('tempo')->delete(auth()->id() . '_original.' . $request->file('tempo')->guessExtension());
            return response('ok', 200);
        } else {
            return response('ko', 500);
        }
    }

    public function getTempoImg () {
        $path = storage_path('app/tempo/'. auth()->id() . '.' . self::_ext);
        if (file_exists($path)) {
            return response(file_get_contents($path),200);
        } else {
            return response('not found', 404);
        }

    }

    public function deleteTempoImg () {
        Storage::disk('tempo')->delete(auth()->id() . '.' . self::_ext);
        return response('ok',200);
    }

    private function testAdvertRequestStructure ($advert) {

        if (!is_array($advert)) {
            return false;
        }

        $keys = ['title', 'description', 'contract', 'tags', 'requirements', 'place'];
        foreach ($keys as $key) {
            if (!key_exists($key, $advert)) {
                return false;
            }
        }

        if (!is_string($advert['title'])
            || !is_string($advert['contract'])
            || strlen($advert['contract']) > Advert::contractLenght
            || strlen($advert['title']) > Advert::titleLength
            || !is_array($advert['tags'])
            || !is_array($advert['requirements'])
        ){
            return false;
        }

        $keys = ['formatted_address', 'lat', 'lon'];
        foreach ($keys as $key) {
            if (!key_exists($key, $advert['place'])) {
                return false;
            }
        }
        if (!is_string($advert['place']['formatted_address'])
            || !filter_var($advert['place']['lat'], FILTER_VALIDATE_FLOAT)
            || !filter_var($advert['place']['lon'], FILTER_VALIDATE_FLOAT)
            || abs(filter_var($advert['place']['lat'], FILTER_VALIDATE_FLOAT))>90
            || abs(filter_var($advert['place']['lon'], FILTER_VALIDATE_FLOAT))>180
        ){
            return false;
        }

        return true;

    }

    private function testQuestionsRequestStructure ($questions) {
        if (!is_array($questions)) {
            return false;
        }

        foreach ($questions as $question){
            if (!key_exists('type', $question)) {
                return false;
            }

            switch ($question['type']) {
                case 0:
                case 1:
                case 2:
                    $keys = ['label', 'options'];
                    foreach ($keys as $key) {
                        if (!key_exists($key, $question)) {
                            return false;
                        }
                    }

                    if (!is_array($question['options']) || count($question['options']) < 2){
                        return false;
                    }

                    foreach ($question['options'] as $option) {
                        $keys = ['label', 'value', 'rank'];
                        foreach ($keys as $key) {
                            if (!key_exists($key, $option)) {
                                return false;
                            }
                        }
                    }
                    break;
            }
        }

        return true;

    }

    private function constructQuestionsDatas ($question) {
        $datas = [];
        switch ($question['type']) {
            case 0:
            case 1:
            case 2:
                $datas = [
                    'label' => $question['label'],
                    'options' => $question['options']
                ];
                break;
        }

        return $datas;
    }
}
