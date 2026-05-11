<?php

namespace App\Mail;

use App\Models\SalaryHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncrementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SalaryHistory $salaryHistory,
        public readonly string        $emailSubject        = 'Salary Increment Notification',
        public readonly string        $introText           = '',
        public readonly string        $closingText         = '',
        public readonly string        $signatureName       = 'HR Department',
        public readonly string        $signatureTitle      = 'Human Resources',
        public readonly string        $signatureContact    = '',
        public readonly string        $companyName         = '',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.increment.notification',
        );
    }
}
