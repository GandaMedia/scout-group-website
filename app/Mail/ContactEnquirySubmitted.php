<?php

namespace App\Mail;

use App\Models\ContactEnquiry;
use App\Settings\GroupProfileSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEnquirySubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public $theme = 'scouts';

    public function __construct(public ContactEnquiry $contactEnquiry) {}

    public function envelope(): Envelope
    {
        $groupProfileSettings = app(GroupProfileSettings::class);

        return new Envelope(
            from: new Address($groupProfileSettings->mail_from_address, $groupProfileSettings->mail_from_name),
            replyTo: [
                new Address($this->contactEnquiry->email, $this->contactEnquiry->name),
            ],
            subject: 'New contact enquiry from '.$this->contactEnquiry->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact-enquiries.submitted',
        );
    }
}
