<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $userName,
        public string $purpose = 'Tasdiqlash kodi',
        public string $description = 'Amalni tasdiqlash uchun quyidagi 6 xonali kodni saytga kiriting:',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name', 'RepetitorLMS').' — '.$this->purpose,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verification-code',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
