<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
  use SoftDeletes;

      const MAX_LENGTH_NOMBRE = 60;

  protected $table = 'categoria';
  protected $fillable = [
    'nombre',
  ];
  protected $dates = [ 'deleted_at', ];

public function cursos()
{
  return $this->hasMany(Curso::class);
}

public static function categoriasCursos()
{
        $categoriasYCursos = Categoria::with('cursos')->get();
        //$categoriasYCursos = Categoria::all();
        return $categoriasYCursos;
}
    //
}
