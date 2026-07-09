<?php

namespace App\Filament\Resources;

use App\Enums\VideoStatus;
use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-video-camera';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('storage_path')
                ->label(fn (string $operation) => $operation === 'edit' ? 'Replace video file' : 'Video file')
                ->helperText(fn (string $operation) => $operation === 'edit'
                    ? 'Upload a new file to replace the current video. It will be re-processed.'
                    : null)
                ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                ->maxSize(512000)
                ->disk('videos')
                ->directory('raw')
                ->previewable(false)
                ->fetchFileInformation(false)
                ->required(fn (string $operation) => $operation === 'create'),
            TextInput::make('original_filename')
                ->label('File name')
                ->maxLength(255)
                ->visibleOn('edit'),
            Select::make('status')
                ->options(collect(VideoStatus::cases())
                    ->mapWithKeys(fn (VideoStatus $status) => [$status->value => ucfirst($status->value)])
                    ->all())
                ->required()
                ->visibleOn('edit'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_filename')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('duration_seconds')->label('Duration (s)'),
                TextColumn::make('created_at')->dateTime(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}
