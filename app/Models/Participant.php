<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Participant extends Model
{
  use SoftDeletes, HasFactory;

  const ENCURSO = 1;
  const REPROBADO = 2;
  const APROBADO = 3;
  const CANCELADO = 4;
  const PORINICIAR = 5;

  protected $table = 'participants';
  protected $fillable = [
    'person_id', 'scheduled_id','participant_status_id'
  ];

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

  public function isReprobado(){
    return $this->participant_status_id==self::REPROBADO;
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

  public function isCancelado(){
      return $this->participant_status_id==self::CANCELADO;
  }

    public function badgeStatus(): string
    {
      if ($this->isPorIniciar())
          return 'warning';
      elseif ($this->isEnCurso())
          return 'success';
      elseif ($this->isAprobado())
          return 'info';
      elseif ($this->isReprobado())
          return 'danger';
      elseif ($this->isCancelado())
          return 'default';

      return 'secondary';
    }
}
