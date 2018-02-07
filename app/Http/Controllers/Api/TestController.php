<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Common\Elasticsearch\ElasticSearchUtils;
use App\Company;
use App\Question;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

class TestController extends Controller
{
    public function test() {
        //return ElasticSearchUtils::reIndexAdverts();

        //$user = User::where('id', 1)->with('questions')->get();
        //$user->load('questions');
        //$var = $user->questions;
        //$var = $user->questions;

        $question = Question::first();
        $time1 = microtime();
        $md5 = md5(json_encode($question->datas));
        $md5time = microtime();

        $time2 = microtime();
        $sha512 = hash('sha512', json_encode($question->datas));
        $shatime = microtime();


        dd([
            'md5' => [
                'hash' => $md5,
                'time' => $md5time-$time1,
            ],
            'sha512' => [
                'hash' => $sha512,
                'time' => $shatime-$time2
            ]
        ]);
    }
}
