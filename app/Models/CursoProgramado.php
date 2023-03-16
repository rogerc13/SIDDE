<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CursoProgramado extends Model
{
  use SoftDeletes;

  protected $table = 'curso_programado';
  protected $fillable = [
    'fecha_i', 'fecha_f' , 'curso_id', 'user_id', 'status_id',
  ];
  protected $dates = [ 'deleted_at', ];

  public function curso()
  {
    return $this->belongsTo(Curso::class);
  }

  public function facilitador()
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
