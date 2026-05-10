<?php

namespace App\Mail;

use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HolidayReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Holiday  $holiday,
        public readonly Employee $employee,
    ) {}

    public function envelope(): Envelope
    {
        $dateRange = $this->holiday->start_date->equalTo($this->holiday->end_date)
            ? $this->holiday->start_date->format('d M Y')
            : $this->holiday->start_date->format('d M') . ' – ' . $this->holiday->end_date->format('d M Y');

        return new Envelope(
            subject: "[Holiday Notice] {$this->holiday->title} · {$dateRange}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.holidays.reminder',
            with: [
                'holiday'  => $this->holiday,
                'employee' => $this->employee,
                'subject'  => $this->envelope()->subject,
            ],
        );
    }
}
