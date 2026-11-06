<?php

/*
|--------------------------------------------------------------------------
| Configuración institucional de Clínica Vida Salud
|--------------------------------------------------------------------------
| Centraliza el contenido de la web pública y el mapa de módulos (casos de
| uso) visibles según el rol del usuario. Las rutas siguen protegidas por el
| middleware `rol:`; este mapa solo decide qué enlaces se muestran.
*/

return [

    'nombre' => 'Clínica Vida Salud',
    'ubicacion' => 'Santa Cruz de la Sierra, Bolivia',
    'telefono' => '+591 3 300 0000',
    'urgencias' => 'Urgencias 24/7',

    // Módulos (casos de uso) y roles con acceso. Roles vacíos = público.
    'modulos' => [
        ['cu' => 'CU11', 'grupo' => 'Citas', 'titulo' => 'Programar cita', 'desc' => 'Reserva de consultas médicas', 'url' => '/citas', 'roles' => ['Administrador', 'Recepcionista', 'Paciente']],
        ['cu' => 'CU18', 'grupo' => 'Citas', 'titulo' => 'Mis citas', 'desc' => 'Próximas visitas e historial', 'url' => '/mis-citas', 'roles' => ['Paciente', 'Administrador']],
        ['cu' => 'CU10', 'grupo' => 'Citas', 'titulo' => 'Disponibilidad', 'desc' => 'Turnos libres en tiempo real', 'url' => '/disponibilidad', 'roles' => []],
        ['cu' => 'CU14', 'grupo' => 'Atención', 'titulo' => 'Control de llegadas', 'desc' => 'Confirmación y asistencia', 'url' => '/control-atencion', 'roles' => ['Administrador', 'Recepcionista']],
        ['cu' => 'CU15', 'grupo' => 'Atención', 'titulo' => 'Atención médica', 'desc' => 'Registrar cita atendida', 'url' => '/atencion-medica', 'roles' => ['Médico', 'Administrador']],
        ['cu' => 'CU16', 'grupo' => 'Atención', 'titulo' => 'Agenda médica', 'desc' => 'Agenda por fecha y médico', 'url' => '/agenda-medica', 'roles' => ['Administrador', 'Recepcionista', 'Médico']],
        ['cu' => 'CU17', 'grupo' => 'Atención', 'titulo' => 'Historial de paciente', 'desc' => 'Citas por paciente', 'url' => '/historial-paciente', 'roles' => ['Administrador', 'Recepcionista']],
        ['cu' => 'CU07', 'grupo' => 'Gestión', 'titulo' => 'Pacientes', 'desc' => 'Directorio de pacientes', 'url' => '/pacientes', 'roles' => ['Administrador', 'Recepcionista']],
        ['cu' => 'CU05', 'grupo' => 'Gestión', 'titulo' => 'Médicos', 'desc' => 'Personal médico', 'url' => '/medicos', 'roles' => ['Administrador', 'Recepcionista']],
        ['cu' => 'CU06', 'grupo' => 'Gestión', 'titulo' => 'Horarios', 'desc' => 'Turnos de atención', 'url' => '/horarios', 'roles' => ['Administrador', 'Recepcionista', 'Médico']],
        ['cu' => 'CU04', 'grupo' => 'Administración', 'titulo' => 'Especialidades', 'desc' => 'Catálogo médico', 'url' => '/especialidades', 'roles' => ['Administrador']],
        ['cu' => 'CU03', 'grupo' => 'Administración', 'titulo' => 'Usuarios y roles', 'desc' => 'Accesos y seguridad', 'url' => '/usuarios', 'roles' => ['Administrador']],
        ['cu' => 'CU19', 'grupo' => 'Administración', 'titulo' => 'Reportes', 'desc' => 'Métricas administrativas', 'url' => '/reportes-administrativos', 'roles' => ['Administrador']],
        ['cu' => 'CU08', 'grupo' => 'Mi cuenta', 'titulo' => 'Mi perfil', 'desc' => 'Datos personales y contacto', 'url' => '/perfil-paciente', 'roles' => ['Paciente', 'Administrador']],
    ],

    // Presentación visual de los estados de cita (id_estado_cita => tipo de badge).
    'estados_badge' => [1 => 'info', 2 => 'stable', 3 => 'stable', 4 => 'critical', 5 => 'caution'],

    // Recorrido por las instalaciones (carrusel de la web pública).
    'areas' => [
        [
            'id' => '01', 'tag' => '01 / QUIRÓFANOS',
            'title' => 'Quirófanos de alta precisión',
            'subtitle' => 'Bloque quirúrgico integrado',
            'description' => 'Pabellones con flujo laminar, monitoreo digital y equipos de última generación para intervenciones seguras y de rápida recuperación.',
            'imageDay' => 'https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Pabellones quirúrgicos equipados',
            'equipment' => 'Monitoreo multiparamétrico, torre de laparoscopía, arco en C',
            'certifications' => 'Protocolos de bioseguridad y sala limpia',
            'status' => 'Activo 24/7', 'statusType' => 'stable',
        ],
        [
            'id' => '02', 'tag' => '02 / SUITES Y HOSPITALIZACIÓN',
            'title' => 'Suites privadas de confort y recuperación',
            'subtitle' => 'Hospitalización con acompañamiento',
            'description' => 'Habitaciones luminosas con aislamiento acústico, control de temperatura y espacio para el familiar acompañante.',
            'imageDay' => 'https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1512678080530-7760d81faba6?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Suites individuales y dobles',
            'equipment' => 'Camas eléctricas, oxígeno central, llamado a enfermería',
            'certifications' => 'Servicio de hotelería hospitalaria',
            'status' => 'Ocupación óptima', 'statusType' => 'info',
        ],
        [
            'id' => '03', 'tag' => '03 / CUIDADOS INTENSIVOS',
            'title' => 'Unidad de terapia intensiva',
            'subtitle' => 'Monitoreo fisiológico avanzado',
            'description' => 'Boxes individuales con soporte ventilatorio y hemodinámico, atendidos por un equipo de intensivistas permanente.',
            'imageDay' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Camas UTI individuales',
            'equipment' => 'Ventiladores mecánicos, monitores de cabecera, bombas de infusión',
            'certifications' => 'Guardia de intensivistas permanente',
            'status' => 'Guardia operativa', 'statusType' => 'stable',
        ],
        [
            'id' => '04', 'tag' => '04 / DIAGNÓSTICO POR IMÁGENES',
            'title' => 'Centro de imagenología y resonancia',
            'subtitle' => 'Diagnóstico de alta resolución',
            'description' => 'Tomografía, resonancia magnética, ecografía y rayos X digitales con informes rápidos para una decisión clínica oportuna.',
            'imageDay' => 'https://images.unsplash.com/photo-1588776814546-1ffcf47267a5?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1579154204601-01588f351e67?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Resonancia, tomografía, ecografía y rayos X',
            'equipment' => 'Resonador 3 Tesla, tomógrafo multicorte, ecógrafos Doppler',
            'certifications' => 'Radiología segura con dosis reducida',
            'status' => 'Atención continua', 'statusType' => 'stable',
        ],
        [
            'id' => '05', 'tag' => '05 / URGENCIAS',
            'title' => 'Urgencias y trauma',
            'subtitle' => 'Triage y respuesta inmediata',
            'description' => 'Acceso vehicular directo, boxes de reanimación y clasificación de pacientes por prioridad clínica.',
            'imageDay' => 'https://images.unsplash.com/photo-1587351021759-3e566b6af7cc?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1519494080410-f9ab76cb1066?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Boxes de urgencia y sala de reanimación',
            'equipment' => 'Desfibriladores bifásicos, ecografía FAST, carro de paro',
            'certifications' => 'Protocolos de infarto y ACV',
            'status' => 'Servicio 24/7', 'statusType' => 'critical',
        ],
        [
            'id' => '06', 'tag' => '06 / ARQUITECTURA TERAPÉUTICA',
            'title' => 'Jardines terapéuticos y salones de bienestar',
            'subtitle' => 'Entornos de recuperación natural',
            'description' => 'Patios internos, fuentes de agua y terrazas verdes que favorecen la recuperación de pacientes y familias.',
            'imageDay' => 'https://images.unsplash.com/photo-1541829070764-84a7d30dd3f3?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1507652313519-d4e9174996dd?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Áreas verdes de libre acceso',
            'equipment' => 'Ventilación natural cruzada y filtros HEPA',
            'certifications' => 'Diseño hospitalario humanizado',
            'status' => 'Visitas 08:00 a 20:00', 'statusType' => 'stable',
        ],
        [
            'id' => '07', 'tag' => '07 / LABORATORIO CLÍNICO',
            'title' => 'Laboratorio central automatizado',
            'subtitle' => 'Análisis clínicos y biología molecular',
            'description' => 'Procesamiento automatizado de muestras con trazabilidad digital y entrega de resultados en tiempos mínimos.',
            'imageDay' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1579165466791-788226ab77b6?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Miles de análisis diarios automatizados',
            'equipment' => 'Analizadores de química, hematología e inmunología',
            'certifications' => 'Control de calidad interno y externo',
            'status' => 'Muestras en proceso continuo', 'statusType' => 'info',
        ],
        [
            'id' => '08', 'tag' => '08 / MATERNIDAD',
            'title' => 'Centro de la mujer y maternidad',
            'subtitle' => 'Parto humanizado y neonatología',
            'description' => 'Salas de parto y recuperación integradas, con equipamiento neonatal y acompañamiento personalizado continuo.',
            'imageDay' => 'https://images.unsplash.com/photo-1516616376974-59a96f4dd42f?auto=format&fit=crop&w=1920&q=80',
            'imageNight' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?auto=format&fit=crop&w=1920&q=80',
            'capacity' => 'Salas de parto y cunas de neonatología',
            'equipment' => 'Incubadoras cerradas, monitor fetal, cunas de calor radiante',
            'certifications' => 'Atención amiga de la madre y el recién nacido',
            'status' => 'Ingreso inmediato', 'statusType' => 'stable',
        ],
    ],
];

