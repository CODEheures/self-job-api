<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Common\Elasticsearch\ElasticSearchUtils;
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

        $user = User::first();
        $var = $user->questions()->where('inLibrary', true)->select('type', 'datas', 'md5')->get();

        $uniq = array_merge([],$var->makeVisible('datas')->unique('md5')->toArray());
        return response()->json($uniq);
    }
}
