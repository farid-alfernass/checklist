<?php

namespace App\models;

use Illuminate\Database\Eloquent\Model;
use DateTime;

class Checklist extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $fillable = [
        'object_domain','object_id','description','is_completed','completed_at','due','due_interval','due_unit','urgency','updated_by','updated_at','created_at'
    ];

    protected $dateFormat = \DateTime::ATOM;

    protected $table = 'checklist';

    protected $primaryKey = 'id';

    public function item()
    {
        return $this->hasMany('App\Models\Item','checklistId');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
