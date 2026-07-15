<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCourseController extends Controller
{
    public function index(): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        return CourseResource::collection(
            Course::withCount('lessons')->latest()->paginate(20)
        );
    }

    public function store(Request $request): CourseResource
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', 'in:draft,published,archived'],
        ]);

        $course = Course::create([
            ...$validated,
            'slug' => Str::slug($validated['title']).'-'.Str::random(6),
            'user_id' => $request->user()->id,
        ]);

        return new CourseResource($course);
    }

    public function update(Request $request, Course $course): CourseResource
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', 'in:draft,published,archived'],
        ]);

        $course->update($validated);

        return new CourseResource($course->fresh());
    }

    public function destroy(Course $course): JsonResponse
    {
        $course->delete();

        return response()->json(['message' => 'Course deleted.']);
    }

    public function storeSection(Request $request, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $section = $course->sections()->create($validated);

        return response()->json(['section' => $section], 201);
    }

    public function storeLesson(Request $request, Section $section): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_id' => ['nullable', 'exists:videos,id'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_free_preview' => ['sometimes', 'boolean'],
        ]);

        $lesson = $section->lessons()->create($validated);

        return response()->json(['lesson' => $lesson], 201);
    }

    public function reorderLessons(Request $request, Section $section): JsonResponse
    {
        $validated = $request->validate([
            'lesson_ids' => ['required', 'array'],
            'lesson_ids.*' => ['integer', 'exists:lessons,id'],
        ]);

        foreach ($validated['lesson_ids'] as $order => $lessonId) {
            Lesson::where('id', $lessonId)
                ->where('section_id', $section->id)
                ->update(['order' => $order]);
        }

        return response()->json(['message' => 'Lessons reordered.']);
    }
}
