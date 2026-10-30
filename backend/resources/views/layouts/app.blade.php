<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Clínica Vida Salud')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('styles')
    <script src="{{ asset('js/app.js') }}" defer></script>
</head>
<body>
    <a class="skip-link" href="#contenido">Ir al contenido</a>

    <header class="site-header">
        <div class="container">
            <a class="brand" href="{{ route('inicio') }}">Clínica Vida Salud</a>
        </div>
    </header>

    <main id="contenido" class="container main-content" tabindex="-1">
        @include('components.alerts')
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
