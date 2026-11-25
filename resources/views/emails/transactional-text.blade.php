{{ $greeting }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@foreach (($details ?? []) as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@if (! empty($actionUrl))

{{ $actionText }}: {{ $actionUrl }}
@endif

@foreach ($outro as $line)
{{ $line }}

@endforeach
{{ $salutation }}
