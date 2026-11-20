/**
 * CLÍNICA VIDA SALUD — MOTOR DE INTERACCIÓN LIGERO Y OPTIMIZADO (60FPS)
 * Rendimiento nativo sin secuestro de scroll (Scroll Hijacking)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Manejo del Menú Móvil
    const menuToggleBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    if (menuToggleBtn && mobileMenu) {
        menuToggleBtn.addEventListener('click', () => {
            const isExpanded = menuToggleBtn.getAttribute('aria-expanded') === 'true';
            menuToggleBtn.setAttribute('aria-expanded', !isExpanded);
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('is-open');
        });

        // Cerrar menú al hacer clic en un enlace
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                menuToggleBtn.setAttribute('aria-expanded', 'false');
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('is-open');
            });
        });
    }

    // 2. Control de Preferencias de Movimiento y Accesibilidad
    const motionToggleBtn = document.getElementById('toggle-motion-btn');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let isReducedMotion = localStorage.getItem('clinica_reduced_motion') === 'true' || prefersReducedMotion;

    function applyMotionState(reduced) {
        if (reduced) {
            document.body.classList.add('reduced-motion');
            if (motionToggleBtn) {
                motionToggleBtn.innerHTML = '<span>▶</span> Activar Animaciones';
                motionToggleBtn.setAttribute('aria-pressed', 'true');
            }
        } else {
            document.body.classList.remove('reduced-motion');
            if (motionToggleBtn) {
                motionToggleBtn.innerHTML = '<span>Ⅱ</span> Pausar Animaciones';
                motionToggleBtn.setAttribute('aria-pressed', 'false');
            }
        }
        localStorage.setItem('clinica_reduced_motion', reduced);
    }

    if (motionToggleBtn) {
        motionToggleBtn.addEventListener('click', () => {
            isReducedMotion = !isReducedMotion;
            applyMotionState(isReducedMotion);
        });
    }

    applyMotionState(isReducedMotion);

    // 3. Revelado de Títulos Rápido y No Bloqueante
    const revealElements = document.querySelectorAll('.title-reveal-inner, .scroll-fade-up');
    
    if ('IntersectionObserver' in window && !isReducedMotion) {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -40px 0px',
            threshold: 0.1
        };

        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.transform = 'translateY(0)';
                    entry.target.style.opacity = '1';
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        revealElements.forEach(el => {
            el.style.transform = 'translateY(24px)';
            el.style.opacity = '0';
            el.style.transition = 'transform 0.5s ease-out, opacity 0.5s ease-out';
            revealObserver.observe(el);
        });
    } else {
        revealElements.forEach(el => {
            el.style.transform = 'none';
            el.style.opacity = '1';
        });
    }

    // 4. Saltar Intro
    const skipIntroBtn = document.getElementById('skip-intro-btn');
    if (skipIntroBtn) {
        skipIntroBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetSection = document.getElementById('capitulo-01');
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: 'smooth' });
            }
        });
    }

    // 5. Manejo de alertas cerrables
    document.querySelectorAll('[data-dismiss="alert"]').forEach(btn => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('[role="alert"], [role="status"]');
            if (alert) alert.remove();
        });
    });
});

/**
 * Componentes Alpine de la interfaz (se registran antes de que Alpine arranque).
 */
