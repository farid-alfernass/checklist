<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTime;

class Item extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $fillable = [
        'description','is_completed','due','urgency','updated_by','updated_at','created_at'
    ];

    protected $table = 'items';

    protected $dateFormat = DateTime::ATOM;

    protected $primaryKey = 'id';

    public function checklist()
    {
        return $this->belongsTo('App\Models\Checklist','id');
    }

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];
}
