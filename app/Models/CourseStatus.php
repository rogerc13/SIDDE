<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseStatus extends Model
{
    const POR_DICTAR = 1;
    const EN_CURSO = 2;
    const CULMINADO = 3;
    const CANCELADO = 4;


    public static $cpstatus = [
        self::POR_DICTAR => 'Por Dictar',
        self::EN_CURSO => 'En Curso',
        self::CULMINADO => 'Culminado',
        self::CANCELADO => 'Cancelado',
    ];

    protected $table = 'course_status';
    protected $fillable = [
        'name',
    ];
    protected $dates = [ ];

    public function scheduled()
    {
        return $this->hasMany(Scheduled::class);
    }


}
