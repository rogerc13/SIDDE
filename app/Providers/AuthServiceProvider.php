<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\User;
use App\Categoria;
use App\Curso;
use App\CursoProgramado;
use App\ParticipanteCurso;

use App\Policies\UserPolicy;
use App\Policies\CategoriaPolicy;
use App\Policies\CursoPolicy;
use App\Policies\CursoProgramadoPolicy;
use App\Policies\ParticipanteCursoPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
        User::class => UserPolicy::class,
        Categoria::class => CategoriaPolicy::class,
        Curso::class => CursoPolicy::class,
        CursoProgramado::class => CursoProgramadoPolicy::class,
        ParticipanteCurso::class => ParticipanteCursoPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
