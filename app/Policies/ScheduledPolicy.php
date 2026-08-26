<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Scheduled;

class ScheduledPolicy
{
    public function evaluate(User $user, Scheduled $scheduled)
    {
        if ($user->isAdministrador() || $user->isProgramador()) {
            return true;
        }

        if (!$user->isFacilitador()) {
            return false;
        }

        return $user->person->facilitator
            && $scheduled->facilitator_id === $user->person->facilitator->id;
    }

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

    public function cancel(User $user)
    {
        return $user->isAdministrador() || $user->isTecEducativa() || $user->isProgramador();
    }
}
