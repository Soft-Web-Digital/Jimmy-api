<x-mail::message>
# {{ $title }}

{!! $body !!}

Regards,<br>
{{ config('app.name') }}
</x-mail::message>
