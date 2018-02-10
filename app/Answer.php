<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Sleimanx2\Plastic\Facades\Plastic;

class Answer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'score', 'email', 'phone', 'advert_id'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    //relations
    public function advert() { return $this->belongsTo(Advert::class); }

    //public tools functions
    public static function calcScore ($answer, Question $question) {

        switch ($question->type) {
            /**
             * Radios list
             * Score = Rank of checked option
             */
            case 0:
                if (!is_int($answer)) {
                    return null;
                }

                foreach ($question->datas->options as $option) {
                    if ($answer == $option->value) {
                        return ['score' =>$option->rank[0] , 'max' => count($question->datas->options)];
                    }
                }

                return null;

                break;

            /**
             * Checkbox list
             * Score = Sum of ranks of each checked checkbox
             */
            case 1:
                if (!is_array($answer)) {
                    return null;
                }

                $score = 0;
                foreach ($answer as $answerPart) {
                    $matchOption = array_filter($question->datas->options, function ($key) use ($answerPart) {
                       return $key->value == $answerPart;
                    });
                    if (count($matchOption) == 0 || count($matchOption) > 1) {
                        return null;
                    } else {
                        $score += array_merge([], $matchOption)[0]->rank[0];
                    }
                }

                $max = array_reduce($question->datas->options, function ($sum, $item) {
                    if ($item->rank[0] > 0) {
                        $sum += $item->rank[0];
                    }
                    return $sum;
                }, 0);

                return ['score' => $score, 'max' => $max];

                break;

            /**
             * Compare 2 ordered lists
             * Score = (Sum of indexes+1) - (Sum of distance of anwser item to options item)
             */
            case 2:


                if (!is_array($answer) || count($answer) !== count($question->datas->options)){
                    return null;
                }

                $sumOfDistance = 0;
                $sumOfIndexes = 0;
                foreach ($answer as $position => $item) {
                    $sumOfIndexes += $position+1;

                    //Get item in option same as item in answer
                    $matchOption =array_filter($question->datas->options, function ($key) use ($item) {
                        return strtolower($key->label) == strtolower($item);
                    });

                    // Continue if only one match item in options
                    if (count($matchOption) == 0 || count($matchOption) > 1) {
                        return null;
                    } else {
                        //Position of option and distance of the answer item
                        $optionPosition = array_merge([], $matchOption)[0]->rank[0];
                        $sumOfDistance += abs($position - $optionPosition);
                    }


                }

                $score = $sumOfIndexes - $sumOfDistance;
                return ['score' => $score, 'max' => $sumOfIndexes];

                break;

            /**
             * Compare text with wanted and unwanted terms
             * Score =
             */
            case 3:

                if (!is_string($answer)){
                    return null;
                }

                $wantedSearch = trim(array_reduce($question->datas->wantedTerms,
                    function ($carry, $item) {
                        $carry .= (' ' . $item->label);
                        return $carry;
                    }
                ));

                $elasticScore3 = new Score3([
                    'documentIndex' => Score3::rootElasticIndex . $question->pref_language,
                    'description' => $answer
                ]);
                $elasticScore3->save();

                //sleep(5);
                $count = 0;
                do {
                    usleep(500000);
                    $count++;
                    $results = Score3::search()
                        ->index(Score3::rootElasticIndex . $question->pref_language)
                        ->must()->match('id', $elasticScore3->id)
                        //->multiMatch(['description', 'description.stemmed'], $wantedSearch , ['fuzziness'=>'AUTO'])
                        ->get();
                } while ($results->totalHits() === 0 && $count < 10);


                dd([$count, $results]);
                break;
        }

        return null;

    }
}
