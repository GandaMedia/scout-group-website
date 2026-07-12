@props(['url'])

@php($groupProfile = app(\App\Settings\GroupProfileSettings::class))

<tr>
<td class="header">
<a href="{{ $url }}" class="header-link">
<span class="header-logo">
<img src="{{ asset('web-app-manifest-192x192.png') }}" class="logo" alt="{{ $groupProfile->group_name }} logo">
</span>
<span class="header-title">{{ $groupProfile->group_name }}</span>
<span class="header-tagline">Skills for life</span>
</a>
</td>
</tr>
