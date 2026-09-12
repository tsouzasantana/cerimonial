<?php

namespace App\Mail;

use App\Models\DocumentFile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentFileMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public DocumentFile $document) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->document->title} — ".config('cerimonial.company_name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-file',
            with: ['document' => $this->document],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('local', $this->document->path)
                ->as($this->document->original_filename)
                ->withMime($this->document->mime_type ?? 'application/octet-stream'),
        ];
    }
}
