<?php

namespace App\Filament\Resources\VideoResource\Pages;

use App\Filament\Resources\VideoResource;
use App\Jobs\ProcessVideoJob;
use App\Models\Video;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateVideo extends CreateRecord
{
    protected static string $resource = VideoResource::class;

    protected function handleRecordCreation(array $data): Video
    {
        $data['original_filename'] = basename((string) ($data['storage_path'] ?? ''));
        $data['status'] = 'uploaded';
        $data['user_id'] = auth()->id();

        $video = Video::create($data);

        ProcessVideoJob::dispatch($video);

        return $video;
    }

    protected function getRedirectUrl(): string
    {
        return VideoResource::getUrl('index');
    }
}
