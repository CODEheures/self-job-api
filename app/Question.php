<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\App;

class Question extends Model
{
    use SoftDeletes;

    /**
     * Types
     * 0: radio choice
     * 1: checkbox choice
     * 2: ordered list
     * 3: open question
     *
     */
    const TYPES = [0,1,2,3];

    /**
     * Library Type
     * 0: private auth
     * 1: private company
     * 2: public
     *
     */
    const LIBRARY_TYPES = [0,1,2];


    protected $fillable = [
        'type', 'order', 'datas', 'hash', 'inLibrary', 'library_type', 'company_id', 'user_id', 'advert_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'datas'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The relation to cascadeSoftDelete
     *
     * @var array
     */
    protected $cascadeDeletes = ['answers'];

    /**
     *
     *
     * @var array
     */
    protected $casts = [
        'datas' => 'object',
    ];

    protected $appends = array('form');

    //relations
    public function advert() { return $this->belongsTo(Advert::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function company() { return $this->belongsTo(Company::class); }

    // Getters
    public function getFormAttribute() {
        switch ($this->type) {
            case 0:
            case 1:
            case 2:
                $form = $this->datas;
                foreach ($form->options as $option){
                    unset ($option->rank);
                }
                break;
            case 3:
                $form = $this->datas;
                unset ($form->wantedTerms);
                unset ($form->unwantedTerms);
                break;
            default:
                return null;
        }
        return $form;
    }

    //Scope
    public function scopeInLibrary($query) {
        return $query->where('inLibrary', true);
    }

    public function scopeMines($query) {
        return $query->where('user_id', auth()->user()->id);
    }

    public function scopeCorporates($query) {
        return $query->whereIn('library_type', [1, 2])
            ->where('company_id', auth()->user()->company_id)
            ->where('user_id', '<>', auth()->user()->id);
    }

    public function scopePublics($query) {
        return $query->where('library_type', 2)
            ->where('pref_language', auth()->user()->pref_language)
            ->where('user_id', '<>', auth()->user()->id);
    }

    public function scopePrivateLibrary($query) {
        return $query->inLibrary()->mines();
    }

    public function scopeCorporateLibrary($query) {
        return $query->inLibrary()->corporates();
    }

    public function scopePublicLibrary($query) {
        return $query->inLibrary()->publics();
    }

    //public tools functions
    public static function testStructure ($question) {
        if (!key_exists('type', $question)) {
            return false;
        }

        if (!key_exists('datas', $question)) {
            return false;
        }

        switch ($question['type']) {
            case 0:
            case 1:
                $keys = ['label', 'options'];
                foreach ($keys as $key) {
                    if (!key_exists($key, $question['datas'])) {
                        return false;
                    }
                }

                if (!is_array($question['datas']['options']) || count($question['datas']['options']) < 2){
                    return false;
                }

                foreach ($question['datas']['options'] as $option) {
                    $keys = ['label', 'value', 'rank'];
                    foreach ($keys as $key) {
                        if (!key_exists($key, $option)) {
                            return false;
                        }
                    }
                }
                break;
            case 2:
                $keys = ['label', 'options'];
                foreach ($keys as $key) {
                    if (!key_exists($key, $question['datas'])) {
                        return false;
                    }
                }

                if (!is_array($question['datas']['options']) || count($question['datas']['options']) < 2){
                    return false;
                }

                foreach ($question['datas']['options'] as $option) {
                    $keys = ['label', 'rank'];
                    foreach ($keys as $key) {
                        if (!key_exists($key, $option)) {
                            return false;
                        }
                    }
                }
                break;
            case 3:
                $keys = ['label', 'wantedTerms', 'unwantedTerms'];
                foreach ($keys as $key) {
                    if (!key_exists($key, $question['datas'])) {
                        return false;
                    }
                }

                if (!is_array($question['datas']['wantedTerms'])
                    || !is_array($question['datas']['unwantedTerms'])
                    || (count($question['datas']['wantedTerms']) + count($question['datas']['unwantedTerms'])) < 1) {
                    return false;
                }

                foreach ($question['datas']['wantedTerms'] as $wantedTerm) {
                    if (!key_exists('label', $wantedTerm)) {
                        return false;
                    }
                }

                foreach ($question['datas']['unwantedTerms'] as $unwantedTerm) {
                    if (!key_exists('label', $unwantedTerm)) {
                        return false;
                    }
                }
                break;
        }

        return true;

    }

    public static function getBluePrint ($type) {
        $bluePrint = new Question();
        switch ($type) {
            case 0:
            case 1:
                $bluePrint->type = $type;
                $bluePrint->datas = [
                    'label' => trans('blueprint.type0.label'),
                    'options' => [
                        ['label' => trans('blueprint.type0.option1'), 'value' => 0, 'rank' => []],
                        ['label' => trans('blueprint.type0.option2'), 'value' => 1, 'rank' => []],
                        ['label' => trans('blueprint.type0.option3'), 'value' => 2, 'rank' => []]
                    ]
                ];
                break;
            case 2:
                $bluePrint->type = $type;
                $bluePrint->datas = [
                    'label' => trans('blueprint.type0.label'),
                    'options' => [
                        ['label' => trans('blueprint.type0.option1'), 'rank' => [0]],
                        ['label' => trans('blueprint.type0.option2'), 'rank' => [1]],
                        ['label' => trans('blueprint.type0.option3'), 'rank' => [2]]
                    ]
                ];
                break;
            case 3:
                $bluePrint->type = $type;
                $bluePrint->datas = [
                    'label' => trans('blueprint.type3.label'),
                    'wantedTerms' => [
                        ['label' => trans('blueprint.type3.wantedTerm1')],
                        ['label' => trans('blueprint.type3.wantedTerm2')]
                    ],
                    'unwantedTerms' => [
                        ['label' => trans('blueprint.type3.unwantedTerm1')],
                        ['label' => trans('blueprint.type3.unwantedTerm2')]
                    ]
                ];
                break;
        }
        return $bluePrint->makeVisible('datas');
    }

    public static function getExample ($type) {
        $example = new Question();
        switch ($type) {
            case 0:
                $example->type = $type;
                $example->datas = [
                    'label' => trans('blueprint.type0.example.label'),
                    'options' => [
                        ['label' => trans('blueprint.type0.example.option1'), 'value' => 0, 'rank' => []],
                        ['label' => trans('blueprint.type0.example.option2'), 'value' => 1, 'rank' => []],
                        ['label' => trans('blueprint.type0.example.option3'), 'value' => 2, 'rank' => []]
                    ]
                ];
                break;
            case 1:
                $example->type = $type;
                $example->datas = [
                    'label' => trans('blueprint.type1.example.label'),
                    'options' => [
                        ['label' => trans('blueprint.type1.example.option1'), 'value' => 0, 'rank' => []],
                        ['label' => trans('blueprint.type1.example.option2'), 'value' => 1, 'rank' => []],
                        ['label' => trans('blueprint.type1.example.option3'), 'value' => 2, 'rank' => []]
                    ]
                ];
                break;
            case 2:
                $example->type = $type;
                $example->datas = [
                    'label' => trans('blueprint.type2.example.label'),
                    'options' => [
                        ['label' => trans('blueprint.type2.example.option1'), 'rank' => [0]],
                        ['label' => trans('blueprint.type2.example.option2'), 'rank' => [1]],
                        ['label' => trans('blueprint.type2.example.option3'), 'rank' => [2]],
                        ['label' => trans('blueprint.type2.example.option4'), 'rank' => [3]],
                        ['label' => trans('blueprint.type2.example.option5'), 'rank' => [4]],
                        ['label' => trans('blueprint.type2.example.option6'), 'rank' => [5]],
                        ['label' => trans('blueprint.type2.example.option7'), 'rank' => [6]],
                        ['label' => trans('blueprint.type2.example.option8'), 'rank' => [7]]
                    ]
                ];
                break;
            case 3:
                $example->type = $type;
                $example->datas = [
                    'label' => trans('blueprint.type3.example.label'),
                ];
                break;
        }
        return $example;
    }

    public static function getPublicDatasLibrary() {
        $publicQuestions = [];

        $file = __DIR__ . '/Common/publicLibrary/'. App::getLocale() . '.json';
        if (file_exists($file)) {
            $jsonQuestions =  json_decode(file_get_contents($file),true);
            foreach ($jsonQuestions as $jsonQuestion) {
                $publicQuestion = new Question();
                $publicQuestion->datas = $jsonQuestion['datas'];
                $publicQuestion->type = $jsonQuestion['type'];
                $publicQuestion->library_type = 2;
                $publicQuestions[] = $publicQuestion->makeVisible('datas');
            }
        }
        return $publicQuestions;
    }
}
