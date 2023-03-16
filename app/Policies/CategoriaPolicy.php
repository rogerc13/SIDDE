<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoriaPolicy
{
    use HandlesAuthorization;
    
    public function get(User $user)
    {
       return $user->isAdministrador() || $user->isTecEducativa();
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
}
