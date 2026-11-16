# Dynamic Medical Scheduling, Filtering, Calendar Views, Audit Traceability, and Clinical Seeding

* **Author:** Annett Cruz
* **Date:** 2026-11-15

## Overview

This specification defines four coordinated upgrades to the Clínica Vida Salud system:
1. Synthetic clinical data spanning the past 3 months (70% Attended, 15% Cancelled, 15% No-Show) and upcoming 3 weeks (Programada/Confirmada) for calendar and reporting accuracy.
2. Non-blocking physician selection in `CU10` and `CU11` grouped by primary specialty (`<optgroup>`) when no specialty filter is active, with optional cascading specialty filtering.
3. Expanded immutable JSON audit metadata (`auditoria.detalle`) capturing state transitions (`estado_anterior` -> `estado_nuevo`), mandatory justification texts, operator IP, and linked patient CI.
4. Role-dependent weekly calendar modes in `CU16` (`/agenda-medica`), isolating individual schedules for physicians while allowing parallel multi-physician comparison for receptionists and administrators.

## Analysis

### Weak Points, Edge Cases, and Counter-Arguments

1. **Multi-Physician Calendar Density and Collision Legibility (CU16)**:
   Overlaying all active doctors in a single weekly 7-column grid risks visual clutter when multiple doctors share the same morning block (e.g., 08:00–12:00 across 10 specialists). Without color-coding per physician or multi-select filtering, a combined calendar degrades into an unreadable vertical scroll.
   *Mitigation*: Use compact physician filter chips (multi-select or "Todos en paralelo") combined with doctor initials/badges on each appointment card.

2. **Grouping Doctors by Primary Specialty vs. Multiple Specialties (CU10/CU11)**:
   A doctor linked to two specialties via `medico_especialidad` creates duplicate `<option>` entries if rendered naively under multiple `<optgroup>` tags, or triggers Alpine `x-model` binding mismatches when `id_medico` appears twice in the same `<select>`.
   *Mitigation*: Group each physician once under their primary specialty when no filter is active, and display secondary specialties in parentheses, or auto-assign `especialidadId` upon selecting a doctor when `especialidadId` is blank.

3. **Audit Payload Immutability vs. DB Trigger Duplication**:
   PostgreSQL may already attach low-level row-level triggers (`INSERT`, `UPDATE`) on `cita_medica`. Application-level `AuditService::registrar` must use explicit domain action names (`CREACION`, `REPROGRAMACION`, `CANCELACION`, `ATENCION`, `INASISTENCIA`) and record `estado_anterior` / `estado_nuevo` before committing, without relying on raw DB triggers that lack HTTP IP or mandatory cancellation reasons.

4. **3-Month Synthetic Data Constraints (GiST Exclusion)**:
   The `citas_medicas` table enforces `citas_medicas_no_superpuestas` (non-overlapping intervals for active statuses `1` and `2`). Seeding historical dates (`-90 days` to `-1 day`) with statuses `3` (Atendida), `4` (Cancelada), and `5` (Inasistencia) will not conflict with active overlap checks, whereas future slots (`+1 day` to `+21 days`) in states `1` and `2` must strictly respect `horarios_atencion` windows and avoid slot collisions.

## Goals vs. Non-Goals

* **In-Scope Goals:**
  * Seed 3 months of historical appointments (~70% Atendidas, ~15% Canceladas with reasons, ~15% Inasistencias) and 3 weeks of future scheduled/confirmed appointments respecting `horarios_atencion`.
  * Refactor `CU10`/`CU11` doctor `<select>` to group all physicians by primary specialty when unfiltered, auto-syncing specialty if a doctor is picked first.
  * Standardize `AuditService` calls across `CitaMedicaService`, `ReprogramarCitaService`, `CancelarCitaService`, `LlegadaService`, and `CitaAtendidaService` to persist `accion_ejecutada`, `estado_anterior`, `estado_nuevo`, `motivo_justificacion`, `paciente` (`id_paciente`, `ci`), `medico`, `especialidad`, `fecha_cita`, `hora_cita`, and `ip`.
  * Upgrade `/agenda-medica` (`CU16`) so `Médico` sees only their own schedule, while `Administrador` and `Recepcionista` can toggle between single-doctor focus and parallel multi-doctor comparison.
* **Out-of-Scope Non-Goals:**
  * Replacing PostgreSQL or changing existing foreign key schemas.
  * Building drag-and-drop rescheduling inside the calendar grid (rescheduling remains handled via `CU12`).

## Alternatives Weighed

* **Separate Audit Table for State Transitions**: Rejected by author: the existing `auditoria.detalle` `jsonb` column already supports structured nested keys (`estado_anterior`, `estado_nuevo`, `paciente`, `motivo_justificacion`), avoiding schema migrations while preserving queryability via `detalle::text` and JSONB operators.
* **Requiring Specialty Selection Before Showing Any Doctors**: Rejected by author: receptionists handling walk-in or returning patients who know their physician's name lose time clicking through specialty menus first.

## Key Decisions & Staging for Next Steps

* Proceed to Goldfish architectural and codebase review to validate SQL seeding safety, Blade/Alpine multi-select grouping, and service-layer audit instrumentation before modifying production code and database records.
