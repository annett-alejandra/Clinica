# Arquitectura MVC con PHP y Blade

Clínica Vida Salud utiliza Laravel 13, PHP 8.3, PostgreSQL 16 y vistas PHP Blade (`.blade.php`) con HTML y CSS. JavaScript se reserva para mejoras progresivas. La estructura sigue la organización académica por paquetes de TELCOM.

## Revisión y alcance

Se revisó `frontend/`: contenía el contador inicial, enlaces, estilos y recursos de la plantilla React/Vite, además de directorios vacíos con `.gitkeep`. No había lógica de la clínica, peticiones al backend, formularios de negocio ni casos de uso implementados. Se retiró esa carpeta y se conservaron exactamente los nombres de sus siete paquetes y sus 19 carpetas de vistas dentro de Laravel.

Los modelos Eloquent, controladores y servicios existentes permanecen intactos. La reorganización no añade tablas, migraciones, seeders ni lógica de los casos de uso. Las carpetas de casos de uso contienen únicamente `.gitkeep`; las pantallas se crearán cuando se implemente cada caso.

## Estructura

```text
clinica-vida-salud/
├── README.md
└── backend/
    ├── app/
    │   ├── Http/Controllers/
    │   │   ├── Controller.php
    │   │   └── Paquetes/                 # Siete paquetes y 19 casos existentes
    │   ├── Models/
    │   │   ├── User.php                  # Único modelo de autenticación
    │   │   └── Paquetes/                 # Modelos Eloquent existentes
    │   └── Services/Paquetes/            # Siete paquetes existentes
    ├── routes/web.php
    ├── public/
    │   ├── index.php
    │   ├── css/app.css                   # Estilos utilizados por Blade
    │   └── js/app.js                     # Mejoras progresivas
    ├── resources/
    │   ├── css/                          # Entradas iniciales opcionales de Vite
    │   ├── js/                           # Entradas iniciales opcionales de Vite
    │   └── views/
    │       ├── welcome.blade.php
    │       ├── layouts/app.blade.php
    │       ├── components/
    │       │   ├── alerts.blade.php
    │       │   └── form.blade.php
    │       └── Paquetes/
    │           ├── P1_SeguridadAdministracion/
    │           │   ├── CU01_IniciarSesion/
    │           │   ├── CU02_CerrarSesion/
    │           │   ├── CU03_GestionarUsuariosRoles/
    │           │   └── CU04_GestionarEspecialidadesMedicas/
    │           ├── P2_PersonalMedico/
    │           │   ├── CU05_GestionarMedicos/
    │           │   └── CU06_GestionarHorariosAtencion/
    │           ├── P3_GestionPacientes/
    │           │   ├── CU07_GestionarPacientes/
    │           │   ├── CU08_GestionarPerfilPaciente/
    │           │   └── CU09_RegistrarCuentaPaciente/
    │           ├── P4_GestionCitas/
    │           │   ├── CU10_ConsultarDisponibilidadMedica/
    │           │   ├── CU11_ProgramarCitaMedica/
    │           │   ├── CU12_ReprogramarCitaMedica/
    │           │   └── CU13_CancelarCitaMedica/
    │           ├── P5_ControlAtencion/
    │           │   ├── CU14_RegistrarConfirmacionAsistenciaInasistencia/
    │           │   └── CU15_RegistrarCitaAtendida/
    │           ├── P6_ConsultasHistoriales/
    │           │   ├── CU16_ConsultarAgendaMedica/
    │           │   ├── CU17_ConsultarHistorialCitasPaciente/
    │           │   └── CU18_ConsultarMisCitas/
    │           └── P7_ReportesEstadisticas/
    │               └── CU19_GenerarReportesAdministrativos/
    └── tests/
        ├── Feature/BladeArchitectureTest.php
        └── Unit/                         # Pruebas existentes
```

Las carpetas de vistas se corresponden con las carpetas existentes de controladores por paquete y caso de uso. Los modelos se comparten entre los paquetes que los necesitan, sin duplicar entidades. Los servicios conservan su ubicación y asumirán las operaciones de negocio al implementar los casos de uso.

