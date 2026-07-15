<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'currency' => $this->currency,
            'thumbnail' => $this->thumbnail ? asset('storage/'.$this->thumbnail) : null,
            'status' => $this->status?->value,
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
            'lessons_count' => $this->whenCounted('lessons'),
            'is_purchased' => $this->when(
                $request->user(),
                fn () => $request->user()->hasPurchased($this->resource)
            ),
            'created_at' => $this->created_at,
        ];
    }
}
