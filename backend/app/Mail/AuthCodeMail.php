<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuthCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $details;

    public function __construct($code, $details = [])
    {
        $this->code = $code;
        $this->details = $details;
    }

    public function envelope(): Envelope
    {
        // Динамическая тема письма
        $subject = match ($this->details['type'] ?? 'default') {
            'reset' => 'SakuraNet: Восстановление пароля',
            'update' => 'SakuraNet: Смена пароля',
            'login' => 'SakuraNet: Подтверждение входа',
            default => 'SakuraNet: Код безопасности',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth_code',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}