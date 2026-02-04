<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $account = $this->accounts->first();
        $limits = $account?->subscriptionLimits();

        $status = 'No Account';
        if ($account && $limits) {
            $subscription = $account->subscription('default');

            if ($limits->onTrial()) {
                $status = 'Trial';
            } elseif ($subscription?->stripe_status === 'paused') {
                $status = 'Paused';
            } elseif ($subscription && $subscription->active() && ! $subscription->onGracePeriod()) {
                $status = 'Active';
            } elseif ($subscription && $subscription->onGracePeriod()) {
                $status = 'Canceled';
            } elseif ($subscription && $subscription->pastDue()) {
                $status = 'Past Due';
            } elseif ($subscription && $subscription->hasIncompletePayment()) {
                $status = 'Incomplete';
            } elseif ($subscription && $subscription->canceled()) {
                $status = 'Canceled';
            } else {
                $status = 'Expired';
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'account_name' => $account?->name,
            'plan_label' => $limits?->getPlan()?->label() ?? 'No Plan',
            'status' => $status,
            'trial_ends_at' => $limits?->trialEndsAt()?->format('M d, Y'),
            'created_at' => $this->created_at->format('M d, Y'),
        ];
    }
}
