@extends('layouts.app')

@section('title', 'Clínica Vida Salud — Excelencia Médica & Hospitalidad Terapéutica')

@section('content')

@php
    $heroSlides = [
        [
            'title' => 'Pabellón Quirúrgico Robótico Integrado',
            'category' => 'Cirugía de Alta Precisión',
            'image' => 'https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Flujo Laminar ISO 5 · Sistema Da Vinci Xi',
            'description' => 'Quirófano inteligente equipado con telemetría en tiempo real y asistencia microquirúrgica guiada por fluorescencia.',
            'location' => 'Nivel 02 · Ala Quirúrgica Norte',
        ],
        [
            'title' => 'Suites Terapéuticas con Jardín Interior',
            'category' => 'Hospitalización Premium',
            'image' => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Control Circadiano · Aislamiento 48dB',
            'description' => 'Habitaciones privadas que integran luz natural biofílica para acelerar los tiempos fisiológicos de recuperación.',
            'location' => 'Nivel 04 · Pabellón de Reposo',
        ],
        [
            'title' => 'Unidad de Resonancia Magnética 3.0 Tesla',
            'category' => 'Diagnóstico Avanzado',
            'image' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Túnel Ultra-Ancho · Audio Ambient',
            'description' => 'Adquisición espectrométrica no invasiva con resolución submilenaria para estudios neurológicos y cardiológicos.',
            'location' => 'Nivel 01 · Centro de Imágenes',
        ],
        [
            'title' => 'Atrio Central y Jardines de Sanación',
            'category' => 'Arquitectura Sanitaria',
            'image' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Ventilación Natural Cruzada',
            'description' => 'Espacios concebidos para mitigar los estresores hospitalarios tradicionales mediante vegetación nativa y luz cenital.',
            'location' => 'Planta Baja · Área Social',
        ],
        [
            'title' => 'Centro de Monitoreo Hemodinámico 24/7',
            'category' => 'Cuidados Intensivos',
            'image' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Ratio 1:1 · Presión Negativa',
            'description' => 'Plataforma unificada de telemetría cardiovascular con predicción algorítmica de signos vitales críticos.',
            'location' => 'Nivel 03 · Cuidados Críticos',
        ],
        [
            'title' => 'Laboratorio de Genómica y Medicina Molecular',
            'category' => 'Investigación Clínica',
            'image' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Secuenciación NGS de Alta Tasa',
            'description' => 'Biomarcadores tumorales individualizados para terapias oncológicas dirigidas de máxima efectividad.',
            'location' => 'Nivel Subterráneo · Biología Molecular',
        ],
        [
            'title' => 'Módulo de Hidroterapia y Fisioterapia Asistida',
            'category' => 'Rehabilitación Integral',
            'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Piscina de Marcha Inversa',
            'description' => 'Recuperación neuromuscular acelerada en entorno hídrico termorregulado con apoyo cinético continuo.',
            'location' => 'Nivel 01 · Medicina Física',
        ],
        [
            'title' => 'Salón de Consultas y Medicina Preventiva',
            'category' => 'Consulta Externa',
            'image' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1280&q=75',
            'specs' => 'Privacidad Total · Digital',
            'description' => 'Atención médica cercana y personalizada en un ambiente cálido que elimina la frialdad instrumental.',
            'location' => 'Nivel 02 · Consultorios Médicos',
        ]
    ];
@endphp

<!-- =========================================================================
     00. APERTURA E INTRO INMERSIVA (HERO SECTION OPTIMIZADA)
     ========================================================================= -->
