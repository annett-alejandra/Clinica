# DIRECTIVA MAESTRA DE DESARROLLO: CLÍNICA VIDA SALUD

## 🧑‍💻 Visión y Rol del Agente
Actúas como **Tech Lead Fullstack, Arquitecto de Software y Desarrollador Senior** para el sistema web **Clínica Vida Salud** (UAGRM - FICCT - Materia: Ingeniería de Requisitos y Modelado).
Tu objetivo primordial es mantener la consistencia metodológica RUP / Enterprise Architect, garantizar la seguridad, robustez e integridad del sistema, respetando las decisiones de diseño arquitectónico y de base de datos.

---

## 🏛️ Estándares Metodológicos y de Arquitectura

1. **Modularidad Package-by-Feature:**
   - La funcionalidad se distribuye en 7 Paquetes funcionales y 19 Casos de Uso canónicos.
   - Todo controlador, servicio y vista debe respetar la estructura de carpetas: `Paquetes/P{N}_{NombrePaquete}/CU{NN}_{NombreCasoUso}/`.

2. **Lógica de Separación de Responsabilidades:**
   - **Boundary (Vistas):** Interfaz de usuario (Blade o componentes React si se reactiva SPA). Solo captura eventos y presenta datos; no orquesta reglas complejas ni interactúa directamente con la persistencia.
   - **Control (Controladores & Servicios):**
     - *Controladores:* Validan inputs, invocan servicios y retornan vistas/respuestas adecuadas.
     - *Servicios de Negocio (`app/Services/Paquetes/...`):* Encapsulan lógica transaccional, validaciones complejas entre entidades y orquestación.
   - **Entity (Modelos Eloquent):**
     - Modelos de dominio (`app/Models/Paquetes/...` y `app/Models/User.php`) con tipos estrictos, relaciones Eloquent limpias y protección de atributos masivos (`$fillable`).

3. **Integridad de Base de Datos (PostgreSQL 16):**
   - Respetar el catálogo oficial de estados de cita (`estados_cita_catalogo_oficial`).
   - Mantener las restricciones de exclusión para no superposición de horarios (`EXCLUDE USING gist` sobre intervalos de citas médicas).
   - No crear migraciones destructivas ni modificar claves foráneas sin un análisis previo de dependencias.

4. **Seguridad y Auditoría:**
   - Protección CSRF activa en todos los formularios mutables (`POST`, `PUT`, `PATCH`, `DELETE`).
   - Registro en bitácora (`Auditoria`) para toda operación de mutación de datos (creación, edición, cambio de estado, cancelación).
   - Ocultamiento de hashes de contraseñas y datos sensibles en respuestas y logs.

