<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use App\Common\Elasticsearch\ElasticSearchUtils;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

class TestController extends Controller
{
    public function test() {
        //return ElasticSearchUtils::reIndexAdverts();

        $adverts = Advert::search()
            ->index(Advert::rootElasticIndex . 'fr')
            ->multiMatch(['title', 'title.stemmed', 'description', 'description.stemmed', 'tags', 'tags.stemmed'], 'sed', ['fuzziness'=>'AUTO'])
            ->from(50)
            ->size(2)
            ->get();

        //$result = $adverts->result();

        dd($adverts);
    }
}
