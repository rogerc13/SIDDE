<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;


class Scheduled extends Model
{
  use SoftDeletes, HasFactory;

  protected $table = 'scheduled_course';
  protected $fillable = [
    'course_id', 'course_status_id' , 'facilitator_id', 'start_date', 'end_date',
  ];

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function facilitator()
  {
    return $this->belongsTo(Facilitator::class);
  }

  public function courseStatus()
  {
    return $this->belongsTo(CourseStatus::class);
  }

  public function participants() //directo a la tabla pivote
  {
    return $this->hasMany(Participant::class);
  }

  public function sessions()
  {
    return $this->hasMany(CourseSession::class, 'scheduled_course_id');
  }

  public function persons(){
    return $this->belongsToMany(Person::class,'participants');
  }
    //

 /*  public function participantes(){
    return $this->belongsToMany(User::class,'participante_curso','curso_programado_id','user_id')->withPivot('id');
  } */

  public function atMaxCapacity(): bool
  {
      $participantCount = $this->participants()->count();
      $maxCapacity = $this->course->capacity->first()?->max ?? 0;

      return $participantCount >= $maxCapacity;
  }

  public function totalSessionHours(): float
  {
      return (float) $this->sessions->sum(fn ($s) => $s->durationHours());
  }

  public function remainingHours(): float
  {
      return (float) $this->course->duration - $this->totalSessionHours();
  }
  
  public function isPorDictar(){
        return $this->course_status_id==CourseStatus::POR_DICTAR;
  }
  public function isEnCurso(){
        return $this->course_status_id==CourseStatus::EN_CURSO;
  }
  public function isCulminado(){
    return $this->course_status_id==CourseStatus::CULMINADO;
  }
  public function isCancelado(){
      return $this->course_status_id==CourseStatus::CANCELADO;
  }

  public function hasStarted(): bool
  {
      if ($this->isEnCurso() || $this->isCulminado()) {
          return true;
      }

      return $this->start_date
          && Carbon::parse($this->start_date)->startOfDay()->lessThanOrEqualTo(Carbon::today());
  }

  public function canBeDeleted(): bool
  {
      return ! $this->hasStarted();
  }

  public function badgeStatus(): string
  {
      if ($this->isPorDictar())
          return 'warning';
      elseif ($this->isEnCurso())
          return 'info';
      elseif ($this->isCulminado())
          return 'success';
      elseif ($this->isCancelado())
          return 'danger';

      return 'secondary';
  }


}
