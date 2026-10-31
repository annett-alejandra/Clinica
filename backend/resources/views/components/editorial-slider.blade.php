@props([
    'slides' => [],
])

<div 
    x-data="{
        current: 0,
        slides: {{ json_encode($slides) }},
        next() {
            this.current = (this.current + 1) % this.slides.length;
        },
        prev() {
            this.current = (this.current - 1 + this.slides.length) % this.slides.length;
        },
        formatNum(n) {
            return String(n + 1).padStart(2, '0');
        },
        openDetailModal() {
            window.dispatchEvent(new CustomEvent('open-gallery-modal', {
                detail: {
                    item: this.slides[this.current]
                }
            }));
        }
    }" 
    class="slider-container shadow-luxury"
>
    <!-- Viewport con Ken Burns y revelado -->
    <div class="slider-viewport group">
        <template x-for="(slide, index) in slides" :key="index">
            <div 
                x-show="current === index" 
                x-transition:enter="transition ease-out duration-700"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-500"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="absolute inset-0 w-full h-full"
            >
                <img 
                    :src="slide.image" 
                    :alt="slide.title"
                    class="slider-img ken-burns" 
                    loading="lazy"
                />
                
                <!-- Overlay Gradiente Editorial -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-slate-900/30"></div>

                <!-- Tag flotante con botón para expandir modal -->
                <div class="absolute top-6 right-6 z-10">
                    <button 
                        @click="openDetailModal()"
                        type="button" 
                        class="glass-panel text-slate-800 text-xs px-4 py-2 rounded-full font-medium tracking-wider uppercase inline-flex items-center gap-2 hover:bg-white transition-all shadow-luxury"
                    >
                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        <span>Alta Resolución</span>
                    </button>
                </div>

                <!-- Etiqueta técnica sobrepuesta -->
                <div class="absolute bottom-6 left-6 right-6 z-10 flex flex-col md:flex-row md:items-end justify-between gap-4 text-white">
                    <div>
                        <span class="editorial-overline text-sky-200" x-text="slide.category"></span>
                        <h3 class="font-display text-2xl md:text-4xl font-normal mt-1" x-text="slide.title"></h3>
                        <p class="text-sm text-slate-300 mt-1 max-w-xl font-light" x-text="slide.description"></p>
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs tracking-widest uppercase font-mono text-slate-300 bg-white/10 backdrop-blur-md px-3 py-1.5 rounded border border-white/15" x-text="slide.specs"></span>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Controles Inferiores y Contador Numérico 01 / 08 -->
    <div class="slider-controls">
        <div class="slider-counter">
            <strong x-text="formatNum(current)">01</strong>
            <span class="mx-1.5 text-slate-400">/</span>
            <span x-text="formatNum(slides.length - 1)">08</span>
            <span class="ml-4 text-xs tracking-widest uppercase text-slate-500 hidden sm:inline-block" x-text="slides[current].location"></span>
        </div>

        <div class="slider-arrows">
            <button 
                @click="prev()" 
                type="button" 
                class="arrow-btn" 
                aria-label="Diapositiva anterior"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>

            <button 
                @click="next()" 
                type="button" 
                class="arrow-btn" 
                aria-label="Siguiente diapositiva"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </div>
</div>

