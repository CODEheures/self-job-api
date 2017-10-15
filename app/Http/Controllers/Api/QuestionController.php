<?php

namespace App\Http\Controllers\Api;

use App\Advert;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class QuestionController extends Controller
{

    public function quiz($advertId) {

        $advert = Advert::find($advertId);

        if($advert) {
            $questions = $advert->questions;
            return response()->json($questions);
        } else {
            return response('Not found', 404);
        }


    }


}