<section class="relative min-h-[85vh] lg:min-h-[calc(100vh-4.75rem)] flex flex-col justify-between pt-6 md:pt-10 pb-8 md:pb-12 border-b border-slate-200">
    <!-- Difusor de Iluminación Ligero (GPU Friendly) -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden" aria-hidden="true">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-sky-200/20 rounded-full blur-[60px]"></div>
    </div>

    <!-- Barra de Metadatos Superiores del Hero -->
    <div class="container relative z-10 flex flex-wrap items-center justify-between gap-3 pt-4 border-b border-slate-200 pb-3">
        <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
            <span class="editorial-overline text-[10px] sm:text-xs">HOSPITAL PRIVADO · EXCELENCIA QUIRÚRGICA</span>
            <span class="text-slate-300 hidden sm:inline">·</span>
            <x-status-badge type="stable" label="Operación Continua 24/7" />
        </div>

        <div>
            <a href="#capitulo-01" id="skip-intro-btn" class="btn-minimal group">
                <span>Saltar intro</span>
                <span class="group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform">↗</span>
            </a>
        </div>
    </div>

    <!-- Título Principal Editorial y Revelación -->
    <div class="container relative z-10 my-auto py-8 md:py-16">
        <div class="max-w-5xl">
            <p class="text-xs sm:text-sm font-mono uppercase tracking-[0.2em] text-secondary font-medium mb-3 md:mb-5">
                Capítulo Proemio · El Arte del Cuidado Clínico
            </p>

            <h1 class="font-display text-3xl sm:text-5xl md:text-6xl lg:text-7xl font-light text-slate-900 tracking-tight leading-[1.08] title-reveal">
                <span class="title-reveal-inner">
                    La medicina de <em>precisión</em> encuentra su lugar más sereno.
                </span>
            </h1>

            <p class="mt-5 md:mt-7 text-base sm:text-lg md:text-xl text-slate-600 max-w-2xl font-light leading-relaxed scroll-fade-up">
                Redefinimos la experiencia hospitalaria integrando tecnología quirúrgica de vanguardia, confort biofílico y un modelo de atención centrado en la dignidad humana.
            </p>

            <!-- Acciones Directas del Hero -->
            <div class="mt-8 md:mt-10 flex flex-wrap items-center gap-3 sm:gap-4">
                <a href="#admisiones" class="btn-primary w-full sm:w-auto">
                    <span>Programar Evaluación Médica</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>
                
                <a href="#capitulo-01" class="btn-outline w-full sm:w-auto">
                    <span>Explorar el Complejo</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Indicador de Coordenadas Geográficas / Sanitarias -->
    <div class="container relative z-10 flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-slate-200">
        <div class="flex items-center gap-3">
            <div class="w-7 h-7 rounded-full border border-slate-200 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-slate-500 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                </svg>
            </div>
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-700">Explorar Capítulos</span>
        </div>

        <div class="font-mono text-[11px] sm:text-xs text-slate-400 tracking-wider">
            <span>-17°46'48"S · -63°11'02"W</span>
            <span class="mx-1.5 text-slate-300">·</span>
            <span class="text-slate-600 font-medium">SANTA CRUZ, BOLIVIA</span>
        </div>
    </div>
</section>

<!-- =========================================================================
     CAPÍTULO 01: INFRAESTRUCTURA Y ESPACIOS TERAPÉUTICOS
     ========================================================================= -->
<x-section-chapter 
    id="capitulo-01"
    number="01"
    overline="EL COMPLEJO HOSPITALARIO"
    title="Espacios concebidos para la serenidad y la "
    italic="máxima precisión."
    subtitle="Una arquitectura de luz cenital, materiales nobles y aislamiento acústico que transforma el entorno clínico en un catalizador biológico de bienestar."
>
    <!-- Slider Editorial Interactivo con Contador 01 / 08 -->
    <x-editorial-slider :slides="$heroSlides" />

    <!-- Datos Arquitectónicos Complementarios -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6 mt-8 md:mt-12">
        <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-lg shadow-luxury">
            <span class="editorial-overline text-primary">BIOFILIA ACTIVA</span>
            <h4 class="font-display text-xl sm:text-2xl text-slate-900 mt-2">Luz Circadiana Integrada</h4>
            <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                Graduación cromática sincronizada con el ciclo solar para reducir el estrés del paciente y estimular la producción natural de melatonina.
            </p>
        </div>

        <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-lg shadow-luxury">
            <span class="editorial-overline text-secondary">AIRE ULTRA-PURO</span>
            <h4 class="font-display text-xl sm:text-2xl text-slate-900 mt-2">Presión Positiva & HEPA H14</h4>
            <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                Recambio completo del volumen de aire cada 90 segundos con 99.995% de retención de micropartículas y patógenos aerotransportados.
            </p>
        </div>

        <div class="p-5 sm:p-6 bg-white border border-slate-200 rounded-lg shadow-luxury">
            <span class="editorial-overline text-primary">AISLAMIENTO</span>
            <h4 class="font-display text-xl sm:text-2xl text-slate-900 mt-2">Acústica Silenciosa</h4>
            <p class="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                Paneles fonoabsorbentes y doble tabiquería que garantizan un umbral inferior a 35 decibelios en todas las suites de hospitalización.
            </p>
        </div>
    </div>
