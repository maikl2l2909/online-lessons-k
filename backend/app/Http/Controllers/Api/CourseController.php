<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CourseController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->withCount('lessons')
            ->latest()
            ->paginate(12);

        return CourseResource::collection($courses);
    }

    public function show(Request $request, Course $course): CourseResource|JsonResponse
    {
        if (! $request->user()?->can('view', $course) && ! $course->isPublished()) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $course->load([
            'sections.lessons.video',
            'sections.lessons.progress' => fn ($q) => $q->where('user_id', $request->user()?->id),
        ]);

        return new CourseResource($course);
    }

    public function myCourses(Request $request): AnonymousResourceCollection
    {
        $courseIds = $request->user()
            ->orders()
            ->where('status', 'paid')
            ->pluck('course_id');

        $courses = Course::query()
            ->whereIn('id', $courseIds)
            ->withCount('lessons')
            ->get();

        return CourseResource::collection($courses);
    }
}
