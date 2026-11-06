@extends('layouts.app')

@section('title', 'Clínica Vida Salud — Complejo Hospitalario & Cirugía Robótica de Alta Complejidad')

@section('content')
<div x-data="portada">

    <!-- 2. HERO SECTION (Fondo Arquitectónico Clínico de Alta Definición) -->
    <section id="hero" class="relative min-h-screen w-full overflow-hidden flex items-center justify-center bg-slate-900">
        <!-- Imagen Principal de Fondo (Hero) -->
        <div class="absolute inset-0 bg-cover bg-center transition-all duration-1000 ease-out"
             style="background-image: url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1920&q=85');">
            <div class="absolute inset-0 bg-gradient-to-b from-slate-950/70 via-slate-900/60 to-slate-950/85"></div>
        </div>

        <!-- Night Layer (Transición suave al hacer scroll) -->
        <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-700 pointer-events-none"
             style="background-image: url('https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?auto=format&fit=crop&w=1920&q=85');"
             :style="`opacity: ${noche}`">
            <div class="absolute inset-0 bg-slate-950/70"></div>
        </div>

        <!-- Hero Content -->
        <div class="relative z-10 text-center px-6 max-w-5xl pt-16">
            <div class="inline-flex items-center space-x-2.5 px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md border border-white/25 mb-8">
                <span class="w-2 h-2 rounded-full transition-colors duration-500"
                      :class="noche > 0.5 ? 'bg-emerald-400 animate-pulse' : 'bg-[#38BDF8]'"></span>
                <span class="text-[11px] uppercase tracking-[0.2em] text-white font-semibold"
                      x-text="noche > 0.5 ? 'Guardia Médica y Pabellones de Urgencia 24/7' : 'Atención Integral & Cirugía Robótica de Precisión'">
                    Atención Integral & Cirugía Robótica de Precisión
                </span>
            </div>

            <h1 class="font-serif text-4xl sm:text-6xl md:text-7xl lg:text-8xl tracking-tight leading-[1.08] mb-8 text-white">
                LA SALUD EN <br />
                <span class="italic font-light text-[#38BDF8]">buenas manos.</span>
            </h1>

            <p class="max-w-2xl mx-auto text-slate-200 text-sm sm:text-base md:text-lg mb-10 leading-relaxed font-normal">
                Clínica Vida Salud integra infraestructura hospitalaria de vanguardia, medicina de alta complejidad y un sistema clínico conectado en tiempo real para el cuidado de cada paciente.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="#projeto"
                   class="w-full sm:w-auto px-8 py-4 bg-[#0284C7] text-white font-semibold text-xs uppercase tracking-widest rounded-xl hover:bg-[#0369A1] transition-all duration-300 shadow-lg shadow-[#0284C7]/30">
                    Conocer el Complejo
                </a>
                <button type="button"
                        @click="$dispatch('abrir-reserva')"
                        class="w-full sm:w-auto px-8 py-4 bg-white/15 backdrop-blur-md border border-white/30 text-white font-semibold text-xs uppercase tracking-widest rounded-xl hover:bg-white/25 transition-all duration-300 flex items-center justify-center space-x-2">
                    <x-icon name="calendar" class="w-4 h-4 text-[#38BDF8]" />
                    <span>Agendar Cita en Línea</span>
                </button>
            </div>
        </div>

        <!-- Bottom Status Bar -->
        <div class="absolute bottom-8 left-6 right-6 md:left-12 md:right-12 flex flex-col sm:flex-row justify-between items-center text-xs text-white/80 z-10 border-t border-white/15 pt-4 gap-2">
            <div class="flex items-center space-x-2">
                <x-icon name="heart-pulse" class="w-4 h-4 text-emerald-400" />
                <span>Guardia Hospitalaria 24 Horas | Triage Promedio: &lt; 4 min</span>
            </div>
            <div class="hidden sm:flex items-center space-x-6">
                <span>Deslizar para explorar instalaciones</span>
            </div>
        </div>
    </section>

    <!-- 3. PROJECT INTRO SECTION (Clinical Value & Bio-Architecture) -->
    <section id="projeto" class="py-24 md:py-32 px-6 md:px-12 max-w-7xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
            <div class="lg:col-span-6">
                <span class="text-[#0284C7] text-xs font-bold uppercase tracking-[0.2em] block mb-3">
                    01 / EL PROYECTO HOSPITALARIO
                </span>
                <h2 class="font-serif text-3xl sm:text-5xl text-[#0F172A] leading-[1.15] mb-6">
                    LA MEDICINA EN <br />
                    vanguardia constante <br />
                    <span class="italic font-light text-[#0284C7]">y calidez humana.</span>
                </h2>
                <div class="h-1 w-20 bg-[#0284C7] rounded-full mb-8"></div>

                <div class="grid grid-cols-3 gap-4 pt-4">
                    <div class="border-l-2 border-[#0284C7] pl-4">
                        <span class="text-2xl md:text-3xl font-bold text-[#0F172A] block">45.000 m²</span>
                        <span class="text-xs text-slate-500">Superficie Clínica</span>
                    </div>
                    <div class="border-l-2 border-emerald-500 pl-4">
                        <span class="text-2xl md:text-3xl font-bold text-[#0F172A] block">180 Camas</span>
                        <span class="text-xs text-slate-500">Suites y UCI</span>
                    </div>
                    <div class="border-l-2 border-sky-400 pl-4">
                        <span class="text-2xl md:text-3xl font-bold text-[#0F172A] block">100%</span>
                        <span class="text-xs text-slate-500">Acreditación JCI</span>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-6 space-y-6 text-slate-600 text-base leading-relaxed">
                <!-- Imagen Destacada del Complejo Hospitalario -->
                <div class="relative rounded-2xl overflow-hidden shadow-xl border border-slate-200/90 aspect-[16/10] group">
                    <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1200&q=85"
                         alt="Instalaciones de Vanguardia Clínica Vida Salud"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></div>
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white text-xs">
                        <span class="font-semibold bg-[#0284C7]/90 px-3 py-1 rounded-lg backdrop-blur-sm">Arquitectura Hospitalaria Sostenible</span>
                        <span class="text-slate-200 text-[11px]">Santa Cruz, Bolivia</span>
                    </div>
                </div>

                <p class="text-[#0F172A] text-lg font-medium leading-relaxed">
                    Diseñado bajo estándares internacionales de arquitectura hospitalaria saludable, el complejo combina tecnología diagnóstica de última generación con espacios luminosos y jardines terapéuticos que aceleran la recuperación del paciente.
                </p>
                <p>
                    Cada nivel ha sido planificado con circulaciones diferenciadas para pacientes ambulatorios, hospitalización privada y flujos críticos de urgencia, garantizando privacidad, bioseguridad absoluta y eficiencia médica en cada intervención.
                </p>
                <div class="pt-2 flex items-center space-x-4 text-xs font-semibold text-[#0284C7]">
                    <x-icon name="shield-check" class="w-5 h-5 shrink-0" />
                    <span>Consorcio Médico de Alta Complejidad — Certificación Internacional ISO 9001 &amp; JCI</span>
                </div>
            </div>
        </div>
    </section>

    <!-- 4. PHOTO JOURNEY SECTION (8 Clinical Areas Carousel) -->
    <section id="ambientes"
             class="py-20 bg-slate-100/80 border-y border-slate-200/80"
             x-data="recorrido(@js($areas))">
        <div class="max-w-7xl mx-auto px-6 md:px-12">

            <!-- Section Header -->
            <div class="flex flex-col md:flex-row md:items-end justify-between mb-12">
                <div>
                    <span class="text-[#0284C7] text-xs font-bold uppercase tracking-[0.2em] block mb-2">
                        02 / RECORRIDO POR LAS INSTALACIONES
                    </span>
                    <h3 class="font-serif text-3xl md:text-4xl text-[#0F172A]">
                        Infraestructura Clínica de Nivel Mundial
                    </h3>
                </div>
                <p class="text-slate-500 text-xs md:text-sm max-w-md mt-3 md:mt-0">
                    Explore nuestras 8 unidades especializadas. Las imágenes se adaptan sutilmente entre modo diurno y guardia nocturna a medida que recorre la página.
                </p>
            </div>

            <!-- Main Carousel Frame -->
            <div class="relative rounded-2xl overflow-hidden bg-slate-900 shadow-2xl border border-slate-200/80 aspect-[16/10] md:aspect-[16/9] min-h-[420px]">

                <!-- Day Image -->
                <img :src="actual.imageDay"
                     :alt="actual.title"
                     class="w-full h-full object-cover transition-all duration-700" />

                <!-- Night Image Overlay (scroll reactive) -->
                <img :src="actual.imageNight"
                     :alt="actual.title + ' - Guardia Nocturna'"
                     class="absolute inset-0 w-full h-full object-cover transition-opacity duration-700 pointer-events-none"
                     :style="`opacity: ${Math.min(0.85, (noche || 0) * 0.85)}`" />

                <!-- Gradient Scrim for Text Readability -->
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/35 to-transparent"></div>

                <!-- Top Bar inside Frame -->
                <div class="absolute top-5 left-5 right-5 md:top-8 md:left-8 md:right-8 flex justify-between items-center z-10">
                    <div class="flex items-center space-x-2">
                        <span class="px-3 py-1 rounded-lg bg-slate-900/75 backdrop-blur-md text-white text-xs font-bold border border-white/15"
                              x-text="`${actual.id} / 08`">
                            01 / 08
                        </span>
                        <span class="hidden sm:inline-block px-3 py-1 rounded-lg bg-[#0284C7]/90 backdrop-blur-md text-white text-xs font-semibold"
                              x-text="actual.subtitle">
                        </span>
                    </div>

                    <button type="button"
                            @click="fichaAbierta = true"
                            class="flex items-center space-x-2 px-4 py-2 rounded-xl bg-white/95 hover:bg-white text-[#0F172A] text-xs font-semibold shadow-lg transition-all">
                        <x-icon name="maximize" class="w-3.5 h-3.5 text-[#0284C7]" />
                        <span>Ficha Técnica &amp; Equipamiento</span>
                    </button>
                </div>

                <!-- Bottom Info Caption -->
                <div class="absolute bottom-6 left-6 right-6 md:bottom-10 md:left-10 md:right-10 flex flex-col md:flex-row md:items-end justify-between gap-6 z-10">
                    <div class="max-w-2xl">
                        <span class="text-[#38BDF8] text-[11px] font-bold uppercase tracking-[0.2em] block mb-2"
                              x-text="actual.tag">
                        </span>
                        <h4 class="font-serif text-2xl sm:text-3xl md:text-4xl text-white mb-3 leading-tight"
                            x-text="actual.title">
                        </h4>
                        <p class="text-slate-200 text-xs sm:text-sm leading-relaxed"
                           x-text="actual.description">
                        </p>
                    </div>

                    <!-- Operational Status Pill -->
                    <div class="flex flex-col items-start md:items-end shrink-0">
                        <span class="text-[10px] uppercase tracking-wider text-slate-300 mb-1">Estado de la Unidad</span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold border"
                              :class="{
                                  'bg-emerald-50 text-emerald-700 border-emerald-200': actual.specs.statusType === 'stable',
                                  'bg-red-50 text-red-700 border-red-200': actual.specs.statusType === 'critical',
                                  'bg-amber-50 text-amber-700 border-amber-200': actual.specs.statusType === 'caution',
                                  'bg-sky-50 text-sky-700 border-sky-200': actual.specs.statusType === 'info'
                              }"
                              x-text="actual.specs.status">
                        </span>
                    </div>
                </div>
            </div>

            <!-- Controls & Quick Thumbnail Strip -->
            <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                <!-- Progress Indicators -->
                <div class="flex items-center space-x-2">
                    <template x-for="(area, idx) in areas" :key="area.id">
                        <button type="button"
                                @click="ir(idx)"
                                class="h-2 rounded-full transition-all duration-300"
                                :class="idx === indice ? 'w-10 bg-[#0284C7]' : 'w-2.5 bg-slate-300 hover:bg-slate-400'"
                                :aria-label="`Ver ${area.title}`">
                        </button>
                    </template>
                </div>

                <!-- Prev / Next Buttons -->
                <div class="flex items-center space-x-3">
                    <button type="button"
                            @click="anterior()"
                            class="w-11 h-11 rounded-xl border border-slate-300 bg-white flex items-center justify-center text-slate-700 hover:bg-[#0284C7] hover:text-white hover:border-[#0284C7] transition-all shadow-sm"
                            aria-label="Anterior instalación">
                        <x-icon name="chevron-left" class="w-5 h-5" />
                    </button>
                    <button type="button"
                            @click="siguiente()"
                            class="w-11 h-11 rounded-xl border border-slate-300 bg-white flex items-center justify-center text-slate-700 hover:bg-[#0284C7] hover:text-white hover:border-[#0284C7] transition-all shadow-sm"
                            aria-label="Siguiente instalación">
                        <x-icon name="chevron-right" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <!-- 8 Area Quick Selector Pills -->
            <div class="mt-8 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
                <template x-for="(item, index) in areas" :key="'thumb-' + item.id">
                    <button type="button"
                            @click="ir(index)"
                            class="text-left p-2.5 rounded-xl border transition-all"
                            :class="index === indice
                                ? 'bg-white border-[#0284C7] shadow-md ring-2 ring-[#0284C7]/15'
                                : 'bg-white/60 border-slate-200/80 hover:bg-white'">
                        <div class="aspect-video rounded-lg overflow-hidden mb-2 bg-slate-200">
                            <img :src="item.imageDay" :alt="item.title" class="w-full h-full object-cover" />
                        </div>
                        <span class="text-[10px] font-bold text-[#0284C7] block" x-text="item.id"></span>
                        <p class="text-[11px] font-semibold text-slate-800 truncate"
                           x-text="item.tag.split('/')[1]?.trim() || item.title"></p>
                    </button>
                </template>
            </div>
        </div>

        <!-- TECHNICAL MODAL FOR THE 8 CLINICAL AREAS -->
        <div x-show="fichaAbierta"
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm">
            <div class="bg-white border border-slate-200 max-w-2xl w-full rounded-2xl p-6 md:p-8 relative shadow-2xl"
                 @click.outside="fichaAbierta = false">
                <button type="button"
                        @click="fichaAbierta = false"
                        class="absolute top-5 right-5 text-slate-400 hover:text-slate-700 p-2 rounded-full hover:bg-slate-100 transition-colors">
                    <x-icon name="x" class="w-5 h-5" />
                </button>

                <span class="text-[#0284C7] text-xs font-bold uppercase tracking-widest block mb-1"
                      x-text="`${actual.tag} — ESPECIFICACIONES CLÍNICAS`">
                </span>
                <h4 class="font-serif text-2xl md:text-3xl text-[#0F172A] mb-2"
                    x-text="actual.title">
                </h4>
                <p class="text-slate-500 text-sm mb-6"
                   x-text="actual.description">
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-5 rounded-xl border border-slate-200/80 mb-6">
                    <div>
                        <span class="text-[11px] font-bold uppercase text-slate-400 block mb-1">Capacidad Instalada</span>
                        <span class="text-sm font-semibold text-slate-800" x-text="actual.specs.capacity"></span>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold uppercase text-slate-400 block mb-1">Certificaciones &amp; Bioseguridad</span>
                        <span class="text-sm font-semibold text-slate-800" x-text="actual.specs.certifications"></span>
                    </div>
                    <div class="sm:col-span-2 pt-2 border-t border-slate-200/70">
                        <span class="text-[11px] font-bold uppercase text-slate-400 block mb-1">Tecnología Biomédica Principal</span>
                        <span class="text-sm font-semibold text-[#0284C7]" x-text="actual.specs.equipment"></span>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold border"
                          :class="{
                              'bg-emerald-50 text-emerald-700 border-emerald-200': actual.specs.statusType === 'stable',
                              'bg-red-50 text-red-700 border-red-200': actual.specs.statusType === 'critical',
                              'bg-amber-50 text-amber-700 border-amber-200': actual.specs.statusType === 'caution',
                              'bg-sky-50 text-sky-700 border-sky-200': actual.specs.statusType === 'info'
                          }"
                          x-text="actual.specs.status">
                    </span>
                    <div class="flex space-x-3 w-full sm:w-auto justify-end">
                        <button type="button"
                                @click="fichaAbierta = false"
                                class="px-5 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50">
                            Cerrar
                        </button>
                        <a href="{{ route('portal') }}"
                           class="px-5 py-2.5 rounded-xl bg-[#0284C7] text-white text-xs font-semibold hover:bg-[#0369A1]">
                            Ver Estado en Portal Clínico
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. MEDICAL SPECIALTIES & FAST SCHEDULING -->
    <section id="especialidades" class="py-24 px-6 md:px-12 max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-14">
            <div>
                <span class="text-[#0284C7] text-xs font-bold uppercase tracking-[0.2em] block mb-2">
                    03 / ESPECIALIDADES Y ACCESO RÁPIDO
                </span>
                <h3 class="font-serif text-3xl md:text-4xl text-[#0F172A]">
                    Excelencia médica en cada especialidad
                </h3>
            </div>
            <button type="button"
                    @click="$dispatch('abrir-reserva')"
                    class="mt-4 md:mt-0 inline-flex items-center space-x-2 bg-[#0284C7] hover:bg-[#0369A1] text-white px-5 py-3 rounded-xl text-xs font-semibold transition-all shadow-md shadow-[#0284C7]/20">
                <x-icon name="calendar" class="w-4 h-4" />
                <span>Agendar Consulta Médica</span>
            </button>
        </div>

        <!-- 3 Centros de Alta Complejidad con Imágenes Clínicas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @php
                $centrosDestacados = [
                    [
                        'title' => 'Cardiología & Hemodinamia',
                        'desc' => 'Angioplastia primaria 24/7, electrofisiología cardíaca y cirugía cardiovascular mínimamente invasiva.',
                        'doctors' => '18 Especialistas',
                        'wait' => 'Atención inmediata',
                        'image' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Neurocirugía & Stroke Center',
                        'desc' => 'Neuronavegación óptica, trombectomía mecánica de urgencia y monitoreo neurofisiológico intraoperatorio.',
                        'doctors' => '14 Especialistas',
                        'wait' => 'Protocolo ACV Activo',
                        'image' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=800&q=80',
                    ],
                    [
                        'title' => 'Oncología & Radioterapia',
                        'desc' => 'Comité oncológico multidisciplinario, inmunoterapia personalizada y acelerador lineal de alta precisión.',
                        'doctors' => '22 Especialistas',
                        'wait' => 'Agenda preferente',
                        'image' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=800&q=80',
                    ],
                ];
            @endphp
            @foreach ($centrosDestacados as $centro)
                <div class="bg-white rounded-2xl overflow-hidden border border-slate-200/90 shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col justify-between group">
                    <div class="relative h-44 overflow-hidden bg-slate-100">
                        <img src="{{ $centro['image'] }}" alt="{{ $centro['title'] }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-slate-950/20 to-transparent"></div>
                        <div class="absolute top-3 right-3 bg-white/90 backdrop-blur-md px-2.5 py-1 rounded-lg text-[10px] font-bold text-slate-800">
                            {{ $centro['doctors'] }}
                        </div>
                        <div class="absolute bottom-3 left-4 right-4">
                            <span class="text-white text-xs font-semibold px-2 py-0.5 rounded bg-[#0284C7]/90 inline-block">Centro de Excelencia</span>
                        </div>
                    </div>
                    <div class="p-6 flex-1 flex flex-col justify-between">
                        <div>
                            <h4 class="font-bold text-lg text-[#0F172A] mb-2">{{ $centro['title'] }}</h4>
                            <p class="text-slate-600 text-sm leading-relaxed mb-6">{{ $centro['desc'] }}</p>
                        </div>
                        <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700">Guardia activa</span>
                            <button type="button"
                                    @click="$dispatch('abrir-reserva')"
                                    class="text-[#0284C7] font-semibold bg-sky-50 hover:bg-[#0284C7] hover:text-white px-3 py-1.5 rounded-lg transition-colors">
                                {{ $centro['wait'] }} →
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Catálogo Real de Especialidades en PostgreSQL (CU04 & CU10) -->
        @if ($especialidades->isNotEmpty())
            <div class="mt-10 pt-8 border-t border-slate-200/80">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-5">
                    <div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-[#0284C7]">Catálogo Oficial Conectado a PostgreSQL (CU04 / CU10)</span>
                        <h4 class="font-bold text-base text-[#0F172A]">Especialidades Activas para Reserva Directa</h4>
                    </div>
                    <a href="{{ url('/disponibilidad') }}" class="text-xs font-semibold text-[#0284C7] hover:underline">
                        Consultar horarios detallados (CU10) →
                    </a>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($especialidades as $esp)
                        <div class="bg-white rounded-xl p-4 border border-slate-200/80 hover:border-[#0284C7] transition-all flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-[#0284C7] bg-sky-50 px-2 py-0.5 rounded">
                                        ESP-{{ str_pad((string) $esp->id_especialidad, 2, '0', STR_PAD_LEFT) }}
                                    </span>
                                    <span class="text-[11px] text-slate-500 font-medium">
                                        {{ $esp->medicos_count ?? 1 }} médico(s)
                                    </span>
                                </div>
                                <h5 class="font-bold text-sm text-[#0F172A] mb-1">{{ $esp->nombre_especialidad }}</h5>
                                <p class="text-xs text-slate-500 line-clamp-2 mb-3">
                                    {{ $esp->descripcion ?: 'Atención médica especializada con agenda digital integrada.' }}
                                </p>
                            </div>
                            <button type="button"
                                    @click="$dispatch('abrir-reserva', { idEspecialidad: '{{ $esp->id_especialidad }}' })"
                                    class="w-full py-2 rounded-lg bg-slate-50 hover:bg-[#0284C7] hover:text-white text-[#0284C7] text-xs font-semibold transition-colors border border-slate-200/70">
                                Reservar en {{ $esp->nombre_especialidad }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Callout Banner hacia Acceso Institucional -->
        <div class="mt-12 rounded-2xl bg-gradient-to-r from-[#0F172A] to-[#1E293B] p-8 md:p-10 text-white flex flex-col lg:flex-row items-center justify-between gap-8 shadow-xl">
            <div class="max-w-2xl">
                <span class="text-[#38BDF8] text-xs font-bold uppercase tracking-widest block mb-2">
                    ACCESO AL SISTEMA HOSPITALARIO
                </span>
                <h4 class="font-serif text-2xl md:text-3xl mb-2">
                    Portal Clínico &amp; Servicios en Línea
                </h4>
                <p class="text-slate-300 text-sm leading-relaxed">
                    Si eres paciente, médico o personal asistencial de Clínica Vida Salud, ingresa con tus credenciales institucionales para gestionar consultas, turnos y registros clínicos.
                </p>
            </div>
            <div class="flex flex-wrap gap-3 shrink-0">
                <a href="{{ route('login') }}"
                   class="px-6 py-3.5 bg-[#0284C7] hover:bg-[#0369A1] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-lg flex items-center space-x-2">
                    <x-icon name="log-in" class="w-4 h-4" />
                    <span>Iniciar Sesión (CU01)</span>
                </a>
                <a href="{{ url('/registro-paciente') }}"
                   class="px-6 py-3.5 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    Registrarse como Paciente
                </a>
            </div>
        </div>
    </section>

    <!-- 6. INSTITUTIONAL FOOTER -->
    <footer class="bg-[#1E293B] text-slate-300 py-16 px-6 md:px-12 border-t border-slate-800">
        <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-10 mb-12">
            <div>
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-[#0284C7] flex items-center justify-center text-white">
                        <x-icon name="compass" class="w-4 h-4" />
                    </div>
                    <span class="font-bold text-white tracking-tight">CLÍNICA VIDA SALUD</span>
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Institución médica de alta complejidad comprometida con la seguridad del paciente, la innovación quirúrgica y la excelencia asistencial.
                </p>
            </div>

            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-white mb-4">Acceso Rápido</h5>
                <ul class="space-y-2.5 text-xs text-slate-400">
                    <li><a href="{{ route('portal', ['rol' => 'medico']) }}" class="hover:text-white transition-colors">Portal Médico Especialista (CU15/16)</a></li>
                    <li><a href="{{ route('portal', ['rol' => 'enfermeria']) }}" class="hover:text-white transition-colors">Consola Enfermería y Recepción (CU14)</a></li>
                    <li><a href="{{ route('portal', ['rol' => 'paciente']) }}" class="hover:text-white transition-colors">Portal del Paciente y Familia (CU08/18)</a></li>
                    <li><a href="{{ url('/citas') }}" class="hover:text-white transition-colors">Programación de Citas Médicas (CU11)</a></li>
                    <li><a href="{{ url('/reportes-administrativos') }}" class="hover:text-white transition-colors">Reportes e Indicadores (CU19)</a></li>
                </ul>
            </div>

            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-white mb-4">Central de Atención 24/7</h5>
                <ul class="space-y-2.5 text-xs text-slate-400">
                    <li class="flex items-center space-x-2">
                        <x-icon name="phone-call" class="w-3.5 h-3.5 text-[#38BDF8]" />
                        <span>Urgencias y Rescate: 800 910 247</span>
                    </li>
                    <li>Mesa Central: +591 (3) 345 9000</li>
                    <li>contacto@clinicavidasalud.bo</li>
                </ul>
            </div>

            <div>
                <h5 class="text-xs font-bold uppercase tracking-wider text-white mb-4">Ubicación y Helipuerto</h5>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Av. San Martín 1480, Equipetrol Norte<br />
                    Santa Cruz de la Sierra, Bolivia<br />
                    <span class="text-emerald-400 font-medium mt-1 inline-block">Helipuerto Médico Habilitado OACI</span>
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto pt-8 border-t border-slate-800 flex flex-col sm:flex-row justify-between items-center text-xs text-slate-500">
            <p>© 2026 Clínica Vida Salud — Todos los derechos reservados.</p>
            <p class="mt-2 sm:mt-0">Estándar de Seguridad Clínica HIPAA / ISO 27799 · Laravel 13 &amp; PostgreSQL</p>
        </div>
    </footer>
</div>
@endsection