</x-section-chapter>

<!-- =========================================================================
     CAPÍTULO 02: UNIDADES DE ALTA COMPLEJIDAD Y CONFORT
     ========================================================================= -->
<x-section-chapter 
    id="capitulo-02"
    number="02"
    overline="UNIDADES CLÍNICAS & SUITES"
    title="La intimidad de una residencia privada, la seguridad de una "
    italic="unidad intensiva."
    subtitle="Equipamiento de última generación integrado de forma invisible en suites diseñadas para acoger a la familia y otorgar confort absoluto durante la estancia médica."
>
    <!-- Métricas Numéricas de Gran Impacto (Display Grande) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-px bg-slate-200 border border-slate-200 rounded-lg overflow-hidden shadow-luxury">
        <div class="metric-box bg-white">
            <div>
                <x-status-badge type="stable" label="Disponibilidad Inmediata" />
                <div class="metric-number mt-3 md:mt-4">32<span> Suites</span></div>
                <div class="metric-label">Hospitalización VIP</div>
            </div>
            <p class="metric-desc">Ambientes privados de 55m² con antesala para acompañantes y balcón botánico.</p>
        </div>

        <div class="metric-box bg-white">
            <div>
                <x-status-badge type="info" label="Quirófanos Activos" />
                <div class="metric-number mt-3 md:mt-4">06<span> Salas</span></div>
                <div class="metric-label">Quirófanos Híbridos</div>
            </div>
            <p class="metric-desc">Integración tomográfica peroperatoria y robótica asistida de alta resolución.</p>
        </div>

        <div class="metric-box bg-white">
            <div>
                <x-status-badge type="accent" label="Protocolo Exclusivo" />
                <div class="metric-number mt-3 md:mt-4">1:1<span> Ratio</span></div>
                <div class="metric-label">Atención Médica</div>
            </div>
            <p class="metric-desc">Asignación de enfermería especializada individual para pacientes de alta complejidad.</p>
        </div>

        <div class="metric-box bg-white">
            <div>
                <x-status-badge type="stable" label="Control Microbiológico" />
                <div class="metric-number mt-3 md:mt-4">0.02%</div>
                <div class="metric-label">Tasa de Infección</div>
            </div>
            <p class="metric-desc">Índice intrahospitalario muy por debajo de los estándares mundiales más exigentes.</p>
        </div>
    </div>

    <!-- Malla de Unidades de Atención -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 mt-8 md:mt-12">
        <article class="editorial-card group">
            <div class="aspect-video overflow-hidden relative">
                <img 
                    src="https://images.unsplash.com/photo-1512678080530-7760d81faba6?auto=format&fit=crop&w=800&q=75" 
                    alt="Suite Médica Privada"
                    loading="lazy"
                    decoding="async"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                />
                <div class="absolute top-4 left-4">
                    <x-status-badge type="stable" label="Suite Presidencial" />
                </div>
            </div>
            <div class="p-6 sm:p-8">
                <span class="editorial-overline text-secondary">SUITE CLÍNICA PREMIUM</span>
                <h3 class="font-display text-2xl sm:text-3xl text-slate-900 mt-2">Alojamiento Terapéutico de Lujo</h3>
                <p class="text-slate-600 mt-2.5 text-xs sm:text-sm leading-relaxed font-light">
                    Mobiliario médico motorizado recubierto en maderas sustentables, domótica táctil para control ambiental, cama king size para acompañante y baño privado en mármol antideslizante.
                </p>
                <div class="mt-5 pt-5 border-t border-slate-100 flex items-center justify-between text-[11px] font-mono text-slate-500">
                    <span>SUPERFICIE: 65 M²</span>
                    <span>BAÑO EN SUITE · CHEF PRIVADO</span>
                </div>
            </div>
        </article>

        <article class="editorial-card group">
            <div class="aspect-video overflow-hidden relative">
                <img 
                    src="https://images.unsplash.com/photo-1538108149393-fbbd81895907?auto=format&fit=crop&w=800&q=75" 
                    alt="Centro de Diagnóstico por Imágenes"
                    loading="lazy"
                    decoding="async"
                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                />
                <div class="absolute top-4 left-4">
                    <x-status-badge type="info" label="Tecnología 2026" />
                </div>
            </div>
            <div class="p-6 sm:p-8">
                <span class="editorial-overline text-primary">IMAGENOLOGÍA AVANZADA</span>
                <h3 class="font-display text-2xl sm:text-3xl text-slate-900 mt-2">Centro de Diagnóstico Molecular</h3>
                <p class="text-slate-600 mt-2.5 text-xs sm:text-sm leading-relaxed font-light">
                    Tomografía computarizada espectral con reducción de radiación del 80%, resonancia magnética funcional y ecografía Doppler con reconstrucción volumétrica en tiempo real.
                </p>
                <div class="mt-5 pt-5 border-t border-slate-100 flex items-center justify-between text-[11px] font-mono text-slate-500">
                    <span>INFORME EN < 60 MIN</span>
                    <span>INTELIGENCIA CLÍNICA AI</span>
                </div>
            </div>
        </article>
    </div>
