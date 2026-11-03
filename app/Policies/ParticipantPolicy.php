<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Participant;

class ParticipantPolicy
{
    public function updateStatus(User $user, Participant $participant)
    {
        if ($user->isAdministrador()) {
            return true;
        }

        if (!$user->isFacilitador()) {
            return false;
        }

        $scheduled = $participant->scheduled()->withTrashed()->first();

        return $scheduled
            && $user->can('evaluate', $scheduled)
            && ($scheduled->isEnCurso() || $scheduled->isCulminado());
    }

    public function get(User $user)
    {
       return $user->isAdministrador() || $user->isFacilitador();
    }
    
    public function getAll(User $user)
    {
        return $user->isAdministrador();
    }

    public function getAllPorCurso(User $user)
    {
        return $user->isAdministrador() || $user->isFacilitador() || $user->isProgramador();
    }
    
     public function store(User $user)
    {
        return $user->isAdministrador() || $user->isProgramador();
    }
    
    public function count(User $user)
    {
        return $user->isAdministrador();
    }
    
    public function update(User $user)
    {
      return $user->isAdministrador() || $user->isFacilitador();
    }
    
    public function delete(User $user)
    {
        return $user->isAdministrador() || $user->isFacilitador() || $user->isProgramador();
    }
}
