@php($groupProfile = app(\App\Settings\GroupProfileSettings::class))

<x-mail::message>
# New contact enquiry

You have received a new message through the website contact form.

<x-mail::panel>
**Name:** {{ $contactEnquiry->name }}

**Email:** {{ $contactEnquiry->email }}

**Submitted:** {{ $contactEnquiry->submitted_at->toDayDateTimeString() }}
</x-mail::panel>

{{ $contactEnquiry->message }}

Thanks,<br>
{{ $groupProfile->group_short_name }}
</x-mail::message>
