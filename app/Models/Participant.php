<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
  use SoftDeletes;

  const ENCURSO = 1;
  const SUSPENDIDO = 2;
  const APROBADO = 3;
  const CANCELADO = 4;
  const PORINICIAR = 5;

  public static $estados = [

      self::ENCURSO => "En Curso",
      self::SUSPENDIDO => "Suspendido/Reprobado",
      self::APROBADO => "Aprobado",
      self::CANCELADO => "Cancelado",
      self::PORINICIAR => "Por Iniciar",
  ];

  protected $table = 'participants';
  protected $fillable = [
    'person_id', 'scheduled_id','participant_status_id'
  ];
  protected $dates = [ 'deleted_at', ];

   public function person()
    {
      return $this->belongsTo(Person::class);
    }

  public function scheduled()
    {
      return $this->belongsTo(Scheduled::class);
    } 

  public function participantStatus()
    {
      return $this->belongsTo(ParticipantStatus::class,'participant_status_id','id');
    }
  /* public function participantCourse()
  {
    return $this->belongsToMany(Course::class);
  }  */

  public function getEstado()
  {
        if($this->participant_status_id==1)
            return "En Curso";
        else if($this->participant_status_id==2)
            return "Suspendido/Reprobado";
        else if($this->participant_status_id==3)
            return "Aprobado";
        else if($this->participant_status_id==4)
            return "Cancelado";
        else if($this->participant_status_id==5)
            return "Por Iniciar";
  }

  public function isPorIniciar(){
        return $this->participant_status_id==self::PORINICIAR;
  }
  public function isEnCurso(){
        return $this->participant_status_id==self::ENCURSO;
  }
  public function isAprobado(){
    return $this->participant_status_id==self::APROBADO;
  }

  public function isSuspendido(){
    return $this->participant_status_id==self::SUSPENDIDO;
  }
  public function isCancelado(){
      return $this->participant_status_id==self::CANCELADO;
  }

    public function badgeStatus(){
      if ($this->isPorIniciar())
          return 'warning';
      elseif ($this->isEnCurso())
          return 'success';
      elseif ($this->isAprobado())
          return 'info';
      elseif ($this->isSuspendido())
          return 'danger';
      elseif ($this->isCancelado())
          return 'default';

  }
}
