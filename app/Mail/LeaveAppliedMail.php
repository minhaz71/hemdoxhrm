<?php

namespace App\Mail;

use App\Models\Leave;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveAppliedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Leave $leave) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Leave Application: {$this->leave->employee->full_name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leaves.applied',
        );
    }
}
