<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $fillable = [
        'name','checklist_id'
    ];

    protected $dateFormat = \DateTime::ATOM;

    protected $table = 'template';

    protected $primaryKey = 'id';

    public function item()
    {
        return $this->hasMany('App\Models\Checklist','id');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
