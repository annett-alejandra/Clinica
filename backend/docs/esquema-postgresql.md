# Clínica Vida Salud — esquema PostgreSQL

Laravel 13, PHP 8.3 y PostgreSQL 16. Se conserva la estructura MVC; los controladores y las vistas de los módulos aún están pendientes.

## Preparación local

1. Instalar PHP 8.3 con `pdo_pgsql`, Composer y PostgreSQL 16.
2. Ejecutar `composer install` desde `backend/`.
3. Si todavía no existe un `.env` local, copiar `.env.example` a `.env`. No sobrescribir un archivo existente ni subir credenciales al repositorio.
4. Completar las credenciales propias y el nombre de una base de datos previamente preparada por el equipo. El ejemplo usa `pgsql`, puerto `5432` y deja usuario y contraseña vacíos.
5. Generar la clave de aplicación solo en una instalación nueva y mantener permisos de escritura en `storage/` y `bootstrap/cache/`.

Las sesiones y la caché usan archivos (`SESSION_DRIVER=file`, `CACHE_STORE=file`). Las colas usan `sync` y su registro de fallos está desactivado (`QUEUE_FAILED_DRIVER=null`). Así no se requieren tablas técnicas adicionales. Los scripts de Composer no ejecutan migraciones automáticamente.

La aplicación se puede iniciar con `php artisan serve`. Las funciones que necesitan datos estarán disponibles después de que el equipo autorice y ejecute por separado las migraciones y los seeders. Esta revisión solo modifica y verifica el código.

## Esquema y orden

Se mantienen exactamente once tablas funcionales: `roles`, `usuarios`, `pacientes`, `especialidades`, `medicos`, `medicos_especialidades`, `horarios_atencion`, `estados_cita`, `citas_medicas`, `auditoria` e `historial_cambios_cita`.

Laravel ordena las migraciones por nombre. La primera crea `roles` y `usuarios`; luego se crean pacientes y especialidades. Al crear médicos se agregan las dos claves foráneas pendientes de `usuarios`, cuando ambas tablas referenciadas ya existen. Después se crean la relación médico–especialidad, los horarios, los estados, las citas, la auditoría y el historial. El orden inverso permite revertir sus dependencias.

`usuarios.id_paciente` y `usuarios.id_medico` admiten NULL y tienen unicidad independiente. PostgreSQL permite varias filas con NULL, pero impide dos cuentas para el mismo paciente o médico. `usuarios_vinculo_exclusivo` impide completar ambos vínculos a la vez; permite dejar ambos vacíos para cuentas del personal.

`tipo_paciente` es opcional hasta que la clínica apruebe su clasificación. No se inventan valores ni una tabla de clasificación.

## Catálogos y protección de horarios

`DatabaseSeeder` llama a `RolesSeeder` y `EstadosCitaSeeder`. No crea cuentas. Los roles se insertan por su nombre único, sin asumir IDs; repetir el seeder conserva las filas y descripciones existentes.

Los estados tienen identificadores explícitos, sin secuencia automática:

| ID | Estado | Ocupa horario |
| --- | --- | --- |
| 1 | Programada | Sí |
| 2 | Confirmada | Sí |
| 3 | Atendida | No |
| 4 | Cancelada | No |
| 5 | Inasistencia | No |

La restricción `estados_cita_catalogo_oficial` fija cada pareja de ID y nombre en la base de datos. Impide reasignar los IDs o agregar otros estados sin una migración deliberada. Se pueden editar las descripciones. `EstadosCitaSeeder` inserta solo filas faltantes y verifica la correspondencia dentro de una transacción; si encuentra IDs o nombres incompatibles, falla sin renumerar ni sobrescribir registros existentes.

**Dependencia:** antes de registrar citas deben cargarse los cinco estados mediante `EstadosCitaSeeder`, llamado por `DatabaseSeeder`. La clave foránea impide usar un estado inexistente. Cambiar el catálogo oficial exige revisar conjuntamente su CHECK, el seeder y la condición parcial de exclusión mediante una migración nueva cuando ya exista una instalación. Los archivos iniciales conservan literales históricos; no deben depender de constantes mutables de la aplicación.

`citas_medicas_no_superpuestas` conserva `EXCLUDE USING gist`, la igualdad de `id_medico` y el operador de solapamiento `&&` sobre el intervalo calculado desde fecha, hora y duración. Su condición `WHERE (id_estado_cita IN (1, 2))` incluye únicamente Programadas y Confirmadas. PostgreSQL comprueba las inserciones y actualizaciones, también entre transacciones concurrentes. Cambiar una cita a un estado que ocupa horario vuelve a comprobar el conflicto; cancelarla o marcar inasistencia libera su intervalo. Atendida queda fuera conforme a la regla actual de ocupación.

El intervalo es `[inicio, fin)`: una cita de 09:00 a 09:30 permite otra a las 09:30. Otro médico puede atender en el mismo intervalo. La duración positiva se exige con CHECK, al igual que el día 1–7, el fin posterior al inicio y la duración positiva de los horarios de atención. Se conserva la extensión `btree_gist`; quien ejecute la migración necesita tenerla disponible y permiso para instalarla, o solicitar su preparación al administrador de PostgreSQL.

