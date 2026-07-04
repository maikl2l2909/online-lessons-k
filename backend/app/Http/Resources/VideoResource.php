<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'duration_seconds' => $this->duration_seconds,
            'thumbnail_url' => $this->thumbnail_path
                ? url('/api/videos/'.$this->id.'/thumbnail')
                : null,
            'stream_url' => $this->isReady()
                ? url('/api/videos/'.$this->id.'/stream')
                : null,
        ];
    }
}
