<?php

namespace App\Policies;

use App\Models\Lesson;
use App\Models\User;

class LessonPolicy
{
    public function view(?User $user, Lesson $lesson): bool
    {
        if ($lesson->is_free_preview) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $course = $lesson->section->course;

        return app(CoursePolicy::class)->viewContent($user, $course);
    }
}
