<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;
    
    public function get(User $user)
    {
       return $user->isAdministrador();
    }
    
    public function getAll(User $user)
    {
        return $user->isAdministrador();
    }
    
     public function store(User $user)
    {
        return $user->isAdministrador();
    }
    
    public function count(User $user)
    {
        return $user->isAdministrador();
    }
    
    public function update(User $user)
    {
      return $user->isAdministrador();
    }
    
    public function delete(User $user)
    {
        return $user->isAdministrador();
    }


//FACILITADORES POLICIES

    public function getFacilitador(User $user)
    {
       return $user->isAdministrador() || $user->isTecEducativa();
    }
    
    public function getAllFacilitador(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }
    
     public function storeFacilitador(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }
    
    public function countFacilitador(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }
    
    public function updateFacilitador(User $user)
    {
      return $user->isAdministrador() || $user->isTecEducativa();
    }
    
    public function deleteFacilitador(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }


//PARTICIPANTES POLICIES

    public function getParticipante(User $user)
    {
       return $user->isAdministrador() || $user->isProgramador();
    }
    
    public function getAllParticipante(User $user)
    {
        return $user->isAdministrador() || $user->isProgramador();
    }
    
     public function storeParticipante(User $user)
    {
        return $user->isAdministrador() || $user->isProgramador();
    }
    
    public function countParticipante(User $user)
    {
        return $user->isAdministrador() || $user->isProgramador();
    }
    
    public function updateParticipante(User $user)
    {
      return $user->isAdministrador() || $user->isProgramador();
    }
    
    public function deleteParticipante(User $user)
    {
        return $user->isAdministrador() || $user->isProgramador();
    }


    public function misDatos(User $user)
    {
       return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador() || $user->isFacilitador() || $user->isParticipante();
    }

    public function misDatosUpdate(User $user)
    {
       return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador() || $user->isFacilitador() || $user->isParticipante();
    }



}
