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

    public function courseName(){
        $title = Course::where('code',$this->prerequisite)->get();
        
        return $title[0]->title;
    }

    public function prerequisite(){
        return $this->hasOne(Course::class,'code','prerequisite');
    } 
}
