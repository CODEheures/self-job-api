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

        $var = Question::first();


        return view('debug', compact('var'));
    }
}
