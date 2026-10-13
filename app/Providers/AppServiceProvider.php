<?php

namespace App\Providers;
use App\Models\User;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrapThree();

        Blade::directive('date', function ($expression) {
            return "<?php echo e(\\App\\Models\\Funciones::formatDate($expression)); ?>";
        });

        // SQLite's LIKE only folds ASCII case, so accented text ("Ética") would
        // never match an unaccented term ("Etica"). Register a strip_accents()
        // function so the accent-insensitive search helper can fold the stored
        // column in SQL. MySQL/MariaDB do not need it (utf8mb4_unicode_ci
        // already matches accents), so this is registered for SQLite only.
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event) {
            if ($event->connection->getDriverName() !== 'sqlite') {
                return;
            }

            $event->connection->getPdo()->sqliteCreateFunction(
                'strip_accents',
                fn ($value) => Str::ascii((string) $value),
                1
            );
        });

        view()->composer('*', function($view){

            $view->with('logeado', Auth::user());
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
