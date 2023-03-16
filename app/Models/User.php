<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Rol;

class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;


  
    const MAX_LENGTH_EMAIL = 60;
    const MAX_LENGTH_PASSWORD = 100;
    const MAX_LENGTH_NOMBRE = 45;
    const MAX_LENGTH_APELLIDO = 45;  
    const MAX_LENGTH_CI = 45; 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'users';
    protected $fillable = [
      'nombre', 'apellido' , 'ci', 'rol_id', 'email' , 'password', 'imagen',
    ];
    protected $dates = [ 'deleted_at', ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];


    public function isAdministrador(){
        return $this->rol_id==Rol::ADMINISTRADOR;
    }

    public function isTecEducativa(){
        return $this->rol_id==Rol::TECNOLOGIA_EDUCATIVA;
    }

    public function isProgramador(){
        return $this->rol_id==Rol::PROGRAMADOR;
    }

    public function isFacilitador(){
        return $this->rol_id==Rol::FACILITADOR;
    }

    public function isParticipante(){
        return $this->rol_id==Rol::PARTICIPANTE;
    }


    public function rol()
    {
      return $this->belongsTo(Rol::class);
    }

    public function cursoFacilitador() // cursos de un facilitador
    {
      return $this->hasMany(CursoProgramado::class);
    }

    public function cursosParticipante() //directo a la tabla pivote (Cursos de un participante)
    {
      return $this->hasMany(ParticipanteCurso::class);
    }

    public function misCursos() //  Cursos Programados a los que esta registrado un Participante
    {
        return $this->belongsToMany(CursoProgramado::class,'participante_curso','user_id','curso_programado_id')->withPivot('id');
    }
}
