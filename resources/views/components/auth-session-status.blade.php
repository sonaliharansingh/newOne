@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'status-message']) }}>
        {{ $status }}
    </div>
@endif
