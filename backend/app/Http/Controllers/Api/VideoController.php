<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VideoResource;
use App\Jobs\ProcessVideoJob;
use App\Models\Lesson;
use App\Models\Video;
use App\Services\SignedStreamUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function __construct(
        private SignedStreamUrlService $streamService
    ) {}

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/webm', 'max:2048000'],
        ]);

        $file = $request->file('video');
        $uuid = Str::uuid()->toString();
        $path = $file->storeAs("videos/raw/{$uuid}", $file->getClientOriginalName(), 'videos');

        $video = Video::create([
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $path,
            'status' => 'uploaded',
            'user_id' => $request->user()->id,
        ]);

        ProcessVideoJob::dispatch($video);

        return response()->json([
            'message' => 'Video uploaded. Processing started.',
            'video' => new VideoResource($video),
        ], 201);
    }

    public function show(Video $video): VideoResource
    {
        return new VideoResource($video);
    }

    public function stream(Request $request, Video $video, string $file = 'playlist.m3u8'): mixed
    {
        $expires = $request->query('expires');
        $signature = $request->query('signature');

        if (! $expires || ! $signature || $expires < now()->timestamp) {
            return response()->json(['message' => 'Invalid or expired link.'], 403);
        }

        $expectedHash = hash_hmac('sha256', $video->id . $expires, config('app.key'));
        if (! hash_equals($expectedHash, $signature)) {
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        if (! $video->isReady() || ! $video->hls_path) {
            return response()->json(['message' => 'Video not ready.'], 422);
        }

        $baseDir = dirname($video->hls_path);

        if (str_contains($file, '..') || str_contains($file, '/')) {
            return response()->json(['message' => 'Invalid file.'], 400);
        }

        $filePath = Storage::disk('videos')->path($baseDir . '/' . $file);

        if (! file_exists($filePath)) {
            return response()->json(['message' => 'Video file not found.'], 404);
        }

        if (str_ends_with($file, '.m3u8')) {
            $content = file_get_contents($filePath);
            $queryString = "expires={$expires}&signature={$signature}";
            // Append query string to all segment lines, handling potential \r
            $content = preg_replace('/^(.*\.ts)(\r?)$/m', "$1?{$queryString}$2", $content);

            return response($content, 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
                'Cache-Control' => 'no-cache',
            ]);
        }

        return response()->file($filePath, [
            'Content-Type' => 'video/MP2T',
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function thumbnail(Request $request, Video $video): JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'Invalid or expired link.'], 403);
        }

        if (! $video->thumbnail_path) {
            return response()->json(['message' => 'Thumbnail not available.'], 404);
        }

        $path = Storage::disk('videos')->path($video->thumbnail_path);

        return response()->file($path);
    }

    public function signedUrl(Request $request, Video $video): JsonResponse
    {
        $lesson = $video->lesson;
        if ($lesson) {
            $this->authorize('view', $lesson);
        } elseif (! $request->user()?->isAdmin()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'stream_url' => $this->streamService->generateStreamUrl($video),
            'thumbnail_url' => $video->thumbnail_path
                ? $this->streamService->generateThumbnailUrl($video)
                : null,
            'expires_at' => now()->addHours(2)->toIso8601String(),
        ]);
    }
}
