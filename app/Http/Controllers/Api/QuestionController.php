<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Answer;
use App\Http\Requests\QuizAnswersRequest;
use App\Question;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

class QuestionController extends Controller
{

    /**
     *
     * Get the QUIZ (list of questions) of an advert
     *
     * @param $advertId
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function quiz($advertId) {

        $advert = Advert::find($advertId);

        if($advert) {
            $advert->load(['questions' => function ($query) {
                $query->select(['advert_id', 'datas', 'order', 'type'])->orderBy('order', 'ASC');
            }]);
            $questions = $advert->questions;
            return response()->json($questions);
        } else {
            return response('Not found', 404);
        }


    }

    /**
     *
     * post the QUIZ answers of an advert
     *
     * @param QuizAnswersRequest $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function quizAnswers(QuizAnswersRequest $request) {

        $advert = Advert::find($request->id);
        $questionsCount = count($advert->questions);
        if ($advert && count($request->answers) == $questionsCount){

            if ($advert->answers()->where('email', $request->email)->count() > 0) {
                return response()->json('already answered', 403);
            }

            // test structure answer and calc score
            $percentQuestionScore = 0;

            foreach ($advert->questions as $question) {
                if (!array_key_exists($question->order, $request->answers)){
                    return response()->json('Not processable 1', 409);
                }

                $answer = $request->answers[$question->order];
                $answerScore = Answer::calcScore($answer, $question);

                if (is_null($answerScore)){
                    return response()->json('Not processable 2', 409);
                }

                $percentQuestionScore += $answerScore['score']/$answerScore['max'];
            }

            $finalScore = round($percentQuestionScore/$questionsCount * 100,3);

            Answer::create([
                'score' => $finalScore,
                'email' => $request->email,
                'phone' => $request->phone,
                'advert_id' => $advert->id
            ]);

            return response()->json($finalScore);
        } else {
            return response()->json('Not processable 3', 409);
        }
    }

    /**
     *
     * Get libraries of questions (news, privates and publics)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        $privatesQuestions = Question::privateLibrary()->select('type', 'library_type', 'datas', 'md5')->get();
        $privates = array_merge([], $privatesQuestions->makeVisible('datas')->unique('md5')->toArray());

        $corporatesQuestions = Question::corporateLibrary()->select('type', 'library_type', 'datas', 'md5')->get();
        $corporates = array_merge([], $corporatesQuestions->makeVisible('datas')->unique('md5')->toArray());

        $publics = Question::getPublicDatasLibrary();

        return response()->json([
            'news' => $news,
            'privates' => $privates,
            'corporates' => $corporates,
            'publics' => $publics
        ]);

    }

    /**
     *
     * Remove one question of private user questions list
     *
     * @param Request $request
     * @return mixed
     */
    public function removeOfLibrary(Request $request) {
        if ($request->filled('md5')){
            $privateQuestions = Question::mines()->where('md5', $request->md5)->get();
            foreach ($privateQuestions as $privateQuestion) {
                $privateQuestion->inLibrary = false;
                $privateQuestion->save();
            }
        }
        $proxy = Request::create(route('getLibrary',[],false), 'GET');
        $response = Route::dispatch($proxy);
        return $response;
    }
}
