<?php

namespace App\Mail;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Contract $contract) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Contrato de cerimonial nº {$this->contract->id} — ".config('cerimonial.company_name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contract-pdf',
            with: ['contract' => $this->contract],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('local', $this->contract->pdf_path)
                ->as("contrato-{$this->contract->id}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
