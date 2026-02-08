<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Course extends Model
{
  use SoftDeletes;

  protected static function booted(): void
  {
    static::deleting(function (Course $course) {
      if ($course->isForceDeleting()) {
        $course->content()->withTrashed()->forceDelete();
        $course->capacity()->withTrashed()->forceDelete();

        return;
      }

      $course->content()->delete();
      $course->capacity()->delete();
    });

    static::restoring(function (Course $course) {
      $course->content()->withTrashed()->restore();
      $course->capacity()->withTrashed()->restore();
    });
  }

    const MAX_LENGTH_TITLE = 300;
    const MAX_LENGTH_OBJECTIVE = 3000;
    const MAX_LENGTH_ADDRESSED = 300;



  protected $table = 'courses';
  protected $fillable = [
    'code', 'title' , 'category_id', 'modality_id', 'objective' , 'duration'
    , 'addressed',
  ];
  protected $dates = [ 'deleted_at', ];
/* public function minMax(){
  return $this->capacity
} */
public function category()
{
    return $this->belongsTo(Category::class);
}

public function scheduled()
{
  return $this->hasMany(Scheduled::class);
}
    //

public function file(){
  return $this->hasMany(File::class);
}

public function content(){
  return $this->hasMany(Content::class);
}
public function capacity(){
  return $this->hasMany(Capacity::class);
}

public function modality(){
  return $this->belongsTo(Modality::class);
}

public function prerequisite(){
  return $this->hasMany(Prerequisite::class);
}


}