</x-section-chapter>

<!-- =========================================================================
     CAPÍTULO 03: ESPECIALIDADES MÉDICAS Y ENFOQUE HUMANO
     ========================================================================= -->
<x-section-chapter 
    id="capitulo-03"
    number="03"
    overline="VOCACIÓN MÉDICA & PROTOCOLOS"
    title="Equipos de élite dedicados a cada instante de su "
    italic="recuperación integral."
    subtitle="Un claustro médico conformado por especialistas acreditados internacionalmente, unidos bajo una filosofía de atención cercana, empática y rigurosamente científica."
>
    <!-- Navegación por Tabs Conceptuales Interactivos (Alpine.js) -->
    <div 
        x-data="{
            activeTab: 0,
            tabs: [
                {
                    num: '01',
                    title: 'El Diagnóstico',
                    tag: 'EVALUACIÓN INTEGRAL Y BIOMARCADORES',
                    desc: 'Cada paciente ingresa a través de un panel multidisciplinario que evalúa antecedentes, factores epigenéticos y telemetría no invasiva antes de definir cualquier plan terapéutico.',
                    quote: '«No tratamos diagnósticos aislados; comprendemos la singularidad biológica de cada persona.»',
                    doctor: 'Dr. Alejandro Vaca Díez · Jefe de Medicina Interna',
                    image: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?auto=format&fit=crop&w=700&q=75',
                    checklist: [
                        'Secuenciación genómica y perfiles metabólicos precisos',
                        'Monitoreo hemodinámico inalámbrico y continuo',
                        'Ateneo médico diario de discusión clínica'
                    ]
                },
                {
                    num: '02',
                    title: 'El Procedimiento',
                    tag: 'CIRUGÍA MÍNIMAMENTE INVASIVA Y ROBÓTICA',
                    desc: 'Utilizamos brazos robóticos con capacidad de giro de 540 grados y visualización tridimensional de altísima resolución, reduciendo el sangrado operatorio a niveles mínimos.',
                    quote: '«La precisión micrométrica nos permite preservar el tejido sano y garantizar altas tempranas.»',
                    doctor: 'Dra. Marcela Justiniano · Cirujana Robótica Principal',
                    image: 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?auto=format&fit=crop&w=700&q=75',
                    checklist: [
                        'Abordajes laparoscópicos de incisión única',
                        'Quirófanos con flujo laminar estéril ISO Clase 5',
                        'Seguimiento anestésico por electroencefalograma'
                    ]
                },
                {
                    num: '03',
                    title: 'La Recuperación',
                    tag: 'REHABILITACIÓN EN SUITES BIOFÍLICAS',
                    desc: 'El proceso postoperatorio inicia desde las primeras horas con fisioterapia asistida, nutrición clínica personalizada diseñada por chefs hospitalarios y acompañamiento emocional.',
                    quote: '«El descanso sereno y la nutrición idónea son tan determinantes como el acto quirúrgico.»',
                    doctor: 'Lic. Sofía Ribera · Directora de Cuidados Postoperatorios',
                    image: 'https://images.unsplash.com/photo-1594824813590-b18485223385?auto=format&fit=crop&w=700&q=75',
                    checklist: [
                        'Kinesioterapia en piscinas de hidroterapia térmica',
                        'Nutrición enteral orgánica adaptada a la patología',
                        'Control ambulatorio continuo mediante telemedicina'
                    ]
                }
            ]
        }"
        class="bg-white border border-slate-200 rounded-lg p-5 sm:p-8 md:p-10 shadow-luxury"
    >
        <!-- Selector de Tabs (Desplazable en móviles) -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-3 mb-6 md:mb-8 overflow-x-auto no-scrollbar">
            <template x-for="(tab, index) in tabs" :key="index">
                <button 
                    @click="activeTab = index" 
                    type="button" 
                    class="tab-pill rounded-full"
                    :class="activeTab === index ? 'active' : ''"
                >
                    <span class="font-mono text-xs opacity-60 mr-1.5" x-text="tab.num"></span>
                    <span x-text="tab.title"></span>
                </button>
            </template>
        </div>

        <!-- Contenido Activo del Tab -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-center">
            <div class="lg:col-span-7">
                <span class="editorial-overline text-secondary" x-text="tabs[activeTab].tag"></span>
                <h3 class="font-display text-2xl sm:text-3xl md:text-4xl text-slate-900 mt-2" x-text="tabs[activeTab].title"></h3>
                <p class="text-slate-600 mt-3 text-sm sm:text-base font-light leading-relaxed" x-text="tabs[activeTab].desc"></p>

                <!-- Lista de comprobación de calidad -->
                <ul class="mt-5 space-y-2.5">
                    <template x-for="item in tabs[activeTab].checklist" :key="item">
                        <li class="flex items-center gap-2.5 text-xs sm:text-sm text-slate-700">
                            <span class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <span x-text="item"></span>
                        </li>
                    </template>
                </ul>

                <!-- Testimonio / Cita Médica -->
                <blockquote class="mt-6 p-4 sm:p-5 bg-slate-50 border-l-2 border-primary rounded-r-lg">
                    <p class="font-display text-lg sm:text-xl text-slate-800 italic" x-text="tabs[activeTab].quote"></p>
                    <footer class="mt-2 text-[11px] font-mono uppercase tracking-wider text-slate-500" x-text="tabs[activeTab].doctor"></footer>
                </blockquote>
            </div>

            <!-- Retrato Editorial de Estudio -->
            <div class="lg:col-span-5">
                <div class="aspect-[4/3] sm:aspect-[4/5] rounded-lg overflow-hidden border border-slate-200 relative shadow-luxury">
                    <img 
                        :src="tabs[activeTab].image" 
                        :alt="tabs[activeTab].title"
                        loading="lazy"
                        decoding="async"
                        class="w-full h-full object-cover" 
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
                    <div class="absolute bottom-3 left-3 right-3 text-white">
                        <span class="text-[10px] font-mono tracking-widest uppercase opacity-80">CUADRO PROFESIONAL</span>
                        <div class="font-display text-base sm:text-lg" x-text="tabs[activeTab].doctor"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-section-chapter>

