<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayrollGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Payroll $payroll) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Salary for {$this->payroll->month_label} Has Been Processed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payroll.generated',
        );
    }
}
