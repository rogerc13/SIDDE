<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Facilitator extends Model
{
    use HasFactory;
    use SoftDeletes;
    

    protected $fillable = ['person_id'];
    protected $table = 'facilitators';

    public function scheduled(){
        return $this->hasMany(Scheduled::class);
    }

    public function sessions()
    {
        return $this->hasManyThrough(
            CourseSession::class,
            Scheduled::class,
            'facilitator_id',
            'scheduled_course_id',
        );
    }

    public function person(){
        return $this->belongsTo(Person::class);
    }
}
