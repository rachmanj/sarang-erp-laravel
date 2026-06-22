@props(['label', 'for' => null])

<div {{ $attributes->merge(['class' => 'form-group mb-2 mr-3']) }}>
    <label class="small text-muted d-block mb-1" @if($for) for="{{ $for }}" @endif>{{ $label }}</label>
    {{ $slot }}
</div>
