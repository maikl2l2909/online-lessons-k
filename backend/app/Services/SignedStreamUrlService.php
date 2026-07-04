<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\URL;

class SignedStreamUrlService
{
    public function generateStreamUrl(Video $video): string
    {
        return URL::temporarySignedRoute(
            'videos.stream',
            now()->addHours(2),
            ['video' => $video->id]
        );
    }

    public function generateThumbnailUrl(Video $video): string
    {
        return URL::temporarySignedRoute(
            'videos.thumbnail',
            now()->addHours(2),
            ['video' => $video->id]
        );
    }
}
