<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prerequisite extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'prerequisite','course_code'];

    public function course(){
        return $this->belongsTo(Course::class);
    }

    public function prerequisite(){
        return $this->hasOne(Course::class,'code','prerequisite');
    } 

    public function courseName(): ?string
    {
        return $this->prerequisite?->title;
    }
}