Referencias: [restricciones de exclusión y condiciones parciales](https://www.postgresql.org/docs/16/sql-createtable.html#SQL-CREATETABLE-EXCLUDE), [rangos e intervalos](https://www.postgresql.org/docs/16/rangetypes.html), [btree_gist](https://www.postgresql.org/docs/16/btree-gist.html).

## Autenticación, pruebas y auditoría

El proveedor Eloquent sigue usando `App\Models\User`, tabla `usuarios`, clave `id_usuario`, contraseña `password_hash` y sin timestamps. Al implementar el acceso, las credenciales de Laravel serán `correo` (o `nombre_usuario`) y `password`; el modelo resuelve el hash almacenado y lo oculta en la serialización. «Recordarme» queda deshabilitado porque no hay columna de token. La recuperación de contraseñas requiere una decisión posterior; no se añade una tabla de tokens.

`UserFactory` se reserva para pruebas o datos solicitados explícitamente. Genera contraseñas aleatorias, usa por defecto el rol Paciente resuelto por nombre y deja los vínculos personales opcionales en NULL. Acepta sobrescribir el rol y los vínculos cuando una prueba los necesite. Ningún seeder crea usuarios administrativos ni contraseñas conocidas.

`auditoria.id_registro_afectado` admite NULL para eventos generales; `id_usuario` también puede ser NULL si no se identificó un usuario. Para accesos se puede usar `tabla_afectada=usuarios` sin inventar una clave de registro. Al implementar el registro, seleccionar solo metadatos necesarios como resultado, acción y nombres de campos cambiados. Excluir contraseñas y hashes, tokens y datos médicos; no volcar solicitudes o modelos completos en `detalle` ni en los logs. Esta revisión no implementa un registrador de auditoría.

Las pruebas unitarias de autenticación no arrancan la aplicación ni conectan a una base de datos:

```bash
php vendor/phpunit/phpunit/phpunit --testsuite=Unit
```

El `phpunit.xml` inicial usa SQLite en memoria para otras pruebas; no valida este esquema específico de PostgreSQL. La validación real de CHECK, claves foráneas, seeders repetidos y concurrencia queda pendiente para una base PostgreSQL de pruebas autorizada. No usar pruebas con `RefreshDatabase` sobre datos existentes.

Si las migraciones iniciales ya se ejecutaron en alguna instalación, editarlas no cambia esa base. Se deberá preparar posteriormente una migración incremental con una revisión de duplicados y estados existentes, sin borrar información.

## Archivos de esta revisión

Las rutas siguientes son relativas a `backend/`. Se editaron seis migraciones existentes y se conservaron sus nombres y métodos de reversión:

- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/0001_01_01_000003_create_pacientes_table.php`
- `database/migrations/0001_01_01_000007_create_horarios_atencion_table.php`
- `database/migrations/0001_01_01_000008_create_estados_cita_table.php`
- `database/migrations/0001_01_01_000009_create_citas_medicas_table.php`
- `database/migrations/0001_01_01_0010_create_auditoria_table.php`
- `app/Models/User.php`
- `database/factories/UserFactory.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/RolesSeeder.php` (nuevo)
- `database/seeders/EstadosCitaSeeder.php` (nuevo)
- `config/database.php`
- `config/session.php`
- `config/cache.php`
- `config/queue.php`
- `.env.example`
- `composer.json`
- `README.md`
- `docs/esquema-postgresql.md` (nuevo)
- `tests/Unit/UserAuthenticationTest.php` (nuevo)

## Resultado de la revisión sin base de datos

- PHP 8.3.33: `php -l` en 31 archivos de `app/`, `config/`, `database/` y `tests/`; cero errores de sintaxis.
- PHPUnit 12.5.42, suite Unit: cuatro pruebas, doce aserciones, todas correctas. Tres pruebas nuevas verifican el contrato de autenticación con Laravel, la contraseña válida e inválida mediante bcrypt y la ocultación del hash.
- Laravel Pint: formato correcto en los dieciséis archivos PHP modificados o nuevos.
- `git diff --check`: sin errores.
- Comparación estática con una instantánea del código antes de la revisión: diez archivos de migración, once tablas y catorce claves foráneas conservadas, incluida la compuesta médico–especialidad, con `RESTRICT` al actualizar y eliminar. Todas las tablas referenciadas existen antes de definir cada clave foránea; se conservaron los métodos de reversión.
- Sin llamadas `Blueprint::check`; correspondencia de los cinco estados comprobada entre el CHECK y el seeder; JSON de Composer válido y sin migraciones automáticas.
- Huella SHA-256 de `.env` idéntica antes y después. No se ejecutaron migraciones, seeders, consultas SQL ni comandos de borrado de datos.

La idempotencia de los seeders y la exclusión se revisaron en el código. Su comportamiento real en PostgreSQL, especialmente entre transacciones concurrentes, requiere una ejecución posterior autorizada en una base de pruebas.
