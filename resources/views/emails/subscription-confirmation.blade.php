<x-mail::message>
# Confirm Your Subscription

Thanks for subscribing to **{{ $brandName }}**!

Please click the button below to confirm your subscription:

<x-mail::button :url="$confirmUrl">
Confirm Subscription
</x-mail::button>

If you didn't subscribe to this newsletter, you can safely ignore this email.

Thanks,<br>
{{ $brandName }}
</x-mail::message>
