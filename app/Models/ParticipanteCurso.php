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

  public static $estados = [

      self::ENCURSO => "En Curso",
      self::SUSPENDIDO => "Suspendido/Reprobado",
      self::APROBADO => "Aprobado",
  ];

  protected $table = 'participants';
  protected $fillable = [
    'person_id', 'status_id', 'scheduled_id',
  ];
  protected $dates = [ 'deleted_at', ];

  public function participant()
  {
    return $this->belongsTo(Person::class);
  }

  public function scheduled()
  {
    return $this->belongsTo(Scheduled::class);
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
