<?php

namespace Database\Seeders\Support;

use App\Enums\VideoStatus;
use App\Models\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class DemoVideoProvisioner
{
    private const SAMPLE_SOURCE = 'raw/demo-sample.mp4';

    private const TEMPLATE_DIR = 'processed/_template';

    private ?int $templateDuration = null;

    /**
     * @param  Collection<int, Video>  $videos
     */
    public function provision(Collection $videos): void
    {
        if ($videos->isEmpty()) {
            return;
        }

        $this->ensureTemplate();

        $disk = Storage::disk('videos');

        foreach ($videos as $video) {
            $targetDir = 'processed/'.$video->id;
            $targetPath = $disk->path($targetDir);

            if (is_dir($targetPath)) {
                File::deleteDirectory($targetPath);
            }

            File::copyDirectory($disk->path(self::TEMPLATE_DIR), $targetPath);

            $video->update([
                'storage_path' => self::SAMPLE_SOURCE,
                'hls_path' => $targetDir.'/playlist.m3u8',
                'thumbnail_path' => $targetDir.'/thumbnail.jpg',
                'duration_seconds' => $this->templateDuration,
                'status' => VideoStatus::Ready,
                'error_message' => null,
            ]);
        }
    }

    private function ensureTemplate(): void
    {
        $disk = Storage::disk('videos');
        $playlistPath = $disk->path(self::TEMPLATE_DIR.'/playlist.m3u8');

        if (file_exists($playlistPath)) {
            $this->templateDuration = $this->getDuration($disk->path(self::SAMPLE_SOURCE));

            return;
        }

        $this->generateSampleSource($disk->path(self::SAMPLE_SOURCE));

        $outputDir = $disk->path(self::TEMPLATE_DIR);
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $this->transcodeToHls($disk->path(self::SAMPLE_SOURCE), $outputDir.'/playlist.m3u8');
        $this->generateThumbnail($disk->path(self::SAMPLE_SOURCE), $outputDir.'/thumbnail.jpg');

        $this->templateDuration = $this->getDuration($disk->path(self::SAMPLE_SOURCE));
    }

    private function generateSampleSource(string $outputPath): void
    {
        $directory = dirname($outputPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $process = new Process([
            'ffmpeg', '-y',
            '-f', 'lavfi', '-i', 'testsrc=duration=10:size=1280x720:rate=30',
            '-f', 'lavfi', '-i', 'sine=frequency=440:duration=10',
            '-c:v', 'libx264', '-preset', 'ultrafast', '-pix_fmt', 'yuv420p',
            '-c:a', 'aac', '-shortest',
            $outputPath,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to generate demo sample video: '.$process->getErrorOutput());
        }
    }

    private function transcodeToHls(string $input, string $output): void
    {
        $process = new Process([
            'ffmpeg', '-y', '-i', $input,
            '-vf', 'scale=w=1280:h=720:force_original_aspect_ratio=decrease',
            '-c:v', 'libx264', '-preset', 'ultrafast', '-crf', '23',
            '-c:a', 'aac', '-b:a', '128k',
            '-hls_time', '10',
            '-hls_playlist_type', 'vod',
            '-hls_segment_filename', dirname($output).'/segment_%03d.ts',
            $output,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to transcode demo video: '.$process->getErrorOutput());
        }
    }

    private function generateThumbnail(string $input, string $output): void
    {
        $process = new Process([
            'ffmpeg', '-y', '-i', $input,
            '-ss', '00:00:02', '-vframes', '1',
            '-vf', 'scale=640:-1',
            $output,
        ]);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('Failed to generate demo thumbnail: '.$process->getErrorOutput());
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
}
