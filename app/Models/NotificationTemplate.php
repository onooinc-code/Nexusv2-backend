<?php

namespace App\Models;

class NotificationTemplate extends BaseModel
{
    public const CHANNEL_EMAIL = 'email';
    public const CHANNEL_SMS = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_PUSH = 'push';

    public const CHANNELS = [
        self::CHANNEL_EMAIL,
        self::CHANNEL_SMS,
        self::CHANNEL_WHATSAPP,
        self::CHANNEL_PUSH,
    ];

    protected $fillable = [
        'key',
        'name',
        'subject',
        'body',
        'channels',
    ];

    protected $casts = [
        'channels' => 'array',
    ];

    /**
     * Render the template body with given variables.
     *
     * @param array<string, string> $variables
     */
    public function render(array $variables): string
    {
        $body = $this->body;
        foreach ($variables as $key => $value) {
            $body = str_replace("{{$key}}", $value, $body);
            $body = str_replace("{{ $key }}", $value, $body);
        }
        return $body;
    }

    /**
     * Render the subject line with given variables.
     *
     * @param array<string, string> $variables
     */
    public function renderSubject(array $variables): ?string
    {
        if (! $this->subject) {
            return null;
        }

        $subject = $this->subject;
        foreach ($variables as $key => $value) {
            $subject = str_replace("{{$key}}", $value, $subject);
            $subject = str_replace("{{ $key }}", $value, $subject);
        }

        return $subject;
    }
}
