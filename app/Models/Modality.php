<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modality extends Model
{
    use HasFactory;

    protected $table = 'modalities';
    protected $fillable = ['name',];

    public function cursos(){
        return $this->hasMany(Curso::class);
    }
}
