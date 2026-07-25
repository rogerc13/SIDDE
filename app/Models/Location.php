<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
  use HasFactory;
  use SoftDeletes;

    const MAX_LENGTH_NAME = 100;

  protected $table = 'locations';
  protected $fillable = [
    'name',
  ];
  protected $dates = [ 'deleted_at', ];

  public function sessions()
  {
    return $this->hasMany(CourseSession::class);
  }
    //
}
