<?php

namespace App\Observers;

use App\Question;

class QuestionObserver
{
    /**
     * Listen to the Advert created event.
     *
     * @param Question $question
     * @return void
     * @internal param Advert $advert
     */
    public function creating(Question $question)
    {
        $question->md5  = md5(json_encode($question->datas));
    }

    public function saving(Question $question) {
        $question->md5  = md5(json_encode($question->datas));
    }

}