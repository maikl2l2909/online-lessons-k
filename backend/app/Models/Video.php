<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Video extends Model
{
    protected $fillable = [
        'original_filename',
        'storage_path',
        'hls_path',
        'thumbnail_path',
        'duration_seconds',
        'status',
        'error_message',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => VideoStatus::class,
            'duration_seconds' => 'integer',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lesson(): HasOne
    {
        return $this->hasOne(Lesson::class);
    }

    public function isReady(): bool
    {
        return $this->status === VideoStatus::Ready;
    }
}
