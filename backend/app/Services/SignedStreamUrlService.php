<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\URL;

class SignedStreamUrlService
{
    public function generateStreamUrl(Video $video): string
    {
        $expires = now()->addHours(2)->timestamp;
        $hash = hash_hmac('sha256', $video->id . $expires, config('app.key'));
        return url("/api/v1/videos/{$video->id}/stream/playlist.m3u8?expires={$expires}&signature={$hash}");
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