## Rutas y sesiones

`bootstrap/app.php` ya carga `routes/web.php`. Laravel le aplica el grupo `web`, que inicia sesiones, comparte errores de validación con las vistas y mantiene su protección CSRF. No se añaden excepciones a esa protección ni se duplica el grupo de middleware.

La ruta inicial `GET /`, nombrada `inicio`, muestra únicamente una página base Blade. No hay rutas de acceso, reservas o mantenimiento implementadas. Las futuras rutas enviarán las solicitudes a los controladores del paquete correspondiente; estos utilizarán los servicios y modelos existentes y devolverán vistas `Paquetes.<paquete>.<caso>.<vista>`.

La configuración existente usa sesiones en archivos por defecto (`storage/framework/sessions`). Se conserva `.env`. Los mensajes temporales se muestran mediante `components/alerts.blade.php`, que escapa su contenido y muestra los errores de validación.

## Layout y formularios

Las futuras vistas extenderán `layouts.app` y completarán las secciones `title` y `content`. El layout proporciona HTML en español, recursos públicos, un enlace accesible al contenido, mensajes y espacios `@stack('styles')` y `@stack('scripts')` para recursos específicos.

El componente anónimo `<x-form>` recibe una URL en `action` y un método en `method`. Genera `@csrf` para POST, PUT, PATCH y DELETE; los tres últimos utilizan POST y el campo `@method` de Laravel. Los formularios GET no incluyen tokens en la URL. Solo se admiten esos cinco métodos. El componente no crea rutas, no procesa datos y no implementa casos de uso.

Para formularios escritos directamente en una vista, incluir `@csrf` dentro del formulario cuando cambie datos y `@method` cuando corresponda. Mostrar los valores mediante `{{ }}` para conservar el escape HTML. La validación y autorización se añadirán al implementar cada caso de uso.

## CSS y JavaScript

Los archivos usados por el layout son `public/css/app.css` y `public/js/app.js`, referenciados mediante `asset()`. Esta base funciona con PHP sin instalar Node.js ni compilar assets. JavaScript permite cerrar mensajes y no controla operaciones de negocio.

Se conservan los archivos iniciales de herramientas del backend (`package.json`, `vite.config.js`, `resources/css/` y `resources/js/`). Vite puede compilar CSS y JavaScript para Blade si el equipo decide utilizarlo después; el layout actual no usa `@vite` ni depende de un manifiesto generado. No hay una aplicación cliente separada.

## Ejecución y comprobaciones

Desde `backend/`, con las dependencias PHP y el entorno local ya configurados:

```bash
php artisan serve
```

Abrir la dirección indicada por Laravel. La preparación de vistas no requiere ejecutar migraciones, seeders ni comandos de reinicio de la base de datos.

Las pruebas verifican el renderizado de Blade, sesiones en memoria, escape de mensajes, CSRF de formularios, middleware web y correspondencia entre las siete carpetas de paquetes y sus 19 casos de uso. Las rutas que emplean para probar formularios existen solo dentro del proceso de pruebas; no se añaden a las rutas de la aplicación.

```bash
php vendor/phpunit/phpunit/phpunit --do-not-cache-result
```

Las comprobaciones no utilizan datos reales ni ejecutan migraciones. Se bloquean las consultas SQL en las nuevas pruebas de arquitectura.

## Archivos de la reorganización

- Eliminado `frontend/` tras revisar su plantilla y sus marcadores vacíos.
- Modificados `README.md` del repositorio y `backend/README.md`.
- Modificados `backend/routes/web.php` y `backend/resources/views/welcome.blade.php`.
- Creados `backend/resources/views/layouts/app.blade.php`, `components/alerts.blade.php` y `components/form.blade.php`.
- Creados 19 archivos `.gitkeep` dentro de `backend/resources/views/Paquetes/`, uno por caso de uso.
- Creados `backend/public/css/app.css` y `backend/public/js/app.js`.
- Creados `backend/docs/arquitectura-mvc-blade.md` y `backend/tests/Feature/BladeArchitectureTest.php`.
