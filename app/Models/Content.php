<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Content extends Model
{
    use HasFactory;

    protected $fillable = ['text','course_id'];


    public function curso(){
        $this->belongsTo(Curso::class);
    }
}
