<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            'description' => 'Learn Laravel from scratch with hands-on projects and real-world patterns.',
            'price_cents' => 4999,
            'currency' => 'usd',
            'status' => CourseStatus::Published,
            'user_id' => $admin->id,
        ]);

        $section = Section::create([
            'course_id' => $course->id,
            'title' => 'Getting Started',
            'order' => 0,
        ]);

        Lesson::create([
            'section_id' => $section->id,
            'title' => 'Welcome to the course',
            'description' => 'Introduction and course overview.',
            'order' => 0,
            'is_free_preview' => true,
        ]);

        Lesson::create([
            'section_id' => $section->id,
            'title' => 'Setting up your environment',
            'description' => 'Install PHP, Composer, and Laravel.',
            'order' => 1,
            'is_free_preview' => false,
        ]);
    }
}
