<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prerequisite extends Model
{
    use HasFactory;

    protected $fillable = ['course_id', 'prerequisite_id'];

    public function course(){
        return $this->belongsTo(Course::class);
    }

    public function courseName(){
        $title = Course::select('title')->find($this->prerequisite_id);

        return $title['title'];
    }
}
