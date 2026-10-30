@if (session('status'))
    <div class="notice notice-success" role="status">
        <p>{{ session('status') }}</p>
        <button class="notice-close" type="button" data-dismiss-alert aria-label="Cerrar mensaje">×</button>
    </div>
@endif

@if ($errors->any())
    <div class="notice notice-error" role="alert">
        <div>
            <p>Revisa los datos del formulario:</p>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
