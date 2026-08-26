<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    const ADMINISTRADOR = 1;
    const TECNOLOGIA_EDUCATIVA = 2;
    const PROGRAMADOR = 3;
    const FACILITADOR = 4;
    const PARTICIPANTE = 5;



    public static $roles = [
        self::ADMINISTRADOR => 'Administrador',
        self::TECNOLOGIA_EDUCATIVA => 'Tecnología Educativa',
        self::PROGRAMADOR => 'Programador',
        self::FACILITADOR => 'Facilitador',
        self::PARTICIPANTE => 'Participante',
    ];

    protected $table = 'roles';
    protected $fillable = [
        'name',
    ];

    public function user(){
        return $this->hasMany(User::class);
    }
}
