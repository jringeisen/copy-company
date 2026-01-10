<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsletterSendResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_line' => $this->subject_line,
            'preview_text' => $this->preview_text,
            'status' => $this->status?->value,
            'total_recipients' => $this->total_recipients,
            'recipients_count' => $this->recipients_count,
            'sent_count' => $this->sent_count,
            'failed_count' => $this->failed_count,
            'opens' => $this->opens,
            'unique_opens' => $this->unique_opens,
            'clicks' => $this->clicks,
            'unique_clicks' => $this->unique_clicks,
            'unsubscribes' => $this->unsubscribes,
            'open_rate' => $this->open_rate,
            'click_rate' => $this->click_rate,
            'scheduled_at' => $this->scheduled_at?->format('M d, Y g:i A'),
            'sent_at' => $this->sent_at?->format('M d, Y g:i A'),
            'created_at' => $this->created_at?->format('M d, Y g:i A'),
            'post' => $this->when($this->relationLoaded('post'), fn () => [
                'id' => $this->post->id,
                'title' => $this->post->title,
                'slug' => $this->post->slug,
            ]),
        ];
    }
}
