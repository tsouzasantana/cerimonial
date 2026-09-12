<?php

namespace App\Mail;

use App\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientActivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AuditLog $log) {}

    public function envelope(): Envelope
    {
        $contract = $this->log->contract;

        return new Envelope(
            subject: "Atividade do cliente no contrato #{$contract->id} — ".config('cerimonial.company_name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.client-activity',
            with: [
                'log' => $this->log,
                'contract' => $this->log->contract,
            ],
        );
    }
}
