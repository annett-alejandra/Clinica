@props(['action', 'method' => 'POST'])

@php
    $formMethod = strtoupper($method);

    if (! in_array($formMethod, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        throw new InvalidArgumentException('Método de formulario no admitido.');
    }

    $htmlMethod = $formMethod === 'GET' ? 'get' : 'post';
@endphp

<form action="{{ $action }}" method="{{ $htmlMethod }}" {{ $attributes }}>
    @if ($htmlMethod === 'post')
        @csrf

        @if ($formMethod !== 'POST')
            @method($formMethod)
        @endif
    @endif

    {{ $slot }}
</form>
