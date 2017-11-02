<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use SoftDeletes;

    const TYPE_ONE_CHOICE = 0;


    protected $fillable = [
        'type', 'order', 'datas', 'advert_id'
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
    public function answers() { return $this->belongsTo(Answer::class); }

    // Getters
    public function getFormAttribute() {
        switch ($this->type) {
            case 0:
                $form = $this->datas;
                foreach ($form->options as $option){
                    unset ($option->rank);
                }
                break;
            default:
                return null;
        }
        return $form;
    }
}
