# Clínica Vida Salud

Sistema web de gestión de citas médicas desarrollado con **Laravel 13, PHP 8.3, PostgreSQL 16 y vistas Blade**. Las interfaces se construyen en PHP, HTML y CSS; JavaScript se utiliza para mejoras progresivas.

La clínica busca centralizar pacientes, médicos, especialidades, horarios y reservas para evitar superposiciones y pérdidas de información. Los actores del sistema son Administrador, Recepcionista, Médico y Paciente; las citas conservan los estados Programada, Confirmada, Atendida, Cancelada e Inasistencia.

## Información académica

| Campo | Información |
| --- | --- |
| Universidad | Universidad Autónoma Gabriel René Moreno (UAGRM) |
| Facultad | Ingeniería en Ciencias de la Computación y Telecomunicaciones |
| Materia | Ingeniería de Requisitos y Modelado |
| Docente | MSc. Ing. Angélica Garzón Cuéllar |
| Grupo | 22 - SC |
| Gestión | 2/2026 |

## Arquitectura MVC

La organización por paquetes sigue el enfoque académico de TELCOM. La aplicación se concentra en `backend/`: Laravel recibe las solicitudes web, los controladores utilizan servicios y modelos Eloquent, y las vistas Blade generan el HTML.

| Capa | Ubicación |
| --- | --- |
| Controladores | `backend/app/Http/Controllers/Paquetes/` |
| Modelos | `backend/app/Models/Paquetes/` y el único `backend/app/Models/User.php` |
| Servicios | `backend/app/Services/Paquetes/` |
| Vistas | `backend/resources/views/Paquetes/` |
| Plantillas y componentes | `backend/resources/views/layouts/` y `components/` |
| Rutas web | `backend/routes/web.php` |
| CSS y JavaScript utilizados por Blade | `backend/public/css/` y `backend/public/js/` |

Los siete paquetes y sus 19 casos de uso conservan sus nombres y numeración:

| Paquete | Casos de uso |
| --- | --- |
| P1_SeguridadAdministracion | CU01–CU04: iniciar y cerrar sesión, usuarios y roles, especialidades médicas |
| P2_PersonalMedico | CU05–CU06: médicos y horarios de atención |
| P3_GestionPacientes | CU07–CU09: pacientes, perfil y registro de cuenta |
| P4_GestionCitas | CU10–CU13: disponibilidad, programación, reprogramación y cancelación |
| P5_ControlAtencion | CU14–CU15: confirmación, asistencia, inasistencia y atención |
| P6_ConsultasHistoriales | CU16–CU18: agenda médica, historial del paciente y mis citas |
| P7_ReportesEstadisticas | CU19: reportes administrativos |

Las carpetas de vistas de los casos de uso son marcadores estructurales. Esta etapa prepara el layout, los componentes y los recursos compartidos; las pantallas y operaciones de negocio se implementarán posteriormente.

## Tecnologías

- PHP 8.3 y Laravel 13 para la aplicación y el renderizado de vistas Blade.
- HTML y CSS para las interfaces, con JavaScript opcional.
- PostgreSQL 16 con once tablas funcionales y sus restricciones existentes.
- Sesiones de Laravel en archivos y formularios protegidos con CSRF en el grupo `web`.

Se retiró la carpeta `frontend/` después de confirmar que contenía la plantilla React/Vite y directorios vacíos. La nueva base Blade sirve sus recursos públicos directamente y no requiere Node.js para visualizarse. Las herramientas iniciales de Vite del backend se conservan como opción para compilar assets en el futuro.

## Ejecución local

Requisitos: PHP 8.3 con `pdo_pgsql`, Composer, PostgreSQL 16 y Git.

Para una instalación nueva, instalar las dependencias con Composer y preparar un `.env` propio a partir de `backend/.env.example`, sin sobrescribir configuraciones existentes ni publicar credenciales. La clave de aplicación se genera únicamente al preparar un entorno nuevo. Las migraciones y los seeders se gestionan por separado según el estado de cada instalación.

Con el entorno existente ya preparado:

```bash
cd backend
php artisan serve
```

Abrir la dirección mostrada por Laravel. No es necesario ejecutar otro servidor de frontend.

## Documentación

- [Árbol MVC completo, siete paquetes, 19 casos de uso y componentes Blade](backend/docs/arquitectura-mvc-blade.md).
- [Esquema PostgreSQL y decisiones de integridad](backend/docs/esquema-postgresql.md).
- [Documentación del backend](backend/README.md).

## Comprobaciones

Las pruebas de arquitectura renderizan Blade, verifican sesiones y CSRF y comprueban los directorios de los paquetes. No ejecutan migraciones ni crean datos reales:

```bash
cd backend
php vendor/phpunit/phpunit/phpunit --do-not-cache-result
```
