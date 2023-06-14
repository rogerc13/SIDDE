<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CursoPolicy
{
    use HandlesAuthorization;

    public function get(User $user)
    {
       return $user->isAdministrador() || $user->isTecEducativa();
    }

    public function getAccionFormacion(User $user){
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador() || $user->isFacilitador();
    }

    public function getAll(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }

     public function store(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }

    public function count(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }

    public function update(User $user)
    {
      return $user->isAdministrador() || $user->isTecEducativa();
    }

    public function delete(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa();
    }

    public function descargarDoc(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador() || $user->isFacilitador();
    }

    public function downloadAllFiles(User $user){
        return $user->isFacilitador();
    }
}
