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
        $uploadPath = $this->form->getState()['upload'] ?? null;

        $video = Video::create([
            'original_filename' => basename((string) $uploadPath),
            'storage_path' => $uploadPath,
            'status' => 'uploaded',
            'user_id' => auth()->id(),
        ]);

        ProcessVideoJob::dispatch($video);

        return $video;
    }

    protected function getRedirectUrl(): string
    {
        return VideoResource::getUrl('index');
    }
}
