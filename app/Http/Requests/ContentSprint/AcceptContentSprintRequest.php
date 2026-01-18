<?php

namespace App\Http\Requests\ContentSprint;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AcceptContentSprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('contentSprint'));
    }

    public function rules(): array
    {
        return [
            'idea_indices' => ['required', 'array', 'min:1'],
            'idea_indices.*' => ['required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'idea_indices.required' => 'Please select at least one idea to accept.',
            'idea_indices.min' => 'Please select at least one idea to accept.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $this->validatePostLimit($validator);
            },
        ];
    }

    protected function validatePostLimit(Validator $validator): void
    {
        /** @var \App\Models\User $user */
        $user = $this->user();
        $account = $user->currentAccount();

        if (! $account) {
            return;
        }

        $limits = $account->subscriptionLimits();
        $postLimit = $limits->getPostLimit();

        // If unlimited posts, no validation needed
        if ($postLimit === null) {
            return;
        }

        $remainingPosts = $limits->getRemainingPosts();
        $selectedCount = count($this->input('idea_indices', []));

        if ($selectedCount > $remainingPosts) {
            $validator->errors()->add(
                'idea_indices',
                $remainingPosts === 0
                    ? 'You have reached your post limit for this month. Please upgrade your plan to create more posts.'
                    : "You can only create {$remainingPosts} more post(s) this month. Please select fewer ideas or upgrade your plan."
            );
        }
    }
}
