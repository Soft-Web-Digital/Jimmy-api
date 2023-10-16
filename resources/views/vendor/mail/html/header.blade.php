@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'KSBTECH' || trim($slot) == 'KSBTECH LIMITED')
<img src="{{ asset('ksbtech-logo.png') }}" class="logo" alt="KSBTECH Logo">
@else
{{ $slot }}
@endif
</a>
</td>
</tr>
