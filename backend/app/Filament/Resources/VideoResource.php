<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Jobs\ProcessVideoJob;
use App\Models\Video;
use Filament\Forms\Components\FileUpload;
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
            FileUpload::make('upload')
                ->label('Video file')
                ->acceptedFileTypes(['video/mp4', 'video/quicktime', 'video/webm'])
                ->maxSize(2048000)
                ->disk('videos')
                ->directory('raw')
                ->required()
                ->dehydrated(false),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
        ];
    }
}
