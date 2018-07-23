<?php

namespace App;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use SoftDeletes, CascadeSoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

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
    protected $cascadeDeletes = ['users'];

    /**
     *
     *
     * @var array
     */
    protected $casts = [

    ];

    //Relations
    public function users() { return $this->hasMany(User::class); }
    public function invitations() { return $this->hasMany(Invitation::class); }
    public function adverts() { return $this->hasMany(Advert::class); }
    public function questions() { return $this->hasMany(Question::class); }

    protected $appends = array();
}
