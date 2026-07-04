<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonResource;
use App\Jobs\ProcessVideoJob;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): LessonResource|JsonResponse
    {
        $this->authorize('view', $lesson);

        $lesson->load(['video', 'section.course']);

        return new LessonResource($lesson);
    }

    public function markComplete(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('view', $lesson);

        LessonProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
            ['completed' => true, 'completed_at' => now()]
        );

        return response()->json(['message' => 'Lesson marked as complete.']);
    }

    public function updateProgress(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorize('view', $lesson);

        $validated = $request->validate([
            'watched_seconds' => ['required', 'integer', 'min:0'],
        ]);

        LessonProgress::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $lesson->id],
            ['watched_seconds' => $validated['watched_seconds']]
        );

        return response()->json(['message' => 'Progress updated.']);
    }
}
