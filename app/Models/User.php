<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Role;
use App\Models\Person;


class User extends Authenticatable
{
    use Notifiable;
    use SoftDeletes;


  
    const MAX_LENGTH_EMAIL = 60;
    const MAX_LENGTH_PASSWORD = 100;
    const MAX_LENGTH_NAME = 45;
    const MAX_LENGTH_LAST_NAME = 45;  
    const MAX_LENGTH_ID_NUMBER = 45; 
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'users';
    protected $fillable = [
      'role_id', 'person_id','email','password',
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
        return $this->role_id==Role::ADMINISTRADOR;
    }

    public function isTecEducativa(){
        return $this->role_id==Role::TECNOLOGIA_EDUCATIVA;
    }

    public function isProgramador(){
        return $this->role_id==Role::PROGRAMADOR;
    }

    public function isFacilitador(){
        return $this->role_id==Role::FACILITADOR;
    }

    public function isParticipante(){
        return $this->role_id==Role::PARTICIPANTE;
    }


    public function role()
    {
      return $this->belongsTo(Role::class);
    }

    public function person(){
      return $this->belongsTo(Person::class);
    }
    public function participant(){

      return $this->hasManyThrough(Participant::class,Person::class);
    }

    public function cursoFacilitador() // cursos de un facilitador
    {
      return $this->hasManyThrough(Scheduled::class , Facilitator::class,'person_id');
    }

    public function cursosParticipante() //directo a la tabla pivote (Cursos de un participante)
    {
      return $this->hasManyThrough(Participant::class, Person::class,'id','person_id');
    }

}
