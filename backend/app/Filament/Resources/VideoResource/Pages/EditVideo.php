<?php

namespace App\Filament\Resources\VideoResource\Pages;

use App\Filament\Resources\VideoResource;
use App\Jobs\ProcessVideoJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditVideo extends EditRecord
{
    protected static string $resource = VideoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $isReplacement = isset($data['storage_path']) && $data['storage_path'] !== $record->storage_path;

        if ($isReplacement) {
            $data['original_filename'] = basename((string) $data['storage_path']);
            $data['status'] = 'uploaded';
        }

        $record->update($data);

        if ($isReplacement) {
            ProcessVideoJob::dispatch($record);
        }

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return VideoResource::getUrl('index');
    }
}
