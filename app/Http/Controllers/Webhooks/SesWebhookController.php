<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\SesEventProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SesWebhookController extends Controller
{
    public function __construct(
        private SesEventProcessor $processor
    ) {}

    /**
     * Handle incoming SES webhook notifications via SNS.
     */
    public function handle(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true);

        if (! $payload) {
            Log::warning('SES webhook received invalid JSON');

            return response('Invalid JSON', 400);
        }

        $type = $payload['Type'] ?? null;

        // Handle SNS subscription confirmation
        if ($type === 'SubscriptionConfirmation') {
            return $this->handleSubscriptionConfirmation($payload);
        }

        // Handle actual notifications
        if ($type === 'Notification') {
            return $this->handleNotification($payload);
        }

        Log::info('SES webhook received unknown type', ['type' => $type]);

        return response('OK', 200);
    }

    /**
     * Confirm SNS subscription by visiting the SubscribeURL.
     */
    private function handleSubscriptionConfirmation(array $payload): Response
    {
        $subscribeUrl = $payload['SubscribeURL'] ?? null;

        if ($subscribeUrl) {
            try {
                Http::get($subscribeUrl);
                Log::info('SNS subscription confirmed', [
                    'topic' => $payload['TopicArn'] ?? 'unknown',
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to confirm SNS subscription', [
                    'error' => $e->getMessage(),
                ]);

                return response('Failed to confirm', 500);
            }
        }

        return response('Confirmed', 200);
    }

    /**
     * Process the actual SES notification.
     */
    private function handleNotification(array $payload): Response
    {
        $message = $payload['Message'] ?? null;

        if (! $message) {
            return response('No message', 400);
        }

        // The Message field contains a JSON string that needs to be decoded
        $messageData = json_decode($message, true);

        if (! $messageData) {
            Log::warning('SES webhook notification has invalid message JSON');

            return response('Invalid message JSON', 400);
        }

        try {
            $this->processor->process($messageData);
        } catch (\Exception $e) {
            Log::error('Failed to process SES event', [
                'error' => $e->getMessage(),
                'message' => $messageData,
            ]);

            // Still return 200 to prevent SNS retries flooding us
            // The event is logged for manual review
        }

        return response('OK', 200);
    }
}
