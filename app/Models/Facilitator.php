<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facilitator extends Model
{
    use HasFactory;

    protected $fillable = ['person_id'];
    protected $table = 'facilitators';

    public function scheduled(){
        return $this->hasMany(Scheduled::class);
    }

    public function person(){
        return $this->belongsTo(Person::class);
    }
}
