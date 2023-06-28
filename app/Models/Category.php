<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
  use SoftDeletes;

      const MAX_LENGTH_NOMBRE = 60;

  protected $table = 'categories';
  protected $fillable = [
    'name',
  ];
  protected $dates = [ 'deleted_at', ];

public function cursos()
{
  return $this->hasMany(Course::class);
}

public static function categoriasCursos()
{
        $categoriasYCursos = Category::with('courses')->get();
        //$categoriasYCursos = Categoria::all();
        return $categoriasYCursos;
}
    //
}
