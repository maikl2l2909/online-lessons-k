<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;

class CoursePolicy
{
    public function view(?User $user, Course $course): bool
    {
        if ($course->isPublished()) {
            return true;
        }

        return $user?->isAdmin() || $user?->id === $course->user_id;
    }

    public function viewContent(User $user, Course $course): bool
    {
        if ($user->isAdmin() || $user->id === $course->user_id) {
            return true;
        }

        return $user->hasPurchased($course);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Course $course): bool
    {
        return $user->isAdmin();
    }
}
