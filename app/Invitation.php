<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Invitation extends Model
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email', 'company_id'
    ];

    /**
     * The relation to cascadeSoftDelete
     *
     * @var array
     */

    //relations
    public function company() { return $this->belongsTo(Company::class); }
}
