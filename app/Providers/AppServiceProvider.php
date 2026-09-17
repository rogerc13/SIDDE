<?php

namespace App\Providers;
use App\Models\User;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        Blade::directive('date', function ($expression) {
            return "<?php echo e(\\App\\Models\\Funciones::formatDate($expression)); ?>";
        });

        view()->composer('*', function($view){

            $view->with('logeado', Auth::user());
            View::share('categoriasAcciones', (\App\Models\Category::categoriasCursos()));
        });


    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
