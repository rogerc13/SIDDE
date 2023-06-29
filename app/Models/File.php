<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;


    protected $fillable = ['path','type_id','course_id'];

    public function type(){
        return $this->belongsTo(Type::class);
    }

    public function course(){
        return $this->belongsTo(Course::class);
    }
}
