<?php

namespace Backstage\Mails\Models;

use Backstage\Mails\Database\Factories\MailAttachmentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property-read string $disk
 * @property-read string $uuid
 * @property-read string $filename
 * @property-read string $mime
 * @property-read bool $inline
 * @property-read int $size
 * @property-read Mail $mail
 * @property-read string $storagePath
 * @property-read string $fileData
 */
class MailAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'disk',
        'uuid',
        'filename',
        'mime',
        'inline',
        'size',
    ];

    protected $casts = [
        'disk' => 'string',
        'uuid' => 'string',
        'filename' => 'string',
        'mime' => 'string',
        'inline' => 'boolean',
        'size' => 'integer',
    ];

    public function getTable()
    {
        return config('mails.database.tables.attachments');
    }

    protected static function newFactory(): Factory
    {
        return MailAttachmentFactory::new();
    }

    public function mail(): BelongsTo
    {
        return $this->belongsTo(config('mails.models.mail'));
    }

    protected function storagePath(): Attribute
    {
        return Attribute::make(get: fn (): string => rtrim((string) config('mails.logging.attachments.root'), '/').'/'.$this->getKey().'/'.$this->filename);
    }

    protected function fileData(): Attribute
    {
        return Attribute::make(get: fn () => Storage::disk($this->disk)->get($this->storagePath));
    }

    public function downloadFileFromStorage(?string $filename = null): string
    {
        return Storage::disk($this->disk)
            ->download(
                $this->storagePath,
                $filename ?? $this->filename, [
                    'Content-Type' => $this->mime,
                ]);
    }
}
