<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $canView = $user
            ? $user->can('view', $this->resource)
            : $this->is_free_preview;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'is_free_preview' => $this->is_free_preview,
            'can_view' => $canView,
            'duration_seconds' => $this->when(
                $this->relationLoaded('video'),
                fn () => $this->video?->duration_seconds
            ),
            'video' => $this->when($canView && $this->relationLoaded('video'), function () {
                return new VideoResource($this->video);
            }),
            'progress' => $this->when($user, function () use ($user) {
                $progress = $this->progress->firstWhere('user_id', $user->id);

                return $progress ? [
                    'completed' => $progress->completed,
                    'watched_seconds' => $progress->watched_seconds,
                ] : null;
            }),
        ];
    }
}
