@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'JIMMY XCHANGE' || trim($slot) == 'Jimmy Xchange')
<img src="{{ asset('jimmy-xchange-logo.png') }}" class="logo" alt="Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
