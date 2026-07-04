<?php

namespace App\Providers;

use App\Policies\CoursePolicy;
use App\Policies\LessonPolicy;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Course::class, CoursePolicy::class);
        Gate::policy(Lesson::class, LessonPolicy::class);
    }
}
