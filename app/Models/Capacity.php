<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Capacity extends Model
{
    use HasFactory;

    protected $fillable =['min','max','course_id'];

    public function course(){
        return $this->belongsTo(Course::class);
    }
}
