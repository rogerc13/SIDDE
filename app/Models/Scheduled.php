<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Scheduled extends Model
{
  use SoftDeletes;

  protected $table = 'scheduled_course';
  protected $fillable = [
    'course_id', 'course_status_id' , 'facilitator_id', 'start_date', 'end_date',
  ];
  protected $dates = [ 'deleted_at', ];

  public function course()
  {
    return $this->belongsTo(Course::class);
  }

  public function facilitator()
  {
    return $this->belongsTo(User::class,'user_id','id');
  }

  public function participantesCurso() //directo a la tabla pivote
  {
    return $this->hasMany(ParticipanteCurso::class);
  }
    //

  public function participantes(){
    return $this->belongsToMany(User::class,'participante_curso','curso_programado_id','user_id')->withPivot('id');
  }

  public function cpStatus(){
      return $this->belongsTo(CPStatus::class,'status_id','id');
  }
  public function isPorDictar(){
        return $this->status_id==CPStatus::POR_DICTAR;
  }
  public function isEnCurso(){
        return $this->status_id==CPStatus::EN_CURSO;
  }
  public function isCulminado(){
    return $this->status_id==CPStatus::CULMINADO;
  }
  public function isCancelado(){
      return $this->status_id==CPStatus::CANCELADO;
  }

  public function badgeStatus(){
      if ($this->isPorDictar())
          return 'warning';
      elseif ($this->isEnCurso())
          return 'info';
      elseif ($this->isCulminado())
          return 'success';
      elseif ($this->isCancelado())
          return 'danger';

  }


}
