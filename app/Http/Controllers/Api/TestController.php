<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Common\Elasticsearch\ElasticSearchUtils;
use App\Question;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

class TestController extends Controller
{
    public function test() {
        //return ElasticSearchUtils::reIndexAdverts();

        $question = Question::first();
        $question->datas->choices[0]->item = "Les raisins";

        $datas = $question->datas;
        $datas->choices[0]->item = "Les raisins";

        $question->datas = $datas;
        $question->save();
        dd($question);


        $question->save();

        dd($question->datas);
    }
}
