<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParticipanteCurso extends Model
{
  use SoftDeletes;

  const ENCURSO = 1;
  const SUSPENDIDO = 2;
   const APROBADO = 3;

  public static $estados = [

      self::ENCURSO => "En Curso",
      self::SUSPENDIDO => "Suspendido/Reprobado",
      self::APROBADO => "Aprobado",
  ];

  protected $table = 'participante_curso';
  protected $fillable = [
    'estado', 'user_id', 'curso_programado_id',
  ];
  protected $dates = [ 'deleted_at', ];

  public function participante()
  {
    return $this->belongsTo(User::class,'user_id','id');
  }

  public function cursoProgramado()
  {
    return $this->belongsTo(CursoProgramado::class);
  }

  public function curso()
  {
    return $this->cursoProgramado->belongsTo(Curso::class);
  }

  public function getEstado()
  {
        if($this->estado==1)
            return "En Curso";
        else if($this->estado==2)
            return "Suspendido/Reprobado";
        else if($this->estado==3)
            return "Aprobado";
  }


    //
}
