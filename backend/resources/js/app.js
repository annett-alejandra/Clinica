import './bootstrap';

/**
 * CLÍNICA VIDA SALUD — MOTOR DE ANIMACIÓN E INTERACCIÓN EDITORIAL
 * Stack: Lenis Smooth Scroll, GSAP ScrollTrigger & Alpine.js Logic
 */

document.addEventListener('DOMContentLoaded', () => {
    // Control de Preferencias de Movimiento y Accesibilidad
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
            if (window.lenisInstance) {
                window.lenisInstance.stop();
            }
        } else {
            document.body.classList.remove('reduced-motion');
            if (motionToggleBtn) {
                motionToggleBtn.innerHTML = '<span>Ⅱ</span> Pausar Animaciones';
                motionToggleBtn.setAttribute('aria-pressed', 'false');
            }
            if (window.lenisInstance) {
                window.lenisInstance.start();
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

    // Lenis Smooth Scroll
    if (!isReducedMotion && typeof Lenis !== 'undefined') {
        const lenis = new Lenis({
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
            direction: 'vertical',
            gestureDirection: 'vertical',
            smooth: true,
            mouseMultiplier: 0.9,
            smoothTouch: false,
            touchMultiplier: 2,
        });

        window.lenisInstance = lenis;

        function raf(time) {
            lenis.raf(time);
            requestAnimationFrame(raf);
        }
        requestAnimationFrame(raf);
    }

    // Revelado de Títulos
    const revealElements = document.querySelectorAll('.title-reveal-inner, .scroll-fade-up');
    
    if ('IntersectionObserver' in window && !isReducedMotion) {
        const observerOptions = {
            root: null,
            rootMargin: '0px 0px -80px 0px',
            threshold: 0.15
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
            el.style.transform = 'translateY(40px)';
            el.style.opacity = '0';
            el.style.transition = 'transform 1s cubic-bezier(0.16, 1, 0.3, 1), opacity 1s cubic-bezier(0.16, 1, 0.3, 1)';
            revealObserver.observe(el);
        });
    } else {
        revealElements.forEach(el => {
            el.style.transform = 'none';
            el.style.opacity = '1';
        });
    }

    // Skip Intro
    const skipIntroBtn = document.getElementById('skip-intro-btn');
    if (skipIntroBtn) {
        skipIntroBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetSection = document.getElementById('capitulo-01');
            if (targetSection) {
                targetSection.scrollIntoView({ behavior: isReducedMotion ? 'auto' : 'smooth' });
            }
        });
    }
});
