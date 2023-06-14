<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Curso extends Model
{
  use SoftDeletes;

    const MAX_LENGTH_TITULO = 300;
    const MAX_LENGTH_MODALIDAD = 45;
    const MAX_LENGTH_CONTENIDO = 3000;
    const MAX_LENGTH_OBJETIVO = 3000;
    const MAX_LENGTH_DIRIGIDO = 300;



  protected $table = 'curso';
  protected $fillable = [
    'codigo', 'titulo' , 'categoria_id', 'modalidad', 'objetivo' , 'contenido', 'duracion'
    , 'dirigido', 'max', 'min', 'manual_f' , 'manual_p', 'guia' , 'presentacion',
  ];
  protected $dates = [ 'deleted_at', ];

public function categoria()
{
    return $this->belongsTo(Categoria::class);
}

public function cursoProgramado()
{
  return $this->hasMany(CursoProgramado::class);
}
    //

public function courseFile(){
  return $this->hasMany(CourseFile::class);
}

public function courseContent(){
  return $this->hasMany(CourseContent::class);
}

}


