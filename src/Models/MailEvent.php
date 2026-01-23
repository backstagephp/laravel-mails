<?php

namespace Backstage\Mails\Models;

use Backstage\Mails\Database\Factories\MailEventFactory;
use Backstage\Mails\Enums\EventType;
use Backstage\Mails\Events\MailEventLogged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property Mail $mail
 * @property EventType $type
 * @property string $ip_address
 * @property string $hostname
 * @property string $platform
 * @property string $os
 * @property string $browser
 * @property string $user_agent
 * @property string $city
 * @property string $country_code
 * @property string $link
 * @property string $tag
 * @property object $payload
 * @property Carbon $occurred_at
 * @property string $eventClass
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MailEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_id',
        'type',
        'ip_address',
        'hostname',
        'platform',
        'os',
        'browser',
        'user_agent',
        'city',
        'country_code',
        'link',
        'tag',
        'payload',
        'occurred_at',
        'unsuppressed_at',
    ];

    protected $casts = [
        'type' => EventType::class,
        'payload' => 'object',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'occurred_at' => 'datetime',
        'unsuppressed_at' => 'datetime',
    ];

    public function getTable()
    {
        return config('mails.database.tables.events');
    }

    protected static function booted(): void
    {
        static::creating(function (MailEvent $mailEvent): void {
            event(MailEventLogged::class, $mailEvent);

            $eventClass = $mailEvent->eventClass;

            if (class_exists($eventClass)) {
                $eventClass::dispatch($mailEvent);
            }
        });
    }

    protected static function newFactory(): Factory
    {
        return MailEventFactory::new();
    }

    public function mail()
    {
        return $this->belongsTo(config('mails.models.mail'));
    }

    public function scopeSuppressed(Builder $builder): void
    {
        $builder->where('type', EventType::HARD_BOUNCED);
    }

    protected function eventClass(): Attribute
    {
        return Attribute::make(get: fn (): string => 'Backstage\Mails\Events\Mail' . Str::studly($this->type->value));
    }
}
