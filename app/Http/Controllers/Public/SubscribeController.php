<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriberStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\SubscribeRequest;
use App\Mail\SubscriptionConfirmation;
use App\Models\Brand;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SubscribeController extends Controller
{
    public function store(SubscribeRequest $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validated();

        $existing = Subscriber::where('brand_id', $brand->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existing) {
            if ($existing->status === SubscriberStatus::Unsubscribed) {
                $existing->update([
                    'status' => SubscriberStatus::Pending,
                    'confirmation_token' => Str::random(64),
                    'unsubscribed_at' => null,
                ]);

                Mail::to($existing->email)->send(new SubscriptionConfirmation($existing, $brand));

                return back()->with('success', 'Please check your email to confirm your subscription.');
            }

            return back()->with('info', 'You are already subscribed.');
        }

        $subscriber = Subscriber::create([
            'brand_id' => $brand->id,
            'email' => $validated['email'],
            'name' => $validated['name'] ?? null,
            'status' => SubscriberStatus::Pending,
            'confirmation_token' => Str::random(64),
        ]);

        Mail::to($subscriber->email)->send(new SubscriptionConfirmation($subscriber, $brand));

        return back()->with('success', 'Please check your email to confirm your subscription.');
    }

    public function confirm(Brand $brand, string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('brand_id', $brand->id)
            ->where('confirmation_token', $token)
            ->first();

        if (! $subscriber) {
            return redirect()->route('public.blog.index', $brand)
                ->with('error', 'Invalid confirmation link.');
        }

        $subscriber->update([
            'status' => SubscriberStatus::Confirmed,
            'confirmed_at' => now(),
            'confirmation_token' => null,
        ]);

        return redirect()->route('public.blog.index', $brand)
            ->with('success', 'Your subscription has been confirmed!');
    }

    public function unsubscribe(Brand $brand, string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('brand_id', $brand->id)
            ->where('unsubscribe_token', $token)
            ->first();

        if (! $subscriber) {
            return redirect()->route('public.blog.index', $brand)
                ->with('error', 'Invalid unsubscribe link.');
        }

        $subscriber->update([
            'status' => SubscriberStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ]);

        return redirect()->route('public.blog.index', $brand)
            ->with('success', 'You have been unsubscribed.');
    }
}
