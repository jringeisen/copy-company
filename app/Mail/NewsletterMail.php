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
        /** @var \App\Models\Brand $brand */
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
        /** @var \App\Models\Brand $brand */
        $brand = $this->newsletterSend->brand;

        return new Content(
            view: 'emails.newsletter',
            with: [
                'post' => $this->newsletterSend->post,
                'brand' => $brand,
                'subscriber' => $this->subscriber,
                'previewText' => $this->newsletterSend->preview_text,
                'unsubscribeUrl' => route('public.subscribe.unsubscribe', [
                    'brand' => $brand->slug,
                    'token' => $this->subscriber->unsubscribe_token,
                ]),
            ],
        );
    }
}
