<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
            case 0:
                /**
                 * Radios list
                 * Score = Rank of checked option
                 */

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
            case 1:
                /**
                 * Checkbox list
                 * Score = Sum of ranks of each checked checkbox
                 */

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
            case 2:
                /**
                 * Compare 2 ordered lists
                 * Score = (Sum of indexes+1) - (Sum of distance of anwser item to options item)
                 */

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
        }

        return null;

    }
}
