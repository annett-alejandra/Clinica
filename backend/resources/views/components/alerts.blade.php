@if (session('status'))
    <div x-data="{ show: true }"
         x-show="show"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-init="setTimeout(() => show = false, 5000)"
         class="notice notice-success max-w-7xl mx-auto mt-4 mb-2 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between gap-3 text-sm font-medium shadow-sm transition-all" role="status">
        <div class="flex items-center gap-2.5">
            <x-icon name="check-circle" class="w-4 h-4 text-emerald-600 shrink-0" />
            <p>{{ session('status') }}</p>
        </div>
        <button @click="show = false" class="notice-close text-emerald-600 hover:text-emerald-900 font-bold px-2 text-lg leading-none cursor-pointer" type="button" data-dismiss-alert aria-label="Cerrar mensaje">×</button>
    </div>
@endif

@if (session('error'))
    <div x-data="{ show: true }"
         x-show="show"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         x-init="setTimeout(() => show = false, 7000)"
         class="notice notice-error max-w-7xl mx-auto mt-4 mb-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between gap-3 text-sm font-medium shadow-sm transition-all" role="alert">
        <div class="flex items-center gap-2.5">
            <x-icon name="alert-circle" class="w-4 h-4 text-red-600 shrink-0" />
            <p>{{ session('error') }}</p>
        </div>
        <button @click="show = false" class="notice-close text-red-600 hover:text-red-900 font-bold px-2 text-lg leading-none cursor-pointer" type="button" data-dismiss-alert aria-label="Cerrar mensaje">×</button>
    </div>
@endif

@if ($errors->any())
    <div x-data="{ show: true }"
         x-show="show"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="notice notice-error max-w-7xl mx-auto mt-4 mb-2 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-start justify-between gap-3 text-sm shadow-sm transition-all" role="alert">
        <div>
            <p class="font-semibold mb-1">Revisa los datos del formulario:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
        <button @click="show = false" class="notice-close text-red-600 hover:text-red-900 font-bold px-2 text-lg leading-none cursor-pointer" type="button" data-dismiss-alert aria-label="Cerrar mensaje">×</button>
    </div>
@endif

<script>
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('[data-dismiss-alert]');
        if (btn) {
            var notice = btn.closest('.notice');
            if (notice) notice.remove();
        }
    });
</script>
