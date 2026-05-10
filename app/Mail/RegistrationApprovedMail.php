<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\PendingRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly PendingRegistration $registration,
        public readonly Employee            $employee,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Account Has Been Activated — Welcome to the Team!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.approved',
        );
    }
}
