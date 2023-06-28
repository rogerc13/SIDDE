<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Course extends Model
{
  use SoftDeletes;

    const MAX_LENGTH_TITLE = 300;
    const MAX_LENGTH_OBJECTIVE = 3000;
    const MAX_LENGTH_ADDRESSED = 300;



  protected $table = 'courses';
  protected $fillable = [
    'code', 'title' , 'category_id', 'modality_id', 'objective' , 'duration'
    , 'addressed',
  ];
  protected $dates = [ 'deleted_at', ];

public function category()
{
    return $this->belongsTo(Category::class);
}

public function cursoProgramado()
{
  return $this->hasMany(CursoProgramado::class);
}
    //

public function file(){
  return $this->hasMany(File::class);
}

public function content(){
  return $this->hasMany(ontent::class);
}

public function modality(){
  return $this->belongsTo(Modality::class);
}

}


