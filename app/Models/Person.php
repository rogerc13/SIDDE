<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'last_name',
        'id_number',
        'phone',
        'sex',
        'avatar_path',
    ];

    public function user(){
        return $this->hasOne(User::class);
    }

    public function facilitator(){
        return $this->hasOne(Facilitator::class);
    }

    public function participant(){
        return $this->hasOne(Participant::class);
    }

    public function scheduled(){
        return $this->hasManyThrough(Scheduled::class,Participant::class);
    }

}
