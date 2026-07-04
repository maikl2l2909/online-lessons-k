<?php

namespace App\Jobs;

use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ProcessVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public function __construct(public Video $video) {}

    public function handle(): void
    {
        $this->video->update(['status' => VideoStatus::Processing]);

        $disk = Storage::disk('videos');
        $inputPath = $disk->path($this->video->storage_path);

        if (! file_exists($inputPath)) {
            $this->failVideo('Source file not found.');

            return;
        }

        $outputDir = 'processed/'.$this->video->id;
        $outputPath = $disk->path($outputDir);
        $playlistRelative = $outputDir.'/playlist.m3u8';
        $thumbnailRelative = $outputDir.'/thumbnail.jpg';

        if (! is_dir($outputPath)) {
            mkdir($outputPath, 0755, true);
        }

        try {
            $duration = $this->getDuration($inputPath);

            $this->transcodeToHls($inputPath, $outputPath.'/playlist.m3u8');
            $this->generateThumbnail($inputPath, $disk->path($thumbnailRelative));

            $this->video->update([
                'hls_path' => $playlistRelative,
                'thumbnail_path' => $thumbnailRelative,
                'duration_seconds' => $duration,
                'status' => VideoStatus::Ready,
                'error_message' => null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Video processing failed', [
                'video_id' => $this->video->id,
                'error' => $e->getMessage(),
            ]);
            $this->failVideo($e->getMessage());
        }
    }

    private function getDuration(string $inputPath): int
    {
        $process = new Process([
            'ffprobe', '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $inputPath,
        ]);
        $process->run();

        return (int) round((float) trim($process->getOutput()));
    }

    private function transcodeToHls(string $input, string $output): void
    {
        $process = new Process([
            'ffmpeg', '-y', '-i', $input,
            '-vf', 'scale=w=1280:h=720:force_original_aspect_ratio=decrease',
            '-c:v', 'libx264', '-preset', 'fast', '-crf', '23',
            '-c:a', 'aac', '-b:a', '128k',
            '-hls_time', '10',
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', dirname($output).'/segment_%03d.ts',
            $output,
        ]);
        $process->setTimeout(3600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    private function generateThumbnail(string $input, string $output): void
    {
        $process = new Process([
            'ffmpeg', '-y', '-i', $input,
            '-ss', '00:00:05', '-vframes', '1',
            '-vf', 'scale=640:-1',
            $output,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }
    }

    private function failVideo(string $message): void
    {
        $this->video->update([
            'status' => VideoStatus::Failed,
            'error_message' => $message,
        ]);
    }
}
