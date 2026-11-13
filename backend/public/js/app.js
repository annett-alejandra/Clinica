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
});
