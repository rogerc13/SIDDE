<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facilitator extends Model
{
    use HasFactory;
    use SoftDeletes;
    

    protected $fillable = ['person_id'];
    protected $table = 'facilitators';
    protected $dates = ['deleted_at',];

    public function scheduled(){
        return $this->hasMany(Scheduled::class);
    }

    public function person(){
        return $this->belongsTo(Person::class);
    }
}
