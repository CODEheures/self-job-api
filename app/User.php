<?php

namespace App;

use Iatstuti\Database\Support\CascadeSoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, CascadeSoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','pref_language', 'contact', 'company_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
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
    protected $cascadeDeletes = ['adverts'];

    //relations
    public function company() { return $this->belongsTo(Company::class); }
    public function adverts() { return $this->hasMany(Advert::class); }
    public function questions() { return $this->hasMany(Question::class); }

    public static function encodePassword($password) {
        return bcrypt($password);
    }
}
