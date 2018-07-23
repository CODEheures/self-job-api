<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use ONGR\ElasticsearchDSL\Query\FullText\MatchQuery;
use Sleimanx2\Plastic\DSL\SearchBuilder;
use function Symfony\Component\Console\Tests\Command\createClosure;

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
             * Score = complex score by elastic score computation
             */
            case 3:

                if (!is_string($answer)){
                    return null;
                }

                // The record of the answer
                $score3 = Score3::create([
                    'documentIndex' => Score3::rootElasticIndex . $question->pref_language,
                    'description' => strtolower($answer)
                ]);

                // The false record for calc the max possible elastic score of each wanted term
                $wantedRecords = [];
                foreach ($question->datas->wantedTerms as $term) {
                    $wantedRecords[] = [
                        'term' => strtolower($term->label),
                        'record' => Score3::create([
                            'documentIndex' => Score3::rootElasticIndex . $question->pref_language,
                            'description' => strtolower($term->label)
                        ])
                    ];
                }

                // The false record for calc the max possible elastic score of each unwanted term
                $unwantedRecords = [];
                foreach ($question->datas->unwantedTerms as $term) {
                    $unwantedRecords[] = [
                        'term' => strtolower($term->label),
                        'record' => Score3::create([
                            'documentIndex' => Score3::rootElasticIndex . $question->pref_language,
                            'description' => strtolower($term->label)
                        ])
                    ];
                }

                // Test if elasticsearch is ready for each new recording
                $count = 0;
                do {
                    $hits = 0;
                    usleep(1000000);
                    $count++;
                    $isInElasticBase = Score3::search()
                        ->index(Score3::rootElasticIndex . $question->pref_language)
                        ->must()->match('id', $score3->id)
                        ->get();
                    $hits += $isInElasticBase->totalHits();

                    foreach ($wantedRecords as $wantedRecord) {
                        $isInElasticBase = Score3::search()
                            ->index(Score3::rootElasticIndex . $question->pref_language)
                            ->must()->match('id', $wantedRecord['record']->id)
                            ->get();
                        $hits += $isInElasticBase->totalHits();
                    }

                    foreach ($unwantedRecords as $unwantedRecord) {
                        $isInElasticBase = Score3::search()
                            ->index(Score3::rootElasticIndex . $question->pref_language)
                            ->must()->match('id', $unwantedRecord['record']->id)
                            ->get();
                        $hits += $isInElasticBase->totalHits();
                    }
                } while ($hits < (1+count($wantedRecords)+count($unwantedRecords)) && $count < 10);


                // When elastic is ready to search in each record
                $wantedScores = [];
                $unwantedScores = [];
                if ($hits === (1+count($wantedRecords)+count($unwantedRecords)) ) {
                    foreach ($wantedRecords as $wantedRecord) {
                        $wantedSearchMax = Score3::search()
                            ->index(Score3::rootElasticIndex . $question->pref_language)
                            ->must()->match('id', $wantedRecord['record']->id)
                            ->multiMatch(['description', 'description.stemmed'], $wantedRecord['term'], ['fuzziness' => 'AUTO'])
                            ->get();

                        $wantedSearch = Score3::search()
                            ->index(Score3::rootElasticIndex . $question->pref_language)
                            ->must()->match('id', $score3->id)
                            ->multiMatch(['description', 'description.stemmed'], $wantedRecord['term'], ['fuzziness' => 'AUTO'])
                            ->get();

                        $wantedScores[] = [
                            'term' => $wantedRecord['term'],
                            'max' => $wantedSearchMax->maxScore(),
                            'score' => is_null($wantedSearch->maxScore()) ? 0 : $wantedSearch->maxScore()
                        ];
                    }

                    foreach ($unwantedRecords as $unwantedRecord) {
                        $unwantedSearchMax = Score3::search()
                            ->index(Score3::rootElasticIndex . $question->pref_language)
                            ->must()->match('id', $unwantedRecord['record']->id)
                            ->multiMatch(['description', 'description.stemmed'], $unwantedRecord['term'], ['fuzziness' => 'AUTO'])
                            ->get();

                        $unwantedSearch = Score3::search()
                            ->index(Score3::rootElasticIndex . $question->pref_language)
                            ->must()->match('id', $score3->id)
                            ->multiMatch(['description', 'description.stemmed'], $unwantedRecord['term'], ['fuzziness' => 'AUTO'])
                            ->get();

                        $unwantedScores[] = [
                            'term' => $unwantedRecord['term'],
                            'max' => $unwantedSearchMax->maxScore(),
                            'score' => is_null($unwantedSearch->maxScore()) ? 0 : $unwantedSearch->maxScore()
                        ];
                    }
                }


                // Calc finals Scores
                $max = 0;
                $score = 0;
                foreach ($wantedScores as $wantedScore) {
                    $max += $wantedScore['max'];
                    $score += $wantedScore['score'];
                }

                foreach ($unwantedScores as $unwantedScore) {
                    $max += $unwantedScore['max'];
                    $score += ($unwantedScore['max'] - $unwantedScore['score']);
                }

                $results = [
                    'count' =>$count,
                    'wantedScore' => $wantedScores,
                    'unwantedScore' => $unwantedScores,
                    'finalMax' => $max,
                    'finalScore' => $score
                ];



                // Delete eslasticScore (in sql and elasticsearch base)
                $score3->delete();
                foreach ($wantedRecords as $wantedRecord) {
                    $wantedRecord['record']->delete();
                }
                foreach ($unwantedRecords as $unwantedRecord) {
                    $unwantedRecord['record']->delete();
                }

                return ['score' => $score, 'max' => $max];
                break;
        }

        return null;

    }
}
