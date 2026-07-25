<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Models\User;
use App\Models\Category;
use App\Models\Course;
use App\Models\Scheduled;
use App\Models\Participant;
use App\Models\Location;

use App\Policies\UserPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CoursePolicy;
use App\Policies\ScheduledPolicy;
use App\Policies\ParticipantPolicy;
use App\Policies\LocationPolicy;

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
        Category::class => CategoryPolicy::class,
        Course::class => CoursePolicy::class,
        Scheduled::class => ScheduledPolicy::class,
        Participant::class => ParticipantPolicy::class,
        Location::class => LocationPolicy::class,
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
