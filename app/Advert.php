<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Iatstuti\Database\Support\CascadeSoftDeletes;
use Sleimanx2\Plastic\Searchable;

class Advert extends Model
{
    use SoftDeletes, Searchable, CascadeSoftDeletes;

    const titleLength = 120;
    const contractLenght = 40;
    const rootElasticIndex = 'selfjob_adverts_';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'documentIndex', 'title', 'description', 'location', 'formatted_address', 'tags',
        'requirements', 'contract', 'pictureUrl', 'is_internal_private', 'is_publish', 'company_id', 'user_id'
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
    protected $cascadeDeletes = ['questions'];

    /**
     *
     *
     * @var array
     */
    protected $casts = [
        'tags' => 'array',
        'requirements' => 'array',
        'location' => 'object',
        'is_publish' => 'bool'
    ];

    private $mileage = 0;
    private $responses_count = 0;
    private $is_updatable = false;
    private $is_deletable = true;

    //Relations
    public function company() { return $this->belongsTo(Company::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function answers() { return $this->hasMany(Answer::class); }

    protected $appends = array('mileage', 'responses_count', 'is_updatable', 'is_deletable');

    //Searchable elastic search attributes
    public $searchable = ['title', 'description', 'location', 'tags', 'requirements', 'contract', 'is_publish'];


    //Build document response for elastic
    public function buildDocument() {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'title' => $this->title,
            'description' => $this->description,
            'tags' => $this->tags,
            'requirements' => $this->requirements,
            'contract' => $this->contract,
            'location' => $this->location,
            'formatted_address' => $this->formatted_address,
            'is_publish' => $this->is_publish,
            'created' => Carbon::parse($this->created_at)->toDateTimeString()
        ];
    }


    //Attribut Getters
    public function getMileageAttribute() {
        return $this->mileage;
    }

    public function getResponsesCountAttribute() {
        return $this->responses_count;
    }

    public function getIsUpdatableAttribute() {
        return $this->is_updatable;
    }

    public function getIsDeletableAttribute() {
        return $this->is_deletable;
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

    public function setResponsesCount(){
        $this->responses_count = $this->answers()->count();
    }

    public function setIsUpdatable() {
        $this->is_updatable = $this->responses_count == 0;
    }

    public function isAccessibleByAuth () {
        return auth()->check() && $this->isAccessibleByUser(auth()->user());
    }

    public function isAccessibleByUser ($user) {
        return $this->user->id === $user->id ||
            (!$this->is_internal_private && $this->company_id === $user->company_id);
    }
    //public statics tools function
    public static function testStructure($advert) {
        if (!is_array($advert)) {
            return false;
        }

        $keys = ['title', 'description', 'contract', 'tags', 'requirements', 'place', 'is_internal_private'];
        foreach ($keys as $key) {
            if (!key_exists($key, $advert)) {
                return false;
            }
        }

        if (!is_string($advert['title'])
            || !is_string($advert['contract'])
            || strlen($advert['contract']) > Advert::contractLenght
            || strlen($advert['title']) > Advert::titleLength
            || !is_array($advert['tags'])
            || !is_array($advert['requirements'])
            || !is_bool($advert['is_internal_private'])
        ){
            return false;
        }

        $keys = ['formatted_address', 'lat', 'lon'];
        foreach ($keys as $key) {
            if (!key_exists($key, $advert['place'])) {
                return false;
            }
        }
        if (!is_string($advert['place']['formatted_address'])
            || !filter_var($advert['place']['lat'], FILTER_VALIDATE_FLOAT)
            || !filter_var($advert['place']['lon'], FILTER_VALIDATE_FLOAT)
            || abs(filter_var($advert['place']['lat'], FILTER_VALIDATE_FLOAT))>90
            || abs(filter_var($advert['place']['lon'], FILTER_VALIDATE_FLOAT))>180
        ){
            return false;
        }

        return true;
    }
}
