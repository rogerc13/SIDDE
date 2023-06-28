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
    ];

    public function user(){
        return $this->hasOne(User::class);
    }

}