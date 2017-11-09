<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Question;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class QuestionController extends Controller
{

    public function quiz($advertId) {

        $advert = Advert::find($advertId);

        if($advert) {
            $advert->load(['questions' => function ($query) {
                $query->select(['advert_id', 'datas', 'order', 'type']);
            }]);
            $questions = $advert->questions;
            return response()->json($questions);
        } else {
            return response('Not found', 404);
        }


    }

    public function getLibrary(Request $request) {
        if ($request->filled('language') && in_array($request->language, config('app.availableLocales'))) {
            App::setLocale($request->language);
        }

        $news = [];
        foreach (Question::TYPES as $type) {
            $news[$type] = [
                'blueprint' => Question::getBluePrint($type),
                'example' => Question::getExample($type)
            ];
        }

        $privatesQuestions = auth()->user()->questions()->where('inLibrary', true)->select('type', 'datas', 'md5')->get();
        $privates = array_merge([], $privatesQuestions->makeVisible('datas')->unique('md5')->toArray());

        $publics = Question::getPublicDatasLibrary();

        return response()->json([
            'news' => $news,
            'privates' => $privates,
            'publics' => $publics
        ]);

    }

    public function removeOfLibrary(Request $request) {
        if ($request->filled('md5')){
            $privateQuestions = auth()->user()->questions()->where('md5', $request->md5)->get();
            foreach ($privateQuestions as $privateQuestion) {
                $privateQuestion->inLibrary = false;
                $privateQuestion->save();
            }
        }
        $proxy = Request::create('api/question/library', 'GET');
        $response = Route::dispatch($proxy);
        return $response;
    }
}
