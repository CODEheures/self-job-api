<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Common\Elasticsearch\ElasticSearchUtils;
use App\Company;
use App\Events\NewAnswerEvent;
use App\Events\UpdateAdvertEvent;
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

        // broadcast new advert for users of company id 1
        // $newAdvertEvent = new UpdateAdvertEvent(1);
        // broadcast($newAdvertEvent);

        // Broadcast 11 answers to advert id 1
        // $newAnswerEvent = new NewAnswerEvent(1, 126);
        // broadcast($newAnswerEvent);
         return response('ok');
    }
}
