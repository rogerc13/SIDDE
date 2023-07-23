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

    public function id_format(){ //returns id_number as 10.000.000
        return number_format($this->id_number, 0, '', '.');
    }

    public function user(){
        return $this->hasOne(User::class);
    }

    public function facilitator(){
        return $this->hasOne(Facilitator::class);
    }

     public function participant(){
        return $this->hasMany(Participant::class);
    }

    public function scheduled(){
        return $this->belongsToMany(Scheduled::class, 'participants');
    }

     /* public function scheduled(){
        return $this->hasManyThrough(Scheduled::class,Participant::class);
    }  */
}   
