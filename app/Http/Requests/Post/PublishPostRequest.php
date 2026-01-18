<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PublishPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        return [
            'publish_to_blog' => ['boolean'],
            'send_as_newsletter' => ['boolean'],
            'subject_line' => ['required_if:send_as_newsletter,true', 'nullable', 'string', 'max:255'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'schedule_mode' => ['required', 'string', 'in:now,scheduled'],
            'scheduled_at' => ['required_if:schedule_mode,scheduled', 'nullable', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_line.required_if' => 'A subject line is required when sending as newsletter.',
            'scheduled_at.required_if' => 'A schedule date is required when scheduling for later.',
            'scheduled_at.after' => 'The scheduled date must be in the future.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $this->validateNewsletterSubscription($validator);
            },
        ];
    }

    protected function validateNewsletterSubscription(Validator $validator): void
    {
        // Only validate if trying to send newsletter
        if (! $this->boolean('send_as_newsletter')) {
            return;
        }

        /** @var \App\Models\User $user */
        $user = $this->user();
        $account = $user->currentAccount();

        if (! $account) {
            return;
        }

        $limits = $account->subscriptionLimits();

        // Trial users cannot send newsletters
        if ($limits->isOnFreeTrialOnly()) {
            $validator->errors()->add(
                'send_as_newsletter',
                'Newsletter sending requires an active subscription. Please upgrade your plan to send newsletters.'
            );
        }
    }
}
