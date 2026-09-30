@if ($script)
    @once
        <script src="{{ $scriptUrl }}" async defer></script>
    @endonce
@endif
<div {{ $attributes->class('cf-turnstile')->merge($dataAttributes()) }}></div>
