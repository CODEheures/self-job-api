<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Sleimanx2\Plastic\Searchable;

class Advert extends Model
{
    use SoftDeletes;
    use Searchable;

    const rootElasticIndex = 'selfjob_adverts_';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'documentIndex', 'title', 'description', 'location', 'locality', 'postal_code',
        'administrative_area_level_2', 'administrative_area_level_1', 'country', 'geoloc', 'tags',
        'user_id'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     *
     *
     * @var array
     */
    protected $casts = [
        'tags' => 'array',
        'location' => 'object'
    ];

    private $mileage = 0;

    //Relations
    public function user() { return $this->belongsTo(User::class); }

    protected $appends = array('mileage');

    //Searchable elastic search attributes
    public $searchable = ['title', 'description', 'location', 'tags'];


    //Build document response for elastic
    public function buildDocument() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,
            'location' => $this->location,
            'created' => Carbon::parse($this->created_at)->format('Y-m-d')
        ];
    }


    //Attribut Getters
    public function getMileageAttribute() {
        return $this->mileage;
    }

    //public function
    public function setMileage($latitude = null, $longitude = null){
        //latitude l longitutde L
        //dL = LB – LA :
        //S = arc cos (sin lA sin lB + cos lA cos lB cos dL)
        //L = S*6 378 137
        if(filter_var($latitude, FILTER_VALIDATE_FLOAT) && filter_var($longitude, FILTER_VALIDATE_FLOAT)) {
            $lB = deg2rad($this->location->lat);
            $LB = deg2rad($this->location->lon);

            $lA = deg2rad($latitude);
            $LA = deg2rad($longitude);

            $dL = $LB - $LA;
            $S = acos((sin($lA)*sin($lB))+(cos($lA)*cos($lB)*cos($dL)));
            $L = $S*6378137;

            $this->mileage = (int)($L/1000);
        }
    }
}