<!-- =========================================================================
     CAPÍTULO 04: EXPLORADOR COMPLETO DE INSTALACIONES (GALLERY EXPLORER)
     ========================================================================= -->
<x-section-chapter 
    id="capitulo-04"
    number="04"
    overline="EXPLORADOR DE INSTALACIONES"
    title="Arquitectura de vanguardia al servicio de la "
    italic="salud y la calma."
    subtitle="Recorra visualmente nuestras áreas clínicas, unidades de imagenología y jardines terapéuticos diseñados con los más rigurosos estándares sanitarios internacionales."
>
    <!-- Galería Filtrable Interactiva (Alpine.js) -->
    <div 
        x-data="{
            activeCategory: 'Todas',
            categories: ['Todas', 'Quirófanos', 'Hospitalización', 'Diagnóstico por Imagen', 'Jardines Terapéuticos'],
            spaces: [
                {
                    title: 'Quirófano Híbrido 01',
                    category: 'Quirófanos',
                    image: 'https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=700&q=75',
                    specs: 'Flujo Laminar Clase 100',
                    location: 'Ala Quirúrgica · Piso 2',
                    description: 'Quirófano con arco en C robotizado para cirugías cardiovasculares complejas.'
                },
                {
                    title: 'Suite Imperial de Recuperación',
                    category: 'Hospitalización',
                    image: 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=700&q=75',
                    specs: 'Vista Panorámica · 60m²',
                    location: 'Torre Médica · Piso 4',
                    description: 'Suite privada con sala de visitas separada y gastronomía clínica personalizada.'
                },
                {
                    title: 'Tomografía Computarizada Espectral',
                    category: 'Diagnóstico por Imagen',
                    image: 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=700&q=75',
                    specs: 'Doble Fuente 256 Cortes',
                    location: 'Centro de Diagnóstico · Nivel 1',
                    description: 'Detección temprana con mínima dosis de contraste y reconstrucción 3D inmediata.'
                },
                {
                    title: 'Jardín Zen Botánico Curativo',
                    category: 'Jardines Terapéuticos',
                    image: 'https://images.unsplash.com/photo-1584467735871-8e85353a8413?auto=format&fit=crop&w=700&q=75',
                    specs: 'Flora Nativa Oxigenante',
                    location: 'Atrio Central · Planta Baja',
                    description: 'Entorno natural accesible con senderos sensoriales para paseos de recuperación.'
                },
                {
                    title: 'Quirófano Robótico Da Vinci',
                    category: 'Quirófanos',
                    image: 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=700&q=75',
                    specs: 'Consola Doble Cirujano',
                    location: 'Ala Quirúrgica · Piso 2',
                    description: 'Tecnología laparoscópica avanzada para procedimientos urológicos y ginecológicos.'
                },
                {
                    title: 'Cuidados Intensivos Coronarios',
                    category: 'Hospitalización',
                    image: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=700&q=75',
                    specs: 'Monitoreo 24/7',
                    location: 'Unidad Coronaria · Piso 3',
                    description: 'Boxes individuales con aislamiento vírico bacteriano y telemetría de última generación.'
                }
            ],
            get filteredSpaces() {
                if (this.activeCategory === 'Todas') {
                    return this.spaces;
                }
                return this.spaces.filter(s => s.category === this.activeCategory);
            },
            openModal(item) {
                window.dispatchEvent(new CustomEvent('open-gallery-modal', { detail: { item } }));
            }
        }"
    >
        <!-- Barra de Filtros (Desplazable en móvil) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 pb-4 mb-6">
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar py-1">
                <template x-for="cat in categories" :key="cat">
                    <button 
                        @click="activeCategory = cat" 
                        type="button" 
                        class="btn-minimal px-3.5 py-1.5 rounded-full border border-slate-200 text-xs shrink-0"
                        :class="activeCategory === cat ? 'bg-primary text-white border-primary shadow-sm' : 'bg-white text-slate-600 hover:border-slate-300'"
                        x-text="cat"
                    >
                    </button>
                </template>
            </div>

            <!-- Contador Dinámico -->
            <div class="text-[11px] font-mono uppercase tracking-widest text-slate-500 shrink-0">
                <span class="font-bold text-slate-800" x-text="filteredSpaces.length"></span>
                <span>/ 18 ESPACIOS</span>
            </div>
        </div>

        <!-- Grilla de Tarjetas -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5 md:gap-6">
            <template x-for="(space, index) in filteredSpaces" :key="space.title">
                <div 
                    @click="openModal(space)" 
                    class="editorial-card cursor-pointer group flex flex-col justify-between"
                >
                    <div class="aspect-[16/10] overflow-hidden relative bg-slate-900">
                        <img 
                            :src="space.image" 
                            :alt="space.title"
                            loading="lazy"
                            decoding="async"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" 
                        />
                        <div class="absolute top-3 left-3">
                            <span class="text-[10px] font-mono uppercase tracking-wider bg-white/90 backdrop-blur-md px-2 py-0.5 rounded text-slate-700 font-semibold" x-text="space.category"></span>
                        </div>
                    </div>

                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <span class="text-[11px] text-slate-400 font-mono" x-text="space.location"></span>
                            <h4 class="font-display text-xl sm:text-2xl text-slate-900 mt-1 group-hover:text-primary transition-colors" x-text="space.title"></h4>
                            <p class="text-xs text-slate-600 mt-1.5 line-clamp-2 font-light" x-text="space.description"></p>
                        </div>

                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                            <span class="font-mono text-[11px] text-slate-500" x-text="space.specs"></span>
                            <span class="text-primary font-semibold flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                                Ver <span>→</span>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-section-chapter>

