<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Enums\VideoStatus;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@courses.test',
            'password' => 'password',
            'role' => UserRole::Admin,
        ]);

        User::factory()->create([
            'name' => 'Student',
            'email' => 'student@courses.test',
            'password' => 'password',
            'role' => UserRole::Student,
        ]);

        $course = Course::create([
            'title' => 'Laravel Masterclass',
            'slug' => 'laravel-masterclass',
            'description' => 'A complete, project-driven guide to building modern web applications with Laravel. '
                .'Start from a blank project and finish with a production-ready app — covering routing, Eloquent, '
                .'authentication, testing, queues, and deployment along the way. Every episode is bite-sized and '
                .'hands-on, so you can follow along at your own pace.',
            'price_cents' => 4999,
            'currency' => 'usd',
            'status' => CourseStatus::Published,
            'user_id' => $admin->id,
        ]);

        $curriculum = [
            'Getting Started' => [
                ['Welcome to the Masterclass', 'A quick tour of what we will build and how the course is structured.', true, 214],
                ['Installing PHP, Composer & Laravel', 'Set up a clean local environment on macOS, Windows, or Linux.', true, 486],
                ['Your First Laravel Project', 'Create a new app and walk through the directory structure.', false, 372],
                ['Understanding the Request Lifecycle', 'How a request flows from the browser through Laravel to a response.', false, 528],
            ],
            'Routing & Controllers' => [
                ['Defining Routes', 'Basic routes, route parameters, and named routes explained.', false, 412],
                ['Controllers & Resource Routes', 'Organize your logic with controllers and RESTful resource routes.', false, 634],
                ['Form Requests & Validation', 'Validate incoming data cleanly with dedicated form request classes.', false, 559],
                ['Middleware Basics', 'Filter and modify requests using built-in and custom middleware.', false, 448],
            ],
            'Blade & the Frontend' => [
                ['Blade Templating 101', 'Layouts, components, and control structures with Blade.', false, 505],
                ['Reusable Blade Components', 'Build a component library to keep your views DRY.', false, 467],
                ['Working with Vite & Tailwind', 'Compile assets and style your app with Tailwind CSS.', false, 583],
            ],
            'Eloquent ORM' => [
                ['Models & Migrations', 'Define your database schema and map it to Eloquent models.', false, 621],
                ['Relationships', 'One-to-many, many-to-many, and polymorphic relationships in depth.', false, 742],
                ['Query Builder & Scopes', 'Write expressive queries and reuse them with query scopes.', false, 538],
                ['Seeders & Factories', 'Generate realistic test data for local development.', false, 396],
            ],
            'Authentication & Authorization' => [
                ['Authentication with Breeze', 'Add login, registration, and password resets in minutes.', false, 489],
                ['Policies & Gates', 'Control who can do what with authorization policies.', false, 617],
                ['API Tokens with Sanctum', 'Protect API routes and issue tokens for SPAs and mobile apps.', false, 553],
            ],
            'Shipping a Real Project' => [
                ['Queues & Background Jobs', 'Offload slow work to queues for a snappy user experience.', false, 664],
                ['Writing Tests with Pest', 'Gain confidence with feature and unit tests.', false, 708],
                ['Deploying to Production', 'Ship your app with zero-downtime deploys and best practices.', false, 792],
            ],
        ];

        $sectionOrder = 0;

        foreach ($curriculum as $sectionTitle => $lessons) {
            $section = Section::create([
                'course_id' => $course->id,
                'title' => $sectionTitle,
                'order' => $sectionOrder++,
            ]);

            foreach ($lessons as $order => [$title, $description, $isFree, $duration]) {
                $video = Video::create([
                    'original_filename' => str($title)->slug().'.mp4',
                    'duration_seconds' => $duration,
                    'status' => VideoStatus::Ready,
                    'user_id' => $admin->id,
                ]);

                Lesson::create([
                    'section_id' => $section->id,
                    'video_id' => $video->id,
                    'title' => $title,
                    'description' => $description,
                    'order' => $order,
                    'is_free_preview' => $isFree,
                ]);
            }
        }
    }
}
