@php($groupProfile = app(\App\Settings\GroupProfileSettings::class))

{!! $slot !!}

@isset($subcopy)
{!! $subcopy !!}
@endisset

© {{ date('Y') }} {{ $groupProfile->group_short_name }}. {{ __('All rights reserved.') }}
Charity No: {{ $groupProfile->charity_number }}