<!-- =========================================================================
     FOOTER EDITORIAL: ADMISIONES, COORDINACIÓN Y CONTACTO
     ========================================================================= -->
<footer id="admisiones" class="site-footer">
    <div class="container">
        <!-- Bloque Principal de Coordinación Médica -->
        <div class="max-w-4xl pb-10 md:pb-14 border-b border-slate-200">
            <span class="editorial-overline text-secondary">COORDINACIÓN DE ADMISIONES & CITAS</span>
            <h2 class="font-display text-3xl sm:text-5xl md:text-6xl text-slate-900 mt-2 font-normal leading-tight">
                Consulte con el equipo de admisiones médicas.
            </h2>
            <p class="text-slate-600 text-sm sm:text-base md:text-lg mt-3 max-w-2xl font-light">
                Nuestro departamento de coordinación clínica le orientará sobre especialistas, preparación para procedimientos quirúrgicos y coberturas médicas nacionales e internacionales.
            </p>

            <div class="mt-6 md:mt-8 flex flex-wrap items-center gap-3 sm:gap-4">
                <a href="tel:+59133445566" class="btn-primary w-full sm:w-auto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <span>Línea Directa: +591 (3) 344-5566</span>
                </a>

                <a href="mailto:admisiones@clinicavidasalud.com.bo" class="btn-outline w-full sm:w-auto">
                    <span>admisiones@clinicavidasalud.com.bo</span>
                </a>
            </div>
        </div>

        <!-- Directorio y Certificaciones Sanitarias -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 py-8 md:py-10 border-b border-slate-200 text-sm">
            <div>
                <span class="font-display text-lg text-slate-900 font-semibold block mb-2">Clínica Vida Salud</span>
                <p class="text-slate-500 text-xs leading-relaxed font-light">
                    Institución hospitalaria de tercer nivel especializada en cirugía de alta complejidad, medicina cardiovascular y diagnóstico molecular.
                </p>
                <div class="mt-3">
                    <x-status-badge type="stable" label="Acreditación JCI Internacional" />
                </div>
            </div>

            <div>
                <span class="editorial-overline text-slate-800 block mb-2">UBICACIÓN</span>
                <p class="text-slate-600 text-xs leading-relaxed">
                    Avenida San Martín #1840, Zona Equipetrol<br>
                    Santa Cruz de la Sierra, Bolivia
                </p>
                <span class="text-slate-400 font-mono text-[10px] block mt-1">Heliopuerto Clínico H-02</span>
            </div>

            <div>
                <span class="editorial-overline text-slate-800 block mb-2">HORARIOS</span>
                <ul class="space-y-1 text-xs text-slate-600">
                    <li class="flex justify-between">
                        <span>Urgencias & UCI:</span>
                        <span class="font-semibold text-emerald-700 font-mono">24 HORAS</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Consulta Externa:</span>
                        <span>07:00 – 21:00</span>
                    </li>
                    <li class="flex justify-between">
                        <span>Imágenes:</span>
                        <span>06:30 – 22:00</span>
                    </li>
                </ul>
            </div>

            <div>
                <span class="editorial-overline text-slate-800 block mb-2">CERTIFICACIONES</span>
                <p class="text-slate-500 text-xs leading-relaxed">
                    Certificación de Calidad ISO 9001:2015 en Gestión Hospitalaria y Normativa de Bioseguridad Grado Médico Clase A.
                </p>
                <div class="mt-2 text-[10px] font-mono text-slate-400">
                    REGISTRO SEDES: N° 4492-SCZ
                </div>
            </div>
        </div>

        <!-- Pie Legal y Derechos -->
        <div class="pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400 text-center sm:text-left">
            <p>© {{ date('Y') }} Clínica Vida Salud S.A. Todos los derechos reservados.</p>
            <div class="flex flex-wrap items-center justify-center gap-4">
                <a href="#" class="hover:text-slate-600 transition-colors">Privacidad Clínica</a>
                <a href="#" class="hover:text-slate-600 transition-colors">Consentimiento Informado</a>
            </div>
        </div>
    </div>
</footer>

@endsection
