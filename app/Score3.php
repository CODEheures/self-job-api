<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Sleimanx2\Plastic\Searchable;

class Score3 extends Model
{
    use Searchable;

    const rootElasticIndex = 'selfjob_scores3_';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'documentIndex',
        'description'
    ];

    public $timestamps = false;

    //Searchable elastic search attributes
    public $searchable = ['id', 'description'];


    //Build document response for elastic
    public function buildDocument() {
        return [
            'id' => $this->id,
            'description' => $this->description
        ];
    }
}
