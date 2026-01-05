<?php

namespace App\Mail;

use App\Models\NewsletterSend;
use App\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public NewsletterSend $newsletterSend,
        public Subscriber $subscriber
    ) {}

    public function envelope(): Envelope
    {
        $brand = $this->newsletterSend->brand;

        return new Envelope(
            from: new Address(
                $brand->getEmailFromAddress(),
                $brand->getEmailFromName()
            ),
            subject: $this->newsletterSend->subject_line,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter',
            with: [
                'post' => $this->newsletterSend->post,
                'brand' => $this->newsletterSend->brand,
                'subscriber' => $this->subscriber,
                'previewText' => $this->newsletterSend->preview_text,
                'unsubscribeUrl' => route('public.subscribe.unsubscribe', [
                    'brand' => $this->newsletterSend->brand->slug,
                    'token' => $this->subscriber->unsubscribe_token,
                ]),
            ],
        );
    }
}
