<div 
    x-data="{
        isOpen: false,
        item: {
            title: '',
            category: '',
            image: '',
            specs: '',
            description: '',
            location: ''
        },
        orientation: 'landscape', // 'landscape' o 'portrait'
        init() {
            window.addEventListener('open-gallery-modal', (e) => {
                if (e.detail && e.detail.item) {
                    this.item = e.detail.item;
                    this.isOpen = true;
                    document.body.style.overflow = 'hidden';
                }
            });
        },
        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },
        toggleOrientation() {
            this.orientation = this.orientation === 'landscape' ? 'portrait' : 'landscape';
        }
    }"
    x-show="isOpen"
    x-cloak
    @keydown.escape.window="close()"
    class="modal-backdrop"
    style="display: none;"
>
    <div 
        @click.outside="close()" 
        class="modal-content shadow-luxury-lg"
        :class="orientation === 'portrait' ? 'max-w-xl' : 'max-w-5xl'"
    >
        <!-- Barra de Cabecera del Modal -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-white">
            <div class="flex items-center gap-3">
                <span class="editorial-overline" x-text="item.category"></span>
                <span class="text-slate-300">·</span>
                <span class="text-xs text-slate-500 font-mono" x-text="item.location"></span>
            </div>

            <div class="flex items-center gap-4">
                <!-- Switch Horizontal / Vertical -->
                <button 
                    @click="toggleOrientation()" 
                    type="button" 
                    class="btn-minimal text-slate-600 hover:text-primary flex items-center gap-1.5"
                    title="Alternar orientación de encuadre"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                    </svg>
                    <span class="text-xs" x-text="orientation === 'landscape' ? 'Ver Vertical' : 'Ver Panorámico'"></span>
                </button>

                <!-- Botón Cerrar -->
                <button 
                    @click="close()" 
                    type="button" 
                    class="w-8 h-8 rounded-full border border-slate-200 flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition-colors"
                    aria-label="Cerrar modal"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Cuerpo del Modal -->
        <div class="relative bg-slate-950 flex items-center justify-center overflow-hidden" :class="orientation === 'portrait' ? 'aspect-[4/5]' : 'aspect-video'">
            <img 
                :src="item.image" 
                :alt="item.title"
                class="w-full h-full object-cover" 
            />
        </div>

        <!-- Pie de Información y Ficha Técnica -->
        <div class="p-6 bg-white border-t border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h4 class="font-display text-2xl text-slate-900" x-text="item.title"></h4>
                <p class="text-sm text-slate-600 mt-1 max-w-2xl font-light" x-text="item.description"></p>
            </div>

            <div class="shrink-0 flex items-center gap-3">
                <span class="text-xs font-mono uppercase tracking-wider text-slate-500 bg-slate-100 px-3 py-1.5 rounded border border-slate-200" x-text="item.specs"></span>
            </div>
        </div>
    </div>
</div>