document.addEventListener('alpine:init', () => {
    const dosDigitos = (n) => String(n).padStart(2, '0');

    // Fecha local (YYYY-MM-DD) sin desfase por zona horaria.
    const hoyLocal = () => {
        const d = new Date();
        return `${d.getFullYear()}-${dosDigitos(d.getMonth() + 1)}-${dosDigitos(d.getDate())}`;
    };

    // Portada: transición suave de fachada de día a noche según el scroll.
    Alpine.data('portada', () => ({
        scrollY: 0,
        init() {
            this.scrollY = window.scrollY;
            window.addEventListener('scroll', () => {
                this.scrollY = window.scrollY;
            }, { passive: true });
        },
        get noche() {
            return Math.min(Math.max((this.scrollY - 80) / 450, 0), 1);
        },
    }));

    // Recorrido por las instalaciones (carrusel + ficha técnica).
    Alpine.data('recorrido', (areas) => ({
        areas: areas || [],
        indice: 0,
        fichaAbierta: false,
        get actual() {
            return (this.areas && this.areas.length > 0) ? this.areas[this.indice] : {
                id: '01',
                tag: '01 / QUIRÓFANOS INTELIGENTES',
                title: 'Cirugía Robótica y Pabellones de Alta Complejidad',
                subtitle: 'Pabellón Quirúrgico Integrado Da Vinci Xi',
                description: 'Instalaciones quirúrgicas de flujo laminar grado 100 con integración digital.',
                imageDay: 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1920&q=80',
                imageNight: 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1920&q=80',
                specs: { capacity: '12 Pabellones Híbridos', equipment: 'Robot Da Vinci Xi', certifications: 'JCI', status: 'Activo 24/7', statusType: 'stable' }
            };
        },
        siguiente() {
            if (this.areas.length > 0) {
                this.indice = (this.indice + 1) % this.areas.length;
            }
        },
        anterior() {
            if (this.areas.length > 0) {
                this.indice = (this.indice - 1 + this.areas.length) % this.areas.length;
            }
        },
        ir(i) {
            this.indice = i;
        },
    }));

    // Reserva de citas: consulta disponibilidad real (CU10) y envía CU11.
    Alpine.data('reserva', (cfg) => ({
        abierto: false,
        especialidad: '',
        idEspecialidad: '',
        idMedico: '',
        fecha: '',
        hoy: hoyLocal(),
        cargando: false,
        buscado: false,
        oferta: [],
        error: '',
        medico: null,
        hora: '',
        duracion: 30,
        motivo: '',

        abrir(idEspecialidad = null) {
            this.reiniciarSeleccion();
            if (idEspecialidad) {
                this.especialidad = String(idEspecialidad);
                this.idEspecialidad = String(idEspecialidad);
            }
            if (!this.fecha) {
                this.fecha = this.hoy;
            }
            this.abierto = true;
            document.body.style.overflow = 'hidden';
            if (this.especialidad && this.fecha) {
                this.buscar();
            }
        },

        cerrar() {
            this.abierto = false;
            document.body.style.overflow = '';
        },

        reiniciarSeleccion() {
            this.medico = null;
            this.idMedico = '';
            this.hora = '';
            this.duracion = 30;
        },

        consultar() {
            return this.buscar();
        },

        async buscar() {
            this.error = '';
            this.buscado = false;
            this.oferta = [];
            this.reiniciarSeleccion();

            const espId = this.idEspecialidad || this.especialidad;
            if (!espId || !this.fecha) {
                return;
            }

            this.cargando = true;
            try {
                const params = new URLSearchParams({ id_especialidad: espId, fecha: this.fecha });
                const respuesta = await fetch(`${cfg.urlDisponibilidad}?${params}`, {
                    headers: { Accept: 'application/json' },
                });
                const json = await respuesta.json();

                if (!respuesta.ok) {
                    const primero = json.errors ? Object.values(json.errors).flat()[0] : null;
                    this.error = primero || json.message || 'No fue posible consultar la disponibilidad.';
                } else {
                    this.oferta = json.data || [];
                }
                this.buscado = true;
            } catch (e) {
                this.error = 'No hay conexión con el servidor. Inténtalo nuevamente.';
            } finally {
                this.cargando = false;
            }
        },

        elegir(bloque, cupo) {
            if (typeof cupo === 'object' && cupo !== null && cupo.estado && cupo.estado !== 'disponible') {
                return;
            }
            this.medico = bloque.medico || bloque;
            this.idMedico = String(bloque.medico ? bloque.medico.id_medico : (bloque.id_medico || ''));
            const horaStr = typeof cupo === 'string' ? cupo : (cupo.hora_inicio || '');
            this.hora = horaStr.substring(0, 5);
            this.duracion = (cupo && cupo.duracion_minutos) ? cupo.duracion_minutos : 30;
        },

        elegirCupo(bloque, cupo) {
            return this.elegir(bloque, cupo);
        },

        esElegido(bloque, cupo) {
            const bId = bloque.medico ? bloque.medico.id_medico : bloque.id_medico;
            const horaStr = typeof cupo === 'string' ? cupo : cupo.hora_inicio;
            return this.idMedico == bId && this.hora === horaStr.substring(0, 5);
        },
    }));

    // Portal: filtro y búsqueda del listado de citas sin recargar la página.
    Alpine.data('listadoCitas', () => ({
        busqueda: '',
        estado: 'todos',
        visible(texto, estado) {
            const coincideTexto = texto.toLowerCase().includes(this.busqueda.trim().toLowerCase());
            const coincideEstado = this.estado === 'todos' || String(estado) === this.estado;
            return coincideTexto && coincideEstado;
        },
    }));

    // Portal médico: modal para registrar la atención de una cita (CU15).
    Alpine.data('atencion', (urlBase) => ({
        abierto: false,
        cita: null,
        accion: '',
        abrir(cita) {
            this.cita = cita;
            this.accion = `${urlBase}/${cita.id}/atender`;
            this.abierto = true;
        },
        cerrar() {
            this.abierto = false;
        },
    }));

    // Paquete 8 / CU20: Asistente Virtual Clínico con IA (página dedicada y widget flotante).
    Alpine.data('chatbotClinica', () => ({
        abierto: false,
        cargando: false,
        estadoSincronizado: false,
        entrada: '',
        error: '',
        endpointMensaje: '/asistente-ia/mensaje',
        endpointEstado: '/asistente-ia/estado',
        storageKey: 'clinica_chatbot_historial',
        motor: {
            configurado: false,
            proveedor: 'asistente_clinico_local',
            modelo: 'Base de Conocimiento Clínica',
            modo: 'local_fallback',
        },
        mensajes: [],

        init() {
            const ds = this.$el?.dataset || {};
            if (ds.endpointMensaje) this.endpointMensaje = ds.endpointMensaje;
            if (ds.endpointEstado) this.endpointEstado = ds.endpointEstado;
            if (ds.proveedor) {
                this.motor = {
                    configurado: ds.configurado === '1',
                    proveedor: ds.proveedor,
                    modelo: ds.modelo || 'Base de Conocimiento Clínica',
                    modo: ds.modo || 'local_fallback',
                };
                this.estadoSincronizado = true;
            }

            this.cargarHistorial();
            if (ds.sincronizarAlIniciar === '1') {
                this.consultarEstado();
            }
            this.$nextTick(() => this.scrollAlFinal());
        },

        mensajeBienvenida() {
            return {
                id: 'bienvenida',
                rol: 'assistant',
                contenido: 'Hola, soy el Asistente Virtual de Clínica Vida Salud. Puedo orientarte sobre especialidades médicas, médicos y horarios de atención, cómo agendar, reprogramar o cancelar citas, ubicación y teléfonos de urgencias.\n\nAviso clínico: Este asistente no emite diagnósticos médicos ni prescribe medicamentos.',
                enlaces: [
                    { titulo: 'Consultar disponibilidad', url: '/disponibilidad' },
                    { titulo: 'Reservar cita médica', url: '/citas' },
                ],
                modo: this.motor.modo,
                proveedor: this.motor.proveedor,
                hora: this.horaActual(),
            };
        },

        cargarHistorial() {
            try {
                const guardado = sessionStorage.getItem(this.storageKey);
                if (guardado) {
                    const parsed = JSON.parse(guardado);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        this.mensajes = parsed.slice(-20);
                        return;
                    }
                }
            } catch (e) {
                // Ignorar errores de sessionStorage en navegación privada
            }
            this.mensajes = [this.mensajeBienvenida()];
        },

        guardarHistorial() {
            try {
                sessionStorage.setItem(this.storageKey, JSON.stringify(this.mensajes.slice(-20)));
            } catch (e) {
                // Ignorar si sessionStorage no está disponible
            }
        },

        horaActual() {
            try {
                return new Date().toLocaleTimeString('es-BO', { hour: '2-digit', minute: '2-digit' });
            } catch (e) {
                return '';
            }
        },

        async consultarEstado() {
            try {
                const res = await fetch(this.endpointEstado, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) return;
                const json = await res.json();
                if (json.success && json.data) {
                    this.motor = json.data;
                    this.estadoSincronizado = true;
                }
            } catch (e) {
                // Conservar estado inicial
            }
        },

        toggle() {
            this.abierto = !this.abierto;
            if (this.abierto) {
                if (!this.estadoSincronizado) {
                    this.consultarEstado();
                }
                this.$nextTick(() => this.scrollAlFinal());
            }
        },

        preguntar(texto) {
            this.entrada = texto;
            this.enviar();
        },

        async enviar() {
            const texto = (this.entrada || '').trim();
            if (!texto || this.cargando) return;

            this.error = '';
            this.entrada = '';

            const historialPrevio = this.mensajes
                .filter((m) => m.id !== 'bienvenida' && (m.rol === 'user' || m.rol === 'assistant'))
                .slice(-8)
                .map((m) => ({ rol: m.rol, contenido: m.contenido }));

            this.mensajes.push({
                id: 'u-' + Date.now(),
                rol: 'user',
                contenido: texto,
                enlaces: [],
                hora: this.horaActual(),
            });
            this.guardarHistorial();
            this.cargando = true;
            this.$nextTick(() => this.scrollAlFinal());

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(this.endpointMensaje, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        mensaje: texto,
                        historial: historialPrevio,
                    }),
                });

                const json = await res.json().catch(() => ({}));

                if (!res.ok || !json.success || !json.data) {
                    this.error = json.message || 'No se pudo procesar tu consulta en este momento.';
                    return;
                }

                const d = json.data;
                this.motor = {
                    configurado: d.modo === 'ia_api',
                    proveedor: d.proveedor || this.motor.proveedor,
                    modelo: d.modelo || this.motor.modelo,
                    modo: d.modo || 'local_fallback',
                };
                this.estadoSincronizado = true;

                this.mensajes.push({
                    id: 'a-' + Date.now(),
                    rol: 'assistant',
                    contenido: d.respuesta || 'Sin respuesta generada.',
                    enlaces: Array.isArray(d.enlaces) ? d.enlaces : [],
                    modo: d.modo,
                    proveedor: d.proveedor,
                    fallback_activado: Boolean(d.fallback_activado),
                    hora: this.horaActual(),
                });
                this.guardarHistorial();
            } catch (e) {
                this.error = 'Error de conexión con el servicio del asistente. Intenta nuevamente.';
            } finally {
                this.cargando = false;
                this.$nextTick(() => this.scrollAlFinal());
            }
        },

        limpiar() {
            this.error = '';
            this.mensajes = [this.mensajeBienvenida()];
            try {
                sessionStorage.removeItem(this.storageKey);
            } catch (e) {
                // Ignorar
            }
            this.$nextTick(() => this.scrollAlFinal());
        },

        scrollAlFinal() {
            const box = this.$refs.chatMensajes;
            if (box) {
                box.scrollTop = box.scrollHeight;
            }
        },
    }));
});
