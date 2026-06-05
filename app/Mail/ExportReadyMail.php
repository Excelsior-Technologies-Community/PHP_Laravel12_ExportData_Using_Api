<?php
// app/Mail/ExportReadyMail.php

namespace App\Mail;

use App\Models\ExportLog;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $exportLog;

    public function __construct(ExportLog $exportLog)
    {
        $this->exportLog = $exportLog;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Product Export is Ready',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.export-ready',
        );
    }
}