<x-mail::message>
# You've Been Invited!

**{{ $inviterName }}** has invited you to join **{{ $accountName }}** on Copy Company.

You've been invited as a **{{ $role }}**.

<x-mail::button :url="$acceptUrl">
Accept Invitation
</x-mail::button>

This invitation will expire on {{ $expiresAt }}.

If you weren't expecting this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
