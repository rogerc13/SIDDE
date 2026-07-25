<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class CourseSession extends Model
{
  use HasFactory;
  use SoftDeletes;

  protected $table = 'course_sessions';
  protected $fillable = [
    'scheduled_course_id', 'location_id', 'session_date', 'start_time', 'end_time', 'status', 'notes',
  ];
  protected $dates = [ 'deleted_at', ];

  public function scheduled()
  {
    return $this->belongsTo(Scheduled::class, 'scheduled_course_id');
  }

  public function location()
  {
    return $this->belongsTo(Location::class);
  }

  public function durationHours()
  {
    $start = Carbon::parse($this->start_time);
    $end = Carbon::parse($this->end_time);
    return $start->diffInMinutes($end) / 60;
  }
    //
}
