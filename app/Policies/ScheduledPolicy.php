<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScheduledPolicy
{
    use HandlesAuthorization;

    public function get(User $user)
    {
       return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }

    public function getAll(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }

     public function store(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }

    public function count(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }

    public function update(User $user)
    {
      return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }

    public function delete(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }

    public function misCursos(User $user)
    {
        return $user->isFacilitador() || $user->isParticipante();
    }

    public function userCursos(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }
}
