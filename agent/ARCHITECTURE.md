# 📐 Arquitectura de Software — Clínica Vida Salud

## 1. Referencia Canónica: `Arquitectura_Sistema_Telcom_BCE` (Obsidian)

Documento en Obsidian: `C:\Users\MSI\Desktop\Desarollo\Arquitectura_Sistema_Telcom_BCE.md`

- **Paradigma:** Desacoplado: **Frontend SPA (React + Vite)** + **Backend API (Laravel 13 / PHP 8.3 / PostgreSQL 16)**.
- **Patrón Boundary-Control-Entity (BCE) & Modularidad Package-by-Feature:**
  - **Boundary (Vistas):** `frontend/src/views/Paquetes/P{N}_{Nombre}/CU{NN}_{Nombre}/`
  - **Control (Custom Hooks / Controllers):**
    - En Frontend: `frontend/src/controllers/Paquetes/P{N}_{Nombre}/CU{NN}_{Nombre}/` (Custom Hooks `use...Controller.js`).
    - En Backend: `backend/app/Http/Controllers/Paquetes/P{N}_{Nombre}/CU{NN}_{Nombre}/` y capa de servicios `backend/app/Services/Paquetes/`.
  - **Entity (Modelos de Dominio y Adaptadores):**
    - En Frontend: `frontend/src/models/Paquetes/P{N}_{Nombre}/`
    - En Backend: `backend/app/Models/Paquetes/P{N}_{Nombre}/`
  - **Canal de Transporte:** `frontend/src/services/` comunicándose vía HTTP/JSON con `backend/routes/api.php`.

---

## 2. Estado Actual del Repositorio (Restaurado)

El repositorio cuenta ahora con la arquitectura BCE completa:
- `frontend/`: Aplicación React + Vite con las 3 capas BCE implementadas por paquetes y casos de uso (`views`, `controllers`, `models`).
- `backend/`: Aplicación Laravel 13 con estructura modular simétrica (`Controllers`, `Services`, `Models`) y esquema relacional PostgreSQL 16.
- `agent/`: Directiva maestra, memoria y documentación arquitectónica del agente.

---

## 3. Matriz de Cumplimiento con TELCOM BCE

| Criterio Arquitectónico | Implementación en Clínica Vida Salud | Estado |
| :--- | :--- | :--- |
| **Arquitectura Desacoplada** | `frontend/` (React/Vite) + `backend/` (Laravel API). | ✅ Cumple |
| **Boundary (UI)** | `frontend/src/views/Paquetes/` organizado por Caso de Uso. | ✅ Cumple |
| **Control en Frontend** | `frontend/src/controllers/Paquetes/` (`use...Controller`). | ✅ Cumple |
| **Entity en Frontend** | `frontend/src/models/Paquetes/` con adaptadores/mock fallback. | ✅ Cumple |
| **Control en Backend** | `backend/app/Http/Controllers/` y `Services/Paquetes/`. | ✅ Cumple |
| **Entity en Backend** | Modelos Eloquent en `backend/app/Models/Paquetes/`. | ✅ Cumple |
| **Modularidad Package-by-Feature** | 7 Paquetes funcionales y 19 Casos de Uso canónicos. | ✅ Cumple |
| **Persistencia** | PostgreSQL 16 con restricciones GiST para citas. | ✅ Cumple |
