<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Content extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['text','course_id'];


    public function course(){
        return $this->belongsTo(Course::class);
    }
}
