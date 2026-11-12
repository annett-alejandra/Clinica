--
-- PostgreSQL database dump
--

\restrict HHoQhff6drOwci3tyEzaNwMzbbLc8WCFdbDEos0xFhM68FKooBub6scyE6fiWxN

-- Dumped from database version 18.6 (Debian 18.6-1.pgdg12+2)
-- Dumped by pg_dump version 18.1

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: public; Type: SCHEMA; Schema: -; Owner: -
--

-- *not* creating schema, since initdb creates it


--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: fn_trg_auditar_cambios_cita(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_auditar_cambios_cita() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_tipo_cambio VARCHAR(100);
BEGIN
    IF (OLD.fecha_cita <> NEW.fecha_cita OR OLD.hora_cita <> NEW.hora_cita) THEN
        INSERT INTO historial_cambio_cita (
            id_cita,
            estado_anterior,
            estado_nuevo,
            tipo_cambio,
            observacion,
            fecha_hora_anterior,
            fecha_hora_nueva,
            fecha_modificacion
        ) VALUES (
            NEW.id_cita,
            OLD.id_estado_cita,
            NEW.id_estado_cita,
            'REPROGRAMACION',
            COALESCE(NEW.motivo, 'Cita reprogramada por cambio de horario o fecha'),
            (OLD.fecha_cita || ' ' || OLD.hora_cita)::TIMESTAMP,
            (NEW.fecha_cita || ' ' || NEW.hora_cita)::TIMESTAMP,
            CURRENT_TIMESTAMP
        );
    END IF;

    IF (OLD.id_estado_cita <> NEW.id_estado_cita) THEN
        CASE NEW.id_estado_cita
            WHEN 2 THEN v_tipo_cambio := 'CONFIRMACION';
            WHEN 3 THEN v_tipo_cambio := 'ATENCION';
            WHEN 4 THEN v_tipo_cambio := 'CANCELACION';
            WHEN 5 THEN v_tipo_cambio := 'INASISTENCIA';
            ELSE v_tipo_cambio := 'CAMBIO_ESTADO';
        END CASE;

        INSERT INTO historial_cambio_cita (
            id_cita,
            estado_anterior,
            estado_nuevo,
            tipo_cambio,
            observacion,
            fecha_hora_anterior,
            fecha_hora_nueva,
            fecha_modificacion
        ) VALUES (
            NEW.id_cita,
            OLD.id_estado_cita,
            NEW.id_estado_cita,
            v_tipo_cambio,
            COALESCE(NEW.motivo_cancelacion, NEW.observacion_atencion, 'Transición de estado de cita'),
            NULL,
            NULL,
            CURRENT_TIMESTAMP
        );
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_auditoria_paciente(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_auditoria_paciente() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF (TG_OP = 'INSERT') THEN
        INSERT INTO auditoria (accion, tabla_afectada, id_registro_afectado, detalle, fecha_hora)
        VALUES ('INSERT', 'paciente', NEW.id_paciente, row_to_json(NEW)::JSONB, CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF (TG_OP = 'UPDATE') THEN
        INSERT INTO auditoria (accion, tabla_afectada, id_registro_afectado, detalle, fecha_hora)
        VALUES ('UPDATE', 'paciente', NEW.id_paciente, jsonb_build_object('anterior', row_to_json(OLD)::JSONB, 'nuevo', row_to_json(NEW)::JSONB), CURRENT_TIMESTAMP);
        RETURN NEW;
    ELSIF (TG_OP = 'DELETE') THEN
        INSERT INTO auditoria (accion, tabla_afectada, id_registro_afectado, detalle, fecha_hora)
        VALUES ('DELETE', 'paciente', OLD.id_paciente, row_to_json(OLD)::JSONB, CURRENT_TIMESTAMP);
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$;


--
-- Name: fn_trg_registrar_hora_llegada_automatica(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_registrar_hora_llegada_automatica() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF NEW.id_estado_cita = 2 AND OLD.id_estado_cita <> 2 AND NEW.fecha_hora_llegada IS NULL THEN
        NEW.fecha_hora_llegada := CURRENT_TIMESTAMP;
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_restringir_cambios_citas_finalizadas(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_restringir_cambios_citas_finalizadas() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    IF OLD.id_estado_cita = 3 THEN
        RAISE EXCEPTION 'Operación inválida: No se pueden realizar modificaciones sobre una cita que ya fue ATENDIDA.';
    END IF;

    IF OLD.id_estado_cita = 4 THEN
        RAISE EXCEPTION 'Operación inválida: No se puede modificar ni reactivar una cita que ya ha sido CANCELADA.';
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_validar_disponibilidad_paciente(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_validar_disponibilidad_paciente() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_conflicto INT;
    v_hora_fin TIME;
BEGIN
    IF NEW.id_estado_cita IN (4, 5) THEN
        RETURN NEW;
    END IF;

    v_hora_fin := NEW.hora_cita + (COALESCE(NEW.duracion_minutos, 30) || ' minutes')::INTERVAL;

    SELECT COUNT(*)
    INTO v_conflicto
    FROM cita_medica c
    WHERE c.id_paciente = NEW.id_paciente
      AND c.fecha_cita = NEW.fecha_cita
      AND c.id_estado_cita NOT IN (4, 5)
      AND c.id_cita <> COALESCE(NEW.id_cita, 0)
      AND (
          (NEW.hora_cita >= c.hora_cita AND NEW.hora_cita < (c.hora_cita + (COALESCE(c.duracion_minutos, 30) || ' minutes')::INTERVAL))
          OR
          (v_hora_fin > c.hora_cita AND v_hora_fin <= (c.hora_cita + (COALESCE(c.duracion_minutos, 30) || ' minutes')::INTERVAL))
      );

    IF v_conflicto > 0 THEN
        RAISE EXCEPTION 'Conflicto detectado: El paciente ya tiene otra cita médica programada en ese mismo intervalo.';
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_validar_entidades_activas(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_validar_entidades_activas() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_medico_activo BOOLEAN;
    v_paciente_activo BOOLEAN;
BEGIN
    IF NEW.id_estado_cita = 4 THEN
        RETURN NEW;
    END IF;

    SELECT estado INTO v_medico_activo FROM medico WHERE id_medico = NEW.id_medico;
    IF v_medico_activo IS NOT TRUE THEN
        RAISE EXCEPTION 'Operación rechazada: El médico seleccionado se encuentra inactivo.';
    END IF;

    SELECT estado INTO v_paciente_activo FROM paciente WHERE id_paciente = NEW.id_paciente;
    IF v_paciente_activo IS NOT TRUE THEN
        RAISE EXCEPTION 'Operación rechazada: El paciente seleccionado se encuentra inactivo.';
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_validar_fecha_futura_cita(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_validar_fecha_futura_cita() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    -- Solo se exige fecha futura al crear o modificar citas en estado 'Programada' (1) o 'Confirmada' (2)
    IF NEW.id_estado_cita IN (1, 2) THEN
        IF (NEW.fecha_cita < CURRENT_DATE) OR (NEW.fecha_cita = CURRENT_DATE AND NEW.hora_cita < CURRENT_TIME) THEN
            RAISE EXCEPTION 'Fecha inválida: No es posible programar una cita en una fecha u hora pasada.';
        END IF;
    END IF;
    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_validar_horarios_medico_sin_traslape(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_validar_horarios_medico_sin_traslape() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_solapamiento INT;
BEGIN
    SELECT COUNT(*)
    INTO v_solapamiento
    FROM horario_atencion h
    WHERE h.id_medico = NEW.id_medico
      AND h.dia_semana = NEW.dia_semana
      AND h.id_horario <> COALESCE(NEW.id_horario, 0)
      AND (
          (NEW.hora_inicio >= h.hora_inicio AND NEW.hora_inicio < h.hora_fin)
          OR
          (NEW.hora_fin > h.hora_inicio AND NEW.hora_fin <= h.hora_fin)
          OR
          (NEW.hora_inicio <= h.hora_inicio AND NEW.hora_fin >= h.hora_fin)
      );

    IF v_solapamiento > 0 THEN
        RAISE EXCEPTION 'Configuración rechazada: El médico ya tiene otro turno de atención superpuesto en ese mismo día y horario.';
    END IF;

    RETURN NEW;
END;
$$;


--
-- Name: fn_trg_validar_superposicion_cita(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.fn_trg_validar_superposicion_cita() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
DECLARE
    v_citas_conflicto INT;
    v_hora_fin_nueva TIME;
BEGIN
    IF NEW.id_estado_cita = 4 THEN
        RETURN NEW;
    END IF;

    v_hora_fin_nueva := NEW.hora_cita + (COALESCE(NEW.duracion_minutos, 30) || ' minutes')::INTERVAL;

    SELECT COUNT(*)
    INTO v_citas_conflicto
    FROM cita_medica c
    WHERE c.id_medico = NEW.id_medico
      AND c.fecha_cita = NEW.fecha_cita
      AND c.id_estado_cita NOT IN (4)
      AND c.id_cita <> COALESCE(NEW.id_cita, 0)
      AND (
          (NEW.hora_cita >= c.hora_cita AND NEW.hora_cita < (c.hora_cita + (COALESCE(c.duracion_minutos, 30) || ' minutes')::INTERVAL))
          OR
          (v_hora_fin_nueva > c.hora_cita AND v_hora_fin_nueva <= (c.hora_cita + (COALESCE(c.duracion_minutos, 30) || ' minutes')::INTERVAL))
      );

    IF v_citas_conflicto > 0 THEN
        RAISE EXCEPTION 'Conflicto de agenda: El médico ya cuenta con una cita programada en ese horario.';
    END IF;

    RETURN NEW;
END;
$$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: auditoria; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.auditoria (
    id_auditoria bigint NOT NULL,
    id_usuario bigint,
    accion character varying(100) NOT NULL,
    tabla_afectada character varying(100) NOT NULL,
    id_registro_afectado bigint,
    detalle jsonb,
    fecha_hora timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.auditoria ALTER COLUMN id_auditoria ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.auditoria_id_auditoria_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: cita_medica; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cita_medica (
    id_cita bigint NOT NULL,
    codigo_unico uuid DEFAULT gen_random_uuid() NOT NULL,
    id_paciente bigint NOT NULL,
    id_medico bigint NOT NULL,
    id_especialidad bigint NOT NULL,
    id_estado_cita bigint NOT NULL,
    fecha_cita date NOT NULL,
    hora_cita time without time zone NOT NULL,
    duracion_minutos smallint DEFAULT 30,
    motivo text,
    observacion_atencion text,
    motivo_cancelacion text,
    fecha_hora_llegada timestamp without time zone
);


--
-- Name: cita_medica_id_cita_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.cita_medica ALTER COLUMN id_cita ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.cita_medica_id_cita_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: citas_medicas; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.citas_medicas AS
 SELECT id_cita,
    codigo_unico,
    id_paciente,
    id_medico,
    id_especialidad,
    id_estado_cita,
    fecha_cita,
    hora_cita,
    duracion_minutos,
    motivo,
    observacion_atencion,
    motivo_cancelacion,
    fecha_hora_llegada
   FROM public.cita_medica;


--
-- Name: especialidad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.especialidad (
    id_especialidad bigint NOT NULL,
    nombre_especialidad character varying(100) NOT NULL,
    descripcion character varying(255),
    estado boolean DEFAULT true NOT NULL
);


--
-- Name: especialidad_id_especialidad_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.especialidad ALTER COLUMN id_especialidad ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.especialidad_id_especialidad_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: especialidades; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.especialidades AS
 SELECT id_especialidad,
    nombre_especialidad,
    descripcion,
    estado
   FROM public.especialidad;


--
-- Name: estado_cita; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.estado_cita (
    id_estado_cita bigint NOT NULL,
    nombre_estado character varying(50) NOT NULL,
    descripcion character varying(255)
);


--
-- Name: estado_cita_id_estado_cita_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.estado_cita ALTER COLUMN id_estado_cita ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.estado_cita_id_estado_cita_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: estados_cita; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.estados_cita AS
 SELECT id_estado_cita,
    nombre_estado,
    descripcion
   FROM public.estado_cita;


--
-- Name: historial_cambio_cita; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.historial_cambio_cita (
    id_historial bigint NOT NULL,
    id_cita bigint NOT NULL,
    id_usuario bigint,
    estado_anterior bigint,
    estado_nuevo bigint,
    tipo_cambio character varying(100),
    observacion text,
    fecha_hora_anterior timestamp without time zone,
    fecha_hora_nueva timestamp without time zone,
    fecha_modificacion timestamp without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: historial_cambio_cita_id_historial_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.historial_cambio_cita ALTER COLUMN id_historial ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.historial_cambio_cita_id_historial_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: historial_cambios_cita; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.historial_cambios_cita AS
 SELECT id_historial,
    id_cita,
    id_usuario,
    estado_anterior,
    estado_nuevo,
    tipo_cambio,
    observacion,
    fecha_hora_anterior,
    fecha_hora_nueva,
    fecha_modificacion
   FROM public.historial_cambio_cita;


--
-- Name: horario_atencion; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.horario_atencion (
    id_horario bigint NOT NULL,
    id_medico bigint NOT NULL,
    dia_semana smallint NOT NULL,
    hora_inicio time without time zone NOT NULL,
    hora_fin time without time zone NOT NULL,
    duracion_cita_minutos smallint NOT NULL,
    CONSTRAINT chk_rango_horario CHECK ((hora_fin > hora_inicio)),
    CONSTRAINT horario_atencion_dia_semana_check CHECK (((dia_semana >= 1) AND (dia_semana <= 7)))
);


--
-- Name: horario_atencion_id_horario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.horario_atencion ALTER COLUMN id_horario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.horario_atencion_id_horario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: horarios_atencion; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.horarios_atencion AS
 SELECT id_horario,
    id_medico,
    dia_semana,
    hora_inicio,
    hora_fin,
    duracion_cita_minutos
   FROM public.horario_atencion;


--
-- Name: medico; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.medico (
    id_medico bigint NOT NULL,
    nombre_completo character varying(150) NOT NULL,
    correo character varying(150),
    telefono character varying(30),
    registro_profesional character varying(80) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


--
-- Name: medico_especialidad; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.medico_especialidad (
    id_medico bigint NOT NULL,
    id_especialidad bigint NOT NULL
);


--
-- Name: medico_id_medico_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.medico ALTER COLUMN id_medico ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.medico_id_medico_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: medicos; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.medicos AS
 SELECT id_medico,
    nombre_completo,
    correo,
    telefono,
    registro_profesional,
    estado
   FROM public.medico;


--
-- Name: medicos_especialidades; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.medicos_especialidades AS
 SELECT id_medico,
    id_especialidad
   FROM public.medico_especialidad;


--
-- Name: paciente; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.paciente (
    id_paciente bigint NOT NULL,
    nombre_completo character varying(150) NOT NULL,
    ci character varying(25) NOT NULL,
    fecha_nacimiento date NOT NULL,
    genero character varying(20),
    telefono character varying(30),
    telefono_emergencia character varying(30),
    contacto_emergencia character varying(150),
    correo character varying(150),
    direccion_domicilio character varying(255),
    alergias character varying(255),
    informacion_medica_basica text,
    tipo_paciente character varying(50),
    estado boolean DEFAULT true NOT NULL
);


--
-- Name: paciente_id_paciente_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.paciente ALTER COLUMN id_paciente ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.paciente_id_paciente_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: pacientes; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.pacientes AS
 SELECT id_paciente,
    nombre_completo,
    ci,
    fecha_nacimiento,
    genero,
    telefono,
    telefono_emergencia,
    contacto_emergencia,
    correo,
    direccion_domicilio,
    alergias,
    informacion_medica_basica,
    tipo_paciente,
    estado
   FROM public.paciente;


--
-- Name: rol; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.rol (
    id_rol bigint NOT NULL,
    nombre_rol character varying(100) NOT NULL,
    descripcion character varying(255)
);


--
-- Name: rol_id_rol_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.rol ALTER COLUMN id_rol ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.rol_id_rol_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: roles; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.roles AS
 SELECT id_rol,
    nombre_rol,
    descripcion
   FROM public.rol;


--
-- Name: usuario; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.usuario (
    id_usuario bigint NOT NULL,
    id_rol bigint NOT NULL,
    id_medico bigint,
    id_paciente bigint,
    nombre_usuario character varying(100) NOT NULL,
    correo character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    estado boolean DEFAULT true NOT NULL
);


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE; Schema: public; Owner: -
--

ALTER TABLE public.usuario ALTER COLUMN id_usuario ADD GENERATED ALWAYS AS IDENTITY (
    SEQUENCE NAME public.usuario_id_usuario_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1
);


--
-- Name: usuarios; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.usuarios AS
 SELECT id_usuario,
    id_rol,
    id_medico,
    id_paciente,
    nombre_usuario,
    correo,
    password_hash,
    estado
   FROM public.usuario;


--
-- Data for Name: auditoria; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.auditoria (id_auditoria, id_usuario, accion, tabla_afectada, id_registro_afectado, detalle, fecha_hora) FROM stdin;
1	2	INSERT	paciente	1	{"ci": "6829104 SC", "operacion": "Registro de nuevo paciente"}	2026-10-25 08:30:00
2	2	INSERT	paciente	2	{"ci": "5341209 SC", "operacion": "Registro de nuevo paciente"}	2026-10-25 09:15:00
3	2	INSERT	paciente	3	{"ci": "4845122 SC", "operacion": "Registro de nuevo paciente"}	2026-10-25 10:40:00
4	2	INSERT	paciente	4	{"ci": "6214223 SC", "operacion": "Registro de nuevo paciente"}	2026-10-26 11:00:00
5	2	INSERT	paciente	5	{"ci": "7819234 SC", "operacion": "Registro de nuevo paciente"}	2026-10-26 14:20:00
6	16	INSERT	usuario	16	{"operacion": "Auto-registro exitoso de cuenta paciente (CU9)"}	2026-10-27 10:05:00
7	17	INSERT	usuario	17	{"operacion": "Auto-registro exitoso de cuenta paciente (CU9)"}	2026-10-27 11:30:00
8	18	INSERT	usuario	18	{"operacion": "Auto-registro exitoso de cuenta paciente (CU9)"}	2026-10-27 15:45:00
9	19	INSERT	usuario	19	{"operacion": "Auto-registro exitoso de cuenta paciente (CU9)"}	2026-10-28 09:12:00
10	20	INSERT	usuario	20	{"operacion": "Auto-registro exitoso de cuenta paciente (CU9)"}	2026-10-28 14:50:00
11	2	INSERT	cita_medica	1	{"medico": "Dr. Roberto Carlos Flores", "operacion": "Programar cita (CU11)"}	2026-10-29 08:45:00
12	2	INSERT	cita_medica	2	{"medico": "Dra. Patricia Vaca Diez", "operacion": "Programar cita (CU11)"}	2026-10-29 09:15:00
13	2	INSERT	cita_medica	3	{"medico": "Dr. Javier Gonzales Soria", "operacion": "Programar cita (CU11)"}	2026-10-29 10:30:00
14	2	INSERT	cita_medica	4	{"medico": "Dr. Mario Gutiérrez Peña", "operacion": "Programar cita (CU11)"}	2026-10-29 11:45:00
15	2	INSERT	cita_medica	5	{"medico": "Dra. Ana Lucía Mendoza", "operacion": "Programar cita (CU11)"}	2026-10-30 08:20:00
16	2	UPDATE	cita_medica	1	{"operacion": "Confirmar cita (CU14)", "estado_nuevo": "Confirmada"}	2026-11-01 10:15:00
17	2	UPDATE	cita_medica	1	{"operacion": "Registrar llegada a sala (CU14)"}	2026-11-02 07:50:00
18	4	UPDATE	cita_medica	1	{"operacion": "Registrar cita como atendida (CU15)"}	2026-11-02 08:30:00
19	2	UPDATE	cita_medica	2	{"operacion": "Confirmar cita (CU14)", "estado_nuevo": "Confirmada"}	2026-11-04 11:20:00
20	6	UPDATE	cita_medica	2	{"operacion": "Registrar cita como atendida (CU15)"}	2026-11-05 09:30:00
21	2	UPDATE	cita_medica	16	{"motivo": "Conflicto laboral", "operacion": "Cancelar cita (CU13)"}	2026-11-03 18:00:00
22	2	UPDATE	cita_medica	17	{"motivo": "Bloqueo vecinal", "operacion": "Cancelar cita (CU13)"}	2026-11-03 08:30:00
23	2	UPDATE	cita_medica	18	{"motivo": "Viaje a Montero", "operacion": "Cancelar cita (CU13)"}	2026-11-03 14:15:00
24	2	UPDATE	cita_medica	19	{"motivo": "Cancelación solicitada", "operacion": "Cancelar cita (CU13)"}	2026-11-04 16:20:00
25	2	UPDATE	cita_medica	20	{"motivo": "Cruce de horario", "operacion": "Cancelar cita (CU13)"}	2026-11-03 12:00:00
26	2	UPDATE	cita_medica	21	{"operacion": "Declarar inasistencia (CU14)"}	2026-11-05 14:40:00
27	2	UPDATE	cita_medica	22	{"operacion": "Declarar inasistencia (CU14)"}	2026-11-06 16:05:00
28	2	UPDATE	cita_medica	23	{"operacion": "Declarar inasistencia (CU14)"}	2026-11-05 10:20:00
29	2	UPDATE	cita_medica	24	{"operacion": "Declarar inasistencia (CU14)"}	2026-11-05 14:35:00
30	2	UPDATE	cita_medica	25	{"operacion": "Declarar inasistencia (CU14)"}	2026-11-05 10:35:00
31	16	UPDATE	paciente	1	{"telefono": "77123450", "operacion": "Actualización de perfil paciente (CU8)"}	2026-11-06 11:15:00
32	1	SELECT	cita_medica	\N	{"operacion": "Generación de reporte citas por especialidad (CU19)"}	2026-11-07 09:30:00
33	1	SELECT	cita_medica	\N	{"operacion": "Generación de métricas e índice de inasistencias (CU19)"}	2026-11-07 10:15:00
34	2	SELECT	cita_medica	\N	{"operacion": "Consulta de agenda médica general (CU16)"}	2026-11-07 11:00:00
35	2	SELECT	cita_medica	\N	{"operacion": "Auditoría de historial de citas por paciente (CU17)"}	2026-11-07 14:30:00
36	1	LOGIN	usuarios	1	{"ip": "190.129.199.17", "rol": "Administrador"}	2026-11-12 19:34:44.813793
37	1	LOGOUT	usuarios	1	{"ip": "190.129.199.17"}	2026-11-12 19:35:17.706919
38	4	LOGIN	usuarios	4	{"ip": "190.129.199.17", "rol": "Médico"}	2026-11-12 19:35:24.914311
197	1	INSERT_CITA	cita_medica	76	{"origen": "POBLACION_CLINICA"}	2026-10-28 09:30:00
198	1	INSERT_CITA	cita_medica	77	{"origen": "POBLACION_CLINICA"}	2026-10-28 15:30:00
199	1	INSERT_CITA	cita_medica	78	{"origen": "POBLACION_CLINICA"}	2026-10-29 16:30:00
79	\N	INSERT	paciente	76	{"ci": "9812401", "correo": "lucas.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 71092831", "id_paciente": 76, "tipo_paciente": "General", "nombre_completo": "Lucas Mateo Banegas Vaca", "fecha_nacimiento": "2021-04-12", "contacto_emergencia": "Mariana Vaca (Madre)", "direccion_domicilio": "Av. Banzer 6to Anillo, Condominio Sevilla", "telefono_emergencia": "+591 71092830", "informacion_medica_basica": "Controles de crecimiento al día. Vacunas completas."}	2026-11-12 20:03:00.288435
80	\N	INSERT	paciente	77	{"ci": "9934102", "correo": "valentina.ribera@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Alérgica a Penicilina", "telefono": "+591 72183940", "id_paciente": 77, "tipo_paciente": "Asegurado", "nombre_completo": "Valentina Sofia Ribera Justiniano", "fecha_nacimiento": "2018-09-25", "contacto_emergencia": "Carlos Ribera (Padre)", "direccion_domicilio": "Barrio Equipetrol Norte, Calle 9", "telefono_emergencia": "+591 72183941", "informacion_medica_basica": "Antecedente de bronquiolitis a los 2 años."}	2026-11-12 20:03:00.288435
81	\N	INSERT	paciente	78	{"ci": "8923411", "correo": "joaquin.saucedo@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 73194051", "id_paciente": 78, "tipo_paciente": "VIP", "nombre_completo": "Joaquín Andrés Saucedo Roca", "fecha_nacimiento": "2015-01-14", "contacto_emergencia": "Patricia Roca (Madre)", "direccion_domicilio": "Av. San Martín, Condominio El Bosque", "telefono_emergencia": "+591 73194050", "informacion_medica_basica": "Rinitis alérgica estacional."}	2026-11-12 20:03:00.288435
82	\N	INSERT	paciente	79	{"ci": "8821903", "correo": "camila.melgar@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Intolerancia a la lactosa", "telefono": "+591 74102938", "id_paciente": 79, "tipo_paciente": "Convenio", "nombre_completo": "Camila Belén Melgar Antelo", "fecha_nacimiento": "2012-07-30", "contacto_emergencia": "Sofía Antelo (Madre)", "direccion_domicilio": "Barrio Las Palmas, C. Los Mangales #140", "telefono_emergencia": "+591 74102937", "informacion_medica_basica": "Desarrollo pondoestatural adecuado."}	2026-11-12 20:03:00.288435
200	1	INSERT_CITA	cita_medica	79	{"origen": "POBLACION_CLINICA"}	2026-10-29 16:00:00
201	1	INSERT_CITA	cita_medica	80	{"origen": "POBLACION_CLINICA"}	2026-10-30 17:30:00
202	1	INSERT_CITA	cita_medica	81	{"origen": "POBLACION_CLINICA"}	2026-10-30 16:00:00
203	1	INSERT_CITA	cita_medica	82	{"origen": "POBLACION_CLINICA"}	2026-11-02 16:30:00
83	\N	INSERT	paciente	80	{"ci": "8712394", "correo": "nicolas.aguilera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Alergia a mariscos", "telefono": "+591 75192834", "id_paciente": 80, "tipo_paciente": "General", "nombre_completo": "Nicolás Eduardo Aguilera Peña", "fecha_nacimiento": "2009-11-08", "contacto_emergencia": "Eduardo Aguilera (Padre)", "direccion_domicilio": "Av. Roca y Coronado, Barrio Hamacas", "telefono_emergencia": "+591 75192833", "informacion_medica_basica": "Práctica fútbol juvenil competitivo."}	2026-11-12 20:03:00.288435
84	\N	INSERT	paciente	81	{"ci": "7129841", "correo": "matias.paz@outlook.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76281920", "id_paciente": 81, "tipo_paciente": "Asegurado", "nombre_completo": "Matías Sebastián Paz Soldán", "fecha_nacimiento": "2002-03-19", "contacto_emergencia": "Lucía Paz (Hermana)", "direccion_domicilio": "Av. Cristo Redentor, Calle Los Tajibos #55", "telefono_emergencia": "+591 76281929", "informacion_medica_basica": "Evaluaciones cardiológicas deportivas."}	2026-11-12 20:03:00.288435
85	\N	INSERT	paciente	82	{"ci": "6819203", "correo": "adriana.hurtado@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Sulfas", "telefono": "+591 77391029", "id_paciente": 82, "tipo_paciente": "VIP", "nombre_completo": "Adriana Nicole Hurtado Suárez", "fecha_nacimiento": "1998-08-14", "contacto_emergencia": "Marcos Hurtado (Esposo)", "direccion_domicilio": "Condominio Las Brisas dpto 8B", "telefono_emergencia": "+591 77391028", "informacion_medica_basica": "Control ginecológico y reproductivo anual."}	2026-11-12 20:03:00.288435
86	\N	INSERT	paciente	83	{"ci": "6291823", "correo": "leonardo.justiniano@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78401928", "id_paciente": 83, "tipo_paciente": "General", "nombre_completo": "Leonardo Favio Justiniano Dorado", "fecha_nacimiento": "1994-12-03", "contacto_emergencia": "Gloria Dorado (Madre)", "direccion_domicilio": "Barrio Urbari, C. Los Pinos #78", "telefono_emergencia": "+591 78401927", "informacion_medica_basica": "Hipermetropía corregida con lentes."}	2026-11-12 20:03:00.288435
87	\N	INSERT	paciente	84	{"ci": "5918204", "correo": "paola.monasterio@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Alergia al polen", "telefono": "+591 79512039", "id_paciente": 84, "tipo_paciente": "Convenio", "nombre_completo": "Paola Andrea Monasterio Roca", "fecha_nacimiento": "1991-05-22", "contacto_emergencia": "Javier Monasterio (Hermano)", "direccion_domicilio": "Av. Beni entre 3er y 4to Anillo", "telefono_emergencia": "+591 79512038", "informacion_medica_basica": "Migraña episódica sin aura."}	2026-11-12 20:03:00.288435
88	\N	INSERT	paciente	85	{"ci": "5728190", "correo": "gabriel.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 70623910", "id_paciente": 85, "tipo_paciente": "General", "nombre_completo": "Gabriel Antonio Antelo Méndez", "fecha_nacimiento": "1989-10-11", "contacto_emergencia": "Verónica Méndez (Esposa)", "direccion_domicilio": "Plan 3000, Barrio Guapilo", "telefono_emergencia": "+591 70623911", "informacion_medica_basica": "Sin patologías crónicas conocidas."}	2026-11-12 20:03:00.288435
89	\N	INSERT	paciente	86	{"ci": "4829103", "correo": "mirtha.vacadiez@gmail.com", "estado": true, "genero": "Femenino", "alergias": "AINEs (Ibuprofeno)", "telefono": "+591 71738291", "id_paciente": 86, "tipo_paciente": "Asegurado", "nombre_completo": "Mirtha Rossana Vaca Diez Pinto", "fecha_nacimiento": "1982-02-17", "contacto_emergencia": "Roberto Pinto (Esposo)", "direccion_domicilio": "Av. Busch esq. 3er Anillo Interno", "telefono_emergencia": "+591 71738292", "informacion_medica_basica": "Hipotiroidismo primario tratado con Levotiroxina 75mcg."}	2026-11-12 20:03:00.288435
90	\N	INSERT	paciente	87	{"ci": "4519283", "correo": "juanpablo.banegas@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72849102", "id_paciente": 87, "tipo_paciente": "VIP", "nombre_completo": "Juan Pablo Banegas Justiniano", "fecha_nacimiento": "1979-06-29", "contacto_emergencia": "Claudia Banegas (Hija)", "direccion_domicilio": "Barrio Sirari, C. Cañada Strongest #33", "telefono_emergencia": "+591 72849103", "informacion_medica_basica": "Hipertensión arterial Grado 1 en tratamiento."}	2026-11-12 20:03:00.288435
91	\N	INSERT	paciente	88	{"ci": "4291820", "correo": "silvia.cuellar@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 73950213", "id_paciente": 88, "tipo_paciente": "General", "nombre_completo": "Silvia Marcela Cuéllar Chávez", "fecha_nacimiento": "1976-09-08", "contacto_emergencia": "Ernesto Cuéllar (Hermano)", "direccion_domicilio": "Villa 1ro de Mayo, Calle 4 Norte #120", "telefono_emergencia": "+591 73950214", "informacion_medica_basica": "Dislipidemia mixta controlada con dieta y Atorvastatina."}	2026-11-12 20:03:00.288435
92	\N	INSERT	paciente	89	{"ci": "3918204", "correo": "jorge.morales@yahoo.com", "estado": true, "genero": "Masculino", "alergias": "Penicilina", "telefono": "+591 74061324", "id_paciente": 89, "tipo_paciente": "Convenio", "nombre_completo": "Jorge Luis Morales Soria", "fecha_nacimiento": "1973-11-24", "contacto_emergencia": "Raquel Morales (Esposa)", "direccion_domicilio": "Av. Grigotá, C. Los Cusis #90", "telefono_emergencia": "+591 74061325", "informacion_medica_basica": "Gastritis crónica antral por H. pylori tratada."}	2026-11-12 20:03:00.288435
93	\N	INSERT	paciente	90	{"ci": "3719284", "correo": "carmen.salvatierra@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Dipirona", "telefono": "+591 75172435", "id_paciente": 90, "tipo_paciente": "VIP", "nombre_completo": "Carmen Rosa Salvatierra Roca", "fecha_nacimiento": "1970-04-15", "contacto_emergencia": "Daniel Salvatierra (Hijo)", "direccion_domicilio": "Condominio Portal del Urubó casa 22", "telefono_emergencia": "+591 75172436", "informacion_medica_basica": "Colecistectomía laparoscópica en 2021."}	2026-11-12 20:03:00.288435
94	\N	INSERT	paciente	91	{"ci": "2918203", "correo": "walter.ribera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76283546", "id_paciente": 91, "tipo_paciente": "Asegurado", "nombre_completo": "Walter Humberto Ribera Melgar", "fecha_nacimiento": "1960-01-10", "contacto_emergencia": "Sonia Ribera (Esposa)", "direccion_domicilio": "Av. Mutualista 3er Anillo Externo", "telefono_emergencia": "+591 76283547", "informacion_medica_basica": "Diabetes Mellitus Tipo 2, cardiopatía isquémica controlada."}	2026-11-12 20:03:00.288435
95	\N	INSERT	paciente	92	{"ci": "2481920", "correo": "guillermina.suarez@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Penicilina", "telefono": "+591 77394657", "id_paciente": 92, "tipo_paciente": "General", "nombre_completo": "Guillermina Suárez vda. de Roca", "fecha_nacimiento": "1955-08-03", "contacto_emergencia": "Gustavo Roca (Hijo)", "direccion_domicilio": "Av. Irala #412", "telefono_emergencia": "+591 77394658", "informacion_medica_basica": "Osteoartrosis de rodilla bilateral, HTA esencial."}	2026-11-12 20:03:00.288435
204	1	INSERT_CITA	cita_medica	83	{"origen": "POBLACION_CLINICA"}	2026-11-02 17:30:00
205	1	INSERT_CITA	cita_medica	84	{"origen": "POBLACION_CLINICA"}	2026-11-03 17:30:00
206	1	INSERT_CITA	cita_medica	85	{"origen": "POBLACION_CLINICA"}	2026-11-03 08:30:00
96	\N	INSERT	paciente	93	{"ci": "2198204", "correo": "alcides.flores@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78405768", "id_paciente": 93, "tipo_paciente": "Convenio", "nombre_completo": "Alcides Fernando Flores Vaca", "fecha_nacimiento": "1951-12-19", "contacto_emergencia": "Rosa Flores (Hija)", "direccion_domicilio": "Barrio Santa Rosita, Calle 3", "telefono_emergencia": "+591 78405769", "informacion_medica_basica": "Hiperplasia prostática benigna, control urológico regular."}	2026-11-12 20:03:00.288435
97	\N	INSERT	paciente	94	{"ci": "1982039", "correo": "gladys.ortiz@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Aspirina", "telefono": "+591 79516879", "id_paciente": 94, "tipo_paciente": "VIP", "nombre_completo": "Gladys Teresa Ortiz Aguilera", "fecha_nacimiento": "1948-05-27", "contacto_emergencia": "Mariana Ortiz (Nieta)", "direccion_domicilio": "Av. Cristóbal de Mendoza #615", "telefono_emergencia": "+591 79516880", "informacion_medica_basica": "Fibrilación auricular no valvular anticoagulada."}	2026-11-12 20:03:00.288435
98	\N	INSERT	paciente	95	{"ci": "1728190", "correo": "ramiro.mendoza@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 70627980", "id_paciente": 95, "tipo_paciente": "Asegurado", "nombre_completo": "Ramiro Gonzalo Mendoza Justiniano", "fecha_nacimiento": "1945-10-02", "contacto_emergencia": "Pedro Mendoza (Hijo)", "direccion_domicilio": "Calle Warnes esq. Potosí #88", "telefono_emergencia": "+591 70627981", "informacion_medica_basica": "Enfermedad pulmonar obstructiva crónica (EPOC) leve."}	2026-11-12 20:03:00.288435
99	\N	INSERT	paciente	96	{"ci": "6192840", "correo": "tania.soria@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Mariscos y Yodo", "telefono": "+591 71839012", "id_paciente": 96, "tipo_paciente": "General", "nombre_completo": "Tania Gisela Soria Galvarro", "fecha_nacimiento": "1987-03-11", "contacto_emergencia": "Fabiola Soria (Hermana)", "direccion_domicilio": "Barrio Los Chacos, C. Las Palmeras", "telefono_emergencia": "+591 71839013", "informacion_medica_basica": "Dermatitis atópica crónica."}	2026-11-12 20:03:00.288435
100	\N	INSERT	paciente	97	{"ci": "5829104", "correo": "victor.mercado@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72940123", "id_paciente": 97, "tipo_paciente": "Asegurado", "nombre_completo": "Víctor Hugo Mercado Banegas", "fecha_nacimiento": "1984-09-21", "contacto_emergencia": "Verónica Mercado (Esposa)", "direccion_domicilio": "Condominio Ciudad Jardín dpto 302", "telefono_emergencia": "+591 72940124", "informacion_medica_basica": "Hernia discal L4-L5 no quirúrgica."}	2026-11-12 20:03:00.288435
101	\N	INSERT	paciente	98	{"ci": "5291830", "correo": "fabiola.dorado@outlook.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 73051234", "id_paciente": 98, "tipo_paciente": "VIP", "nombre_completo": "Fabiola Patricia Dorado Melgar", "fecha_nacimiento": "1992-11-04", "contacto_emergencia": "Mauricio Dorado (Hermano)", "direccion_domicilio": "Av. San Aurelio 4to Anillo", "telefono_emergencia": "+591 73051235", "informacion_medica_basica": "Embarazo de 28 semanas de evolución normal."}	2026-11-12 20:03:00.288435
102	\N	INSERT	paciente	99	{"ci": "4918205", "correo": "alvaro.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Sulfamidas", "telefono": "+591 74162345", "id_paciente": 99, "tipo_paciente": "Convenio", "nombre_completo": "Álvaro René Banegas Aguilera", "fecha_nacimiento": "1980-07-16", "contacto_emergencia": "Cecilia Banegas (Esposa)", "direccion_domicilio": "Av. Santos Dumont #1024", "telefono_emergencia": "+591 74162346", "informacion_medica_basica": "Hipercolesterolemia familiar."}	2026-11-12 20:03:00.288435
103	\N	INSERT	paciente	100	{"ci": "4381920", "correo": "lourdes.justiniano@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 75273456", "id_paciente": 100, "tipo_paciente": "General", "nombre_completo": "Lourdes Marina Justiniano Roca", "fecha_nacimiento": "1977-02-28", "contacto_emergencia": "Karina Justiniano (Hija)", "direccion_domicilio": "Barrio Ramafa, C. Cañoto #15", "telefono_emergencia": "+591 75273457", "informacion_medica_basica": "Gastritis erosiva superficial."}	2026-11-12 20:03:00.288435
104	\N	INSERT	paciente	101	{"ci": "7918204", "correo": "esteban.pinto@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76384567", "id_paciente": 101, "tipo_paciente": "General", "nombre_completo": "Esteban David Pinto Saucedo", "fecha_nacimiento": "2000-12-14", "contacto_emergencia": "David Pinto (Padre)", "direccion_domicilio": "Av. Piraí entre 2do y 3er Anillo", "telefono_emergencia": "+591 76384568", "informacion_medica_basica": "Chequeo médico universitario anual."}	2026-11-12 20:03:00.288435
105	\N	INSERT	paciente	102	{"ci": "7481920", "correo": "valeria.vaca@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Cefalosporinas", "telefono": "+591 77495678", "id_paciente": 102, "tipo_paciente": "VIP", "nombre_completo": "Valeria Inés Vaca Antelo", "fecha_nacimiento": "1997-04-09", "contacto_emergencia": "Lucía Antelo (Madre)", "direccion_domicilio": "Av. Monseñor Rivero #320", "telefono_emergencia": "+591 77495679", "informacion_medica_basica": "Acné inflamatorio grado II."}	2026-11-12 20:03:00.288435
106	\N	INSERT	paciente	103	{"ci": "6918203", "correo": "rene.melgar@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78506789", "id_paciente": 103, "tipo_paciente": "Asegurado", "nombre_completo": "René Alejandro Melgar Justiniano", "fecha_nacimiento": "1993-01-23", "contacto_emergencia": "Beatriz Melgar (Esposa)", "direccion_domicilio": "Condominio La Casona casa 4", "telefono_emergencia": "+591 78506780", "informacion_medica_basica": "Esguince de tobillo derecho en rehabilitación."}	2026-11-12 20:03:00.288435
107	\N	INSERT	paciente	104	{"ci": "6381920", "correo": "carla.aguilera@yahoo.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 79617890", "id_paciente": 104, "tipo_paciente": "General", "nombre_completo": "Carla Jimena Aguilera Paz", "fecha_nacimiento": "1990-08-17", "contacto_emergencia": "Gonzalo Paz (Padre)", "direccion_domicilio": "Av. Alemania 5to Anillo", "telefono_emergencia": "+591 79617891", "informacion_medica_basica": "Migrañas catameniales periódicas."}	2026-11-12 20:03:00.288435
108	\N	INSERT	paciente	105	{"ci": "5619283", "correo": "gustavo.ribera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Metamizol", "telefono": "+591 70728901", "id_paciente": 105, "tipo_paciente": "Convenio", "nombre_completo": "Gustavo Adolfo Ribera Soria", "fecha_nacimiento": "1986-06-05", "contacto_emergencia": "Elena Ribera (Hermana)", "direccion_domicilio": "Av. 2 de Agosto #880", "telefono_emergencia": "+591 70728902", "informacion_medica_basica": "Sobrepeso grado 1, consulta nutricional."}	2026-11-12 20:03:00.288435
109	\N	INSERT	paciente	106	{"ci": "5129840", "correo": "daniela.suarez@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 71839023", "id_paciente": 106, "tipo_paciente": "VIP", "nombre_completo": "Daniela Elizabeth Suárez Vaca", "fecha_nacimiento": "1983-10-12", "contacto_emergencia": "Martín Suárez (Esposo)", "direccion_domicilio": "Av. Virgen de Cotoca 3er Anillo", "telefono_emergencia": "+591 71839024", "informacion_medica_basica": "Control mamográfico preventivo birads 1."}	2026-11-12 20:03:00.288435
110	\N	INSERT	paciente	107	{"ci": "4719283", "correo": "oscar.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72940134", "id_paciente": 107, "tipo_paciente": "Asegurado", "nombre_completo": "Óscar Manuel Antelo Banegas", "fecha_nacimiento": "1978-03-30", "contacto_emergencia": "Susana Antelo (Esposa)", "direccion_domicilio": "Barrio La Morita, C. Los Helechos", "telefono_emergencia": "+591 72940135", "informacion_medica_basica": "Insuficiencia venosa periférica bilateral."}	2026-11-12 20:03:00.288435
111	\N	INSERT	paciente	108	{"ci": "4192840", "correo": "martha.roca@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Eritromicina", "telefono": "+591 73051245", "id_paciente": 108, "tipo_paciente": "General", "nombre_completo": "Martha Cecilia Roca Melgar", "fecha_nacimiento": "1974-12-01", "contacto_emergencia": "Raúl Roca (Hijo)", "direccion_domicilio": "Av. Tres Pasos al Frente #540", "telefono_emergencia": "+591 73051246", "informacion_medica_basica": "Artrosis de manos y nódulos de Heberden."}	2026-11-12 20:03:00.288435
112	\N	INSERT	paciente	109	{"ci": "3819204", "correo": "jaime.saucedo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 74162356", "id_paciente": 109, "tipo_paciente": "Convenio", "nombre_completo": "Jaime Eduardo Saucedo Justiniano", "fecha_nacimiento": "1971-07-07", "contacto_emergencia": "Gladys Saucedo (Esposa)", "direccion_domicilio": "Av. Cumavi, Barrio 10 de Octubre", "telefono_emergencia": "+591 74162357", "informacion_medica_basica": "Hipertrigliceridemia y esteatosis hepática leve."}	2026-11-12 20:03:00.288435
113	\N	INSERT	paciente	110	{"ci": "3291820", "correo": "beatriz.dorado@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Penicilina", "telefono": "+591 75273467", "id_paciente": 110, "tipo_paciente": "VIP", "nombre_completo": "Beatriz Eugenia Dorado Pinto", "fecha_nacimiento": "1966-09-18", "contacto_emergencia": "Ignacio Dorado (Hijo)", "direccion_domicilio": "Barrio Petrolero, C. Las Lilas #45", "telefono_emergencia": "+591 75273468", "informacion_medica_basica": "Osteoporosis densitométrica en tratamiento."}	2026-11-12 20:03:00.288435
114	\N	INSERT	paciente	111	{"ci": "2819204", "correo": "felix.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76384578", "id_paciente": 111, "tipo_paciente": "Asegurado", "nombre_completo": "Félix Rolando Banegas Ortiz", "fecha_nacimiento": "1961-05-14", "contacto_emergencia": "Hilda Banegas (Esposa)", "direccion_domicilio": "Av. San Martín 2do Anillo", "telefono_emergencia": "+591 76384579", "informacion_medica_basica": "Reemplazo total de cadera derecha en 2023."}	2026-11-12 20:03:00.288435
115	\N	INSERT	paciente	112	{"ci": "2391820", "correo": "rosaelva.justiniano@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Sulfas", "telefono": "+591 77495689", "id_paciente": 112, "tipo_paciente": "General", "nombre_completo": "Rosa Elva Justiniano vda. de Paz", "fecha_nacimiento": "1956-11-29", "contacto_emergencia": "Carolina Paz (Hija)", "direccion_domicilio": "C. Sucre #220", "telefono_emergencia": "+591 77495680", "informacion_medica_basica": "Glaucoma de ángulo abierto tratado con gotas."}	2026-11-12 20:03:00.288435
116	\N	INSERT	paciente	113	{"ci": "1829104", "correo": "armando.aguilera@yahoo.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78506790", "id_paciente": 113, "tipo_paciente": "VIP", "nombre_completo": "Armando César Aguilera Roca", "fecha_nacimiento": "1950-04-03", "contacto_emergencia": "César Aguilera (Hijo)", "direccion_domicilio": "Av. Cañoto #112", "telefono_emergencia": "+591 78506791", "informacion_medica_basica": "Secuela de infarto agudo de miocardio 2019 estable."}	2026-11-12 20:03:00.288435
117	\N	INSERT	paciente	114	{"ci": "1591820", "correo": "ines.soria@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 79617801", "id_paciente": 114, "tipo_paciente": "Asegurado", "nombre_completo": "Inés Magdalena Soria Melgar", "fecha_nacimiento": "1947-08-16", "contacto_emergencia": "Andrea Soria (Nieta)", "direccion_domicilio": "Av. Argandoña #85", "telefono_emergencia": "+591 79617802", "informacion_medica_basica": "Demencia vascular en fase inicial con cuidador."}	2026-11-12 20:03:00.288435
118	\N	INSERT	paciente	115	{"ci": "1291820", "correo": "julio.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Aspirina", "telefono": "+591 70728912", "id_paciente": 115, "tipo_paciente": "Convenio", "nombre_completo": "Julio César Antelo Vaca", "fecha_nacimiento": "1943-02-20", "contacto_emergencia": "Mariano Antelo (Hijo)", "direccion_domicilio": "Barrio El Trompillo, C. 5 #10", "telefono_emergencia": "+591 70728913", "informacion_medica_basica": "Portador de marcapasos bicameral definitivo."}	2026-11-12 20:03:00.288435
119	\N	UPDATE	paciente	76	{"nuevo": {"ci": "9812401", "correo": "lucas.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 71092831", "id_paciente": 76, "tipo_paciente": "General", "nombre_completo": "Lucas Mateo Banegas Vaca", "fecha_nacimiento": "2021-04-12", "contacto_emergencia": "Mariana Vaca (Madre)", "direccion_domicilio": "Av. Banzer 6to Anillo, Condominio Sevilla", "telefono_emergencia": "+591 71092830", "informacion_medica_basica": "Controles de crecimiento al día. Vacunas completas."}, "anterior": {"ci": "9812401", "correo": "lucas.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 71092831", "id_paciente": 76, "tipo_paciente": "General", "nombre_completo": "Lucas Mateo Banegas Vaca", "fecha_nacimiento": "2021-04-12", "contacto_emergencia": "Mariana Vaca (Madre)", "direccion_domicilio": "Av. Banzer 6to Anillo, Condominio Sevilla", "telefono_emergencia": "+591 71092830", "informacion_medica_basica": "Controles de crecimiento al día. Vacunas completas."}}	2026-11-12 20:04:35.547707
120	\N	UPDATE	paciente	77	{"nuevo": {"ci": "9934102", "correo": "valentina.ribera@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Alérgica a Penicilina", "telefono": "+591 72183940", "id_paciente": 77, "tipo_paciente": "Asegurado", "nombre_completo": "Valentina Sofia Ribera Justiniano", "fecha_nacimiento": "2018-09-25", "contacto_emergencia": "Carlos Ribera (Padre)", "direccion_domicilio": "Barrio Equipetrol Norte, Calle 9", "telefono_emergencia": "+591 72183941", "informacion_medica_basica": "Antecedente de bronquiolitis a los 2 años."}, "anterior": {"ci": "9934102", "correo": "valentina.ribera@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Alérgica a Penicilina", "telefono": "+591 72183940", "id_paciente": 77, "tipo_paciente": "Asegurado", "nombre_completo": "Valentina Sofia Ribera Justiniano", "fecha_nacimiento": "2018-09-25", "contacto_emergencia": "Carlos Ribera (Padre)", "direccion_domicilio": "Barrio Equipetrol Norte, Calle 9", "telefono_emergencia": "+591 72183941", "informacion_medica_basica": "Antecedente de bronquiolitis a los 2 años."}}	2026-11-12 20:04:35.547707
207	1	INSERT_CITA	cita_medica	86	{"origen": "POBLACION_CLINICA"}	2026-11-04 09:00:00
208	1	INSERT_CITA	cita_medica	87	{"origen": "POBLACION_CLINICA"}	2026-11-04 10:00:00
209	1	INSERT_CITA	cita_medica	88	{"origen": "POBLACION_CLINICA"}	2026-11-05 14:30:00
210	1	INSERT_CITA	cita_medica	89	{"origen": "POBLACION_CLINICA"}	2026-11-05 15:00:00
121	\N	UPDATE	paciente	78	{"nuevo": {"ci": "8923411", "correo": "joaquin.saucedo@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 73194051", "id_paciente": 78, "tipo_paciente": "VIP", "nombre_completo": "Joaquín Andrés Saucedo Roca", "fecha_nacimiento": "2015-01-14", "contacto_emergencia": "Patricia Roca (Madre)", "direccion_domicilio": "Av. San Martín, Condominio El Bosque", "telefono_emergencia": "+591 73194050", "informacion_medica_basica": "Rinitis alérgica estacional."}, "anterior": {"ci": "8923411", "correo": "joaquin.saucedo@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 73194051", "id_paciente": 78, "tipo_paciente": "VIP", "nombre_completo": "Joaquín Andrés Saucedo Roca", "fecha_nacimiento": "2015-01-14", "contacto_emergencia": "Patricia Roca (Madre)", "direccion_domicilio": "Av. San Martín, Condominio El Bosque", "telefono_emergencia": "+591 73194050", "informacion_medica_basica": "Rinitis alérgica estacional."}}	2026-11-12 20:04:35.547707
122	\N	UPDATE	paciente	79	{"nuevo": {"ci": "8821903", "correo": "camila.melgar@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Intolerancia a la lactosa", "telefono": "+591 74102938", "id_paciente": 79, "tipo_paciente": "Convenio", "nombre_completo": "Camila Belén Melgar Antelo", "fecha_nacimiento": "2012-07-30", "contacto_emergencia": "Sofía Antelo (Madre)", "direccion_domicilio": "Barrio Las Palmas, C. Los Mangales #140", "telefono_emergencia": "+591 74102937", "informacion_medica_basica": "Desarrollo pondoestatural adecuado."}, "anterior": {"ci": "8821903", "correo": "camila.melgar@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Intolerancia a la lactosa", "telefono": "+591 74102938", "id_paciente": 79, "tipo_paciente": "Convenio", "nombre_completo": "Camila Belén Melgar Antelo", "fecha_nacimiento": "2012-07-30", "contacto_emergencia": "Sofía Antelo (Madre)", "direccion_domicilio": "Barrio Las Palmas, C. Los Mangales #140", "telefono_emergencia": "+591 74102937", "informacion_medica_basica": "Desarrollo pondoestatural adecuado."}}	2026-11-12 20:04:35.547707
123	\N	UPDATE	paciente	80	{"nuevo": {"ci": "8712394", "correo": "nicolas.aguilera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Alergia a mariscos", "telefono": "+591 75192834", "id_paciente": 80, "tipo_paciente": "General", "nombre_completo": "Nicolás Eduardo Aguilera Peña", "fecha_nacimiento": "2009-11-08", "contacto_emergencia": "Eduardo Aguilera (Padre)", "direccion_domicilio": "Av. Roca y Coronado, Barrio Hamacas", "telefono_emergencia": "+591 75192833", "informacion_medica_basica": "Práctica fútbol juvenil competitivo."}, "anterior": {"ci": "8712394", "correo": "nicolas.aguilera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Alergia a mariscos", "telefono": "+591 75192834", "id_paciente": 80, "tipo_paciente": "General", "nombre_completo": "Nicolás Eduardo Aguilera Peña", "fecha_nacimiento": "2009-11-08", "contacto_emergencia": "Eduardo Aguilera (Padre)", "direccion_domicilio": "Av. Roca y Coronado, Barrio Hamacas", "telefono_emergencia": "+591 75192833", "informacion_medica_basica": "Práctica fútbol juvenil competitivo."}}	2026-11-12 20:04:35.547707
124	\N	UPDATE	paciente	81	{"nuevo": {"ci": "7129841", "correo": "matias.paz@outlook.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76281920", "id_paciente": 81, "tipo_paciente": "Asegurado", "nombre_completo": "Matías Sebastián Paz Soldán", "fecha_nacimiento": "2002-03-19", "contacto_emergencia": "Lucía Paz (Hermana)", "direccion_domicilio": "Av. Cristo Redentor, Calle Los Tajibos #55", "telefono_emergencia": "+591 76281929", "informacion_medica_basica": "Evaluaciones cardiológicas deportivas."}, "anterior": {"ci": "7129841", "correo": "matias.paz@outlook.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76281920", "id_paciente": 81, "tipo_paciente": "Asegurado", "nombre_completo": "Matías Sebastián Paz Soldán", "fecha_nacimiento": "2002-03-19", "contacto_emergencia": "Lucía Paz (Hermana)", "direccion_domicilio": "Av. Cristo Redentor, Calle Los Tajibos #55", "telefono_emergencia": "+591 76281929", "informacion_medica_basica": "Evaluaciones cardiológicas deportivas."}}	2026-11-12 20:04:35.547707
125	\N	UPDATE	paciente	82	{"nuevo": {"ci": "6819203", "correo": "adriana.hurtado@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Sulfas", "telefono": "+591 77391029", "id_paciente": 82, "tipo_paciente": "VIP", "nombre_completo": "Adriana Nicole Hurtado Suárez", "fecha_nacimiento": "1998-08-14", "contacto_emergencia": "Marcos Hurtado (Esposo)", "direccion_domicilio": "Condominio Las Brisas dpto 8B", "telefono_emergencia": "+591 77391028", "informacion_medica_basica": "Control ginecológico y reproductivo anual."}, "anterior": {"ci": "6819203", "correo": "adriana.hurtado@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Sulfas", "telefono": "+591 77391029", "id_paciente": 82, "tipo_paciente": "VIP", "nombre_completo": "Adriana Nicole Hurtado Suárez", "fecha_nacimiento": "1998-08-14", "contacto_emergencia": "Marcos Hurtado (Esposo)", "direccion_domicilio": "Condominio Las Brisas dpto 8B", "telefono_emergencia": "+591 77391028", "informacion_medica_basica": "Control ginecológico y reproductivo anual."}}	2026-11-12 20:04:35.547707
126	\N	UPDATE	paciente	83	{"nuevo": {"ci": "6291823", "correo": "leonardo.justiniano@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78401928", "id_paciente": 83, "tipo_paciente": "General", "nombre_completo": "Leonardo Favio Justiniano Dorado", "fecha_nacimiento": "1994-12-03", "contacto_emergencia": "Gloria Dorado (Madre)", "direccion_domicilio": "Barrio Urbari, C. Los Pinos #78", "telefono_emergencia": "+591 78401927", "informacion_medica_basica": "Hipermetropía corregida con lentes."}, "anterior": {"ci": "6291823", "correo": "leonardo.justiniano@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78401928", "id_paciente": 83, "tipo_paciente": "General", "nombre_completo": "Leonardo Favio Justiniano Dorado", "fecha_nacimiento": "1994-12-03", "contacto_emergencia": "Gloria Dorado (Madre)", "direccion_domicilio": "Barrio Urbari, C. Los Pinos #78", "telefono_emergencia": "+591 78401927", "informacion_medica_basica": "Hipermetropía corregida con lentes."}}	2026-11-12 20:04:35.547707
127	\N	UPDATE	paciente	84	{"nuevo": {"ci": "5918204", "correo": "paola.monasterio@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Alergia al polen", "telefono": "+591 79512039", "id_paciente": 84, "tipo_paciente": "Convenio", "nombre_completo": "Paola Andrea Monasterio Roca", "fecha_nacimiento": "1991-05-22", "contacto_emergencia": "Javier Monasterio (Hermano)", "direccion_domicilio": "Av. Beni entre 3er y 4to Anillo", "telefono_emergencia": "+591 79512038", "informacion_medica_basica": "Migraña episódica sin aura."}, "anterior": {"ci": "5918204", "correo": "paola.monasterio@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Alergia al polen", "telefono": "+591 79512039", "id_paciente": 84, "tipo_paciente": "Convenio", "nombre_completo": "Paola Andrea Monasterio Roca", "fecha_nacimiento": "1991-05-22", "contacto_emergencia": "Javier Monasterio (Hermano)", "direccion_domicilio": "Av. Beni entre 3er y 4to Anillo", "telefono_emergencia": "+591 79512038", "informacion_medica_basica": "Migraña episódica sin aura."}}	2026-11-12 20:04:35.547707
211	1	INSERT_CITA	cita_medica	90	{"origen": "POBLACION_CLINICA"}	2026-11-06 11:00:00
128	\N	UPDATE	paciente	85	{"nuevo": {"ci": "5728190", "correo": "gabriel.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 70623910", "id_paciente": 85, "tipo_paciente": "General", "nombre_completo": "Gabriel Antonio Antelo Méndez", "fecha_nacimiento": "1989-10-11", "contacto_emergencia": "Verónica Méndez (Esposa)", "direccion_domicilio": "Plan 3000, Barrio Guapilo", "telefono_emergencia": "+591 70623911", "informacion_medica_basica": "Sin patologías crónicas conocidas."}, "anterior": {"ci": "5728190", "correo": "gabriel.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 70623910", "id_paciente": 85, "tipo_paciente": "General", "nombre_completo": "Gabriel Antonio Antelo Méndez", "fecha_nacimiento": "1989-10-11", "contacto_emergencia": "Verónica Méndez (Esposa)", "direccion_domicilio": "Plan 3000, Barrio Guapilo", "telefono_emergencia": "+591 70623911", "informacion_medica_basica": "Sin patologías crónicas conocidas."}}	2026-11-12 20:04:35.547707
129	\N	UPDATE	paciente	86	{"nuevo": {"ci": "4829103", "correo": "mirtha.vacadiez@gmail.com", "estado": true, "genero": "Femenino", "alergias": "AINEs (Ibuprofeno)", "telefono": "+591 71738291", "id_paciente": 86, "tipo_paciente": "Asegurado", "nombre_completo": "Mirtha Rossana Vaca Diez Pinto", "fecha_nacimiento": "1982-02-17", "contacto_emergencia": "Roberto Pinto (Esposo)", "direccion_domicilio": "Av. Busch esq. 3er Anillo Interno", "telefono_emergencia": "+591 71738292", "informacion_medica_basica": "Hipotiroidismo primario tratado con Levotiroxina 75mcg."}, "anterior": {"ci": "4829103", "correo": "mirtha.vacadiez@gmail.com", "estado": true, "genero": "Femenino", "alergias": "AINEs (Ibuprofeno)", "telefono": "+591 71738291", "id_paciente": 86, "tipo_paciente": "Asegurado", "nombre_completo": "Mirtha Rossana Vaca Diez Pinto", "fecha_nacimiento": "1982-02-17", "contacto_emergencia": "Roberto Pinto (Esposo)", "direccion_domicilio": "Av. Busch esq. 3er Anillo Interno", "telefono_emergencia": "+591 71738292", "informacion_medica_basica": "Hipotiroidismo primario tratado con Levotiroxina 75mcg."}}	2026-11-12 20:04:35.547707
130	\N	UPDATE	paciente	87	{"nuevo": {"ci": "4519283", "correo": "juanpablo.banegas@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72849102", "id_paciente": 87, "tipo_paciente": "VIP", "nombre_completo": "Juan Pablo Banegas Justiniano", "fecha_nacimiento": "1979-06-29", "contacto_emergencia": "Claudia Banegas (Hija)", "direccion_domicilio": "Barrio Sirari, C. Cañada Strongest #33", "telefono_emergencia": "+591 72849103", "informacion_medica_basica": "Hipertensión arterial Grado 1 en tratamiento."}, "anterior": {"ci": "4519283", "correo": "juanpablo.banegas@hotmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72849102", "id_paciente": 87, "tipo_paciente": "VIP", "nombre_completo": "Juan Pablo Banegas Justiniano", "fecha_nacimiento": "1979-06-29", "contacto_emergencia": "Claudia Banegas (Hija)", "direccion_domicilio": "Barrio Sirari, C. Cañada Strongest #33", "telefono_emergencia": "+591 72849103", "informacion_medica_basica": "Hipertensión arterial Grado 1 en tratamiento."}}	2026-11-12 20:04:35.547707
131	\N	UPDATE	paciente	88	{"nuevo": {"ci": "4291820", "correo": "silvia.cuellar@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 73950213", "id_paciente": 88, "tipo_paciente": "General", "nombre_completo": "Silvia Marcela Cuéllar Chávez", "fecha_nacimiento": "1976-09-08", "contacto_emergencia": "Ernesto Cuéllar (Hermano)", "direccion_domicilio": "Villa 1ro de Mayo, Calle 4 Norte #120", "telefono_emergencia": "+591 73950214", "informacion_medica_basica": "Dislipidemia mixta controlada con dieta y Atorvastatina."}, "anterior": {"ci": "4291820", "correo": "silvia.cuellar@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 73950213", "id_paciente": 88, "tipo_paciente": "General", "nombre_completo": "Silvia Marcela Cuéllar Chávez", "fecha_nacimiento": "1976-09-08", "contacto_emergencia": "Ernesto Cuéllar (Hermano)", "direccion_domicilio": "Villa 1ro de Mayo, Calle 4 Norte #120", "telefono_emergencia": "+591 73950214", "informacion_medica_basica": "Dislipidemia mixta controlada con dieta y Atorvastatina."}}	2026-11-12 20:04:35.547707
132	\N	UPDATE	paciente	89	{"nuevo": {"ci": "3918204", "correo": "jorge.morales@yahoo.com", "estado": true, "genero": "Masculino", "alergias": "Penicilina", "telefono": "+591 74061324", "id_paciente": 89, "tipo_paciente": "Convenio", "nombre_completo": "Jorge Luis Morales Soria", "fecha_nacimiento": "1973-11-24", "contacto_emergencia": "Raquel Morales (Esposa)", "direccion_domicilio": "Av. Grigotá, C. Los Cusis #90", "telefono_emergencia": "+591 74061325", "informacion_medica_basica": "Gastritis crónica antral por H. pylori tratada."}, "anterior": {"ci": "3918204", "correo": "jorge.morales@yahoo.com", "estado": true, "genero": "Masculino", "alergias": "Penicilina", "telefono": "+591 74061324", "id_paciente": 89, "tipo_paciente": "Convenio", "nombre_completo": "Jorge Luis Morales Soria", "fecha_nacimiento": "1973-11-24", "contacto_emergencia": "Raquel Morales (Esposa)", "direccion_domicilio": "Av. Grigotá, C. Los Cusis #90", "telefono_emergencia": "+591 74061325", "informacion_medica_basica": "Gastritis crónica antral por H. pylori tratada."}}	2026-11-12 20:04:35.547707
133	\N	UPDATE	paciente	90	{"nuevo": {"ci": "3719284", "correo": "carmen.salvatierra@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Dipirona", "telefono": "+591 75172435", "id_paciente": 90, "tipo_paciente": "VIP", "nombre_completo": "Carmen Rosa Salvatierra Roca", "fecha_nacimiento": "1970-04-15", "contacto_emergencia": "Daniel Salvatierra (Hijo)", "direccion_domicilio": "Condominio Portal del Urubó casa 22", "telefono_emergencia": "+591 75172436", "informacion_medica_basica": "Colecistectomía laparoscópica en 2021."}, "anterior": {"ci": "3719284", "correo": "carmen.salvatierra@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Dipirona", "telefono": "+591 75172435", "id_paciente": 90, "tipo_paciente": "VIP", "nombre_completo": "Carmen Rosa Salvatierra Roca", "fecha_nacimiento": "1970-04-15", "contacto_emergencia": "Daniel Salvatierra (Hijo)", "direccion_domicilio": "Condominio Portal del Urubó casa 22", "telefono_emergencia": "+591 75172436", "informacion_medica_basica": "Colecistectomía laparoscópica en 2021."}}	2026-11-12 20:04:35.547707
134	\N	UPDATE	paciente	91	{"nuevo": {"ci": "2918203", "correo": "walter.ribera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76283546", "id_paciente": 91, "tipo_paciente": "Asegurado", "nombre_completo": "Walter Humberto Ribera Melgar", "fecha_nacimiento": "1960-01-10", "contacto_emergencia": "Sonia Ribera (Esposa)", "direccion_domicilio": "Av. Mutualista 3er Anillo Externo", "telefono_emergencia": "+591 76283547", "informacion_medica_basica": "Diabetes Mellitus Tipo 2, cardiopatía isquémica controlada."}, "anterior": {"ci": "2918203", "correo": "walter.ribera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76283546", "id_paciente": 91, "tipo_paciente": "Asegurado", "nombre_completo": "Walter Humberto Ribera Melgar", "fecha_nacimiento": "1960-01-10", "contacto_emergencia": "Sonia Ribera (Esposa)", "direccion_domicilio": "Av. Mutualista 3er Anillo Externo", "telefono_emergencia": "+591 76283547", "informacion_medica_basica": "Diabetes Mellitus Tipo 2, cardiopatía isquémica controlada."}}	2026-11-12 20:04:35.547707
135	\N	UPDATE	paciente	92	{"nuevo": {"ci": "2481920", "correo": "guillermina.suarez@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Penicilina", "telefono": "+591 77394657", "id_paciente": 92, "tipo_paciente": "General", "nombre_completo": "Guillermina Suárez vda. de Roca", "fecha_nacimiento": "1955-08-03", "contacto_emergencia": "Gustavo Roca (Hijo)", "direccion_domicilio": "Av. Irala #412", "telefono_emergencia": "+591 77394658", "informacion_medica_basica": "Osteoartrosis de rodilla bilateral, HTA esencial."}, "anterior": {"ci": "2481920", "correo": "guillermina.suarez@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Penicilina", "telefono": "+591 77394657", "id_paciente": 92, "tipo_paciente": "General", "nombre_completo": "Guillermina Suárez vda. de Roca", "fecha_nacimiento": "1955-08-03", "contacto_emergencia": "Gustavo Roca (Hijo)", "direccion_domicilio": "Av. Irala #412", "telefono_emergencia": "+591 77394658", "informacion_medica_basica": "Osteoartrosis de rodilla bilateral, HTA esencial."}}	2026-11-12 20:04:35.547707
136	\N	UPDATE	paciente	93	{"nuevo": {"ci": "2198204", "correo": "alcides.flores@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78405768", "id_paciente": 93, "tipo_paciente": "Convenio", "nombre_completo": "Alcides Fernando Flores Vaca", "fecha_nacimiento": "1951-12-19", "contacto_emergencia": "Rosa Flores (Hija)", "direccion_domicilio": "Barrio Santa Rosita, Calle 3", "telefono_emergencia": "+591 78405769", "informacion_medica_basica": "Hiperplasia prostática benigna, control urológico regular."}, "anterior": {"ci": "2198204", "correo": "alcides.flores@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78405768", "id_paciente": 93, "tipo_paciente": "Convenio", "nombre_completo": "Alcides Fernando Flores Vaca", "fecha_nacimiento": "1951-12-19", "contacto_emergencia": "Rosa Flores (Hija)", "direccion_domicilio": "Barrio Santa Rosita, Calle 3", "telefono_emergencia": "+591 78405769", "informacion_medica_basica": "Hiperplasia prostática benigna, control urológico regular."}}	2026-11-12 20:04:35.547707
137	\N	UPDATE	paciente	94	{"nuevo": {"ci": "1982039", "correo": "gladys.ortiz@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Aspirina", "telefono": "+591 79516879", "id_paciente": 94, "tipo_paciente": "VIP", "nombre_completo": "Gladys Teresa Ortiz Aguilera", "fecha_nacimiento": "1948-05-27", "contacto_emergencia": "Mariana Ortiz (Nieta)", "direccion_domicilio": "Av. Cristóbal de Mendoza #615", "telefono_emergencia": "+591 79516880", "informacion_medica_basica": "Fibrilación auricular no valvular anticoagulada."}, "anterior": {"ci": "1982039", "correo": "gladys.ortiz@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Aspirina", "telefono": "+591 79516879", "id_paciente": 94, "tipo_paciente": "VIP", "nombre_completo": "Gladys Teresa Ortiz Aguilera", "fecha_nacimiento": "1948-05-27", "contacto_emergencia": "Mariana Ortiz (Nieta)", "direccion_domicilio": "Av. Cristóbal de Mendoza #615", "telefono_emergencia": "+591 79516880", "informacion_medica_basica": "Fibrilación auricular no valvular anticoagulada."}}	2026-11-12 20:04:35.547707
138	\N	UPDATE	paciente	95	{"nuevo": {"ci": "1728190", "correo": "ramiro.mendoza@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 70627980", "id_paciente": 95, "tipo_paciente": "Asegurado", "nombre_completo": "Ramiro Gonzalo Mendoza Justiniano", "fecha_nacimiento": "1945-10-02", "contacto_emergencia": "Pedro Mendoza (Hijo)", "direccion_domicilio": "Calle Warnes esq. Potosí #88", "telefono_emergencia": "+591 70627981", "informacion_medica_basica": "Enfermedad pulmonar obstructiva crónica (EPOC) leve."}, "anterior": {"ci": "1728190", "correo": "ramiro.mendoza@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 70627980", "id_paciente": 95, "tipo_paciente": "Asegurado", "nombre_completo": "Ramiro Gonzalo Mendoza Justiniano", "fecha_nacimiento": "1945-10-02", "contacto_emergencia": "Pedro Mendoza (Hijo)", "direccion_domicilio": "Calle Warnes esq. Potosí #88", "telefono_emergencia": "+591 70627981", "informacion_medica_basica": "Enfermedad pulmonar obstructiva crónica (EPOC) leve."}}	2026-11-12 20:04:35.547707
139	\N	UPDATE	paciente	96	{"nuevo": {"ci": "6192840", "correo": "tania.soria@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Mariscos y Yodo", "telefono": "+591 71839012", "id_paciente": 96, "tipo_paciente": "General", "nombre_completo": "Tania Gisela Soria Galvarro", "fecha_nacimiento": "1987-03-11", "contacto_emergencia": "Fabiola Soria (Hermana)", "direccion_domicilio": "Barrio Los Chacos, C. Las Palmeras", "telefono_emergencia": "+591 71839013", "informacion_medica_basica": "Dermatitis atópica crónica."}, "anterior": {"ci": "6192840", "correo": "tania.soria@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Mariscos y Yodo", "telefono": "+591 71839012", "id_paciente": 96, "tipo_paciente": "General", "nombre_completo": "Tania Gisela Soria Galvarro", "fecha_nacimiento": "1987-03-11", "contacto_emergencia": "Fabiola Soria (Hermana)", "direccion_domicilio": "Barrio Los Chacos, C. Las Palmeras", "telefono_emergencia": "+591 71839013", "informacion_medica_basica": "Dermatitis atópica crónica."}}	2026-11-12 20:04:35.547707
140	\N	UPDATE	paciente	97	{"nuevo": {"ci": "5829104", "correo": "victor.mercado@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72940123", "id_paciente": 97, "tipo_paciente": "Asegurado", "nombre_completo": "Víctor Hugo Mercado Banegas", "fecha_nacimiento": "1984-09-21", "contacto_emergencia": "Verónica Mercado (Esposa)", "direccion_domicilio": "Condominio Ciudad Jardín dpto 302", "telefono_emergencia": "+591 72940124", "informacion_medica_basica": "Hernia discal L4-L5 no quirúrgica."}, "anterior": {"ci": "5829104", "correo": "victor.mercado@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72940123", "id_paciente": 97, "tipo_paciente": "Asegurado", "nombre_completo": "Víctor Hugo Mercado Banegas", "fecha_nacimiento": "1984-09-21", "contacto_emergencia": "Verónica Mercado (Esposa)", "direccion_domicilio": "Condominio Ciudad Jardín dpto 302", "telefono_emergencia": "+591 72940124", "informacion_medica_basica": "Hernia discal L4-L5 no quirúrgica."}}	2026-11-12 20:04:35.547707
141	\N	UPDATE	paciente	98	{"nuevo": {"ci": "5291830", "correo": "fabiola.dorado@outlook.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 73051234", "id_paciente": 98, "tipo_paciente": "VIP", "nombre_completo": "Fabiola Patricia Dorado Melgar", "fecha_nacimiento": "1992-11-04", "contacto_emergencia": "Mauricio Dorado (Hermano)", "direccion_domicilio": "Av. San Aurelio 4to Anillo", "telefono_emergencia": "+591 73051235", "informacion_medica_basica": "Embarazo de 28 semanas de evolución normal."}, "anterior": {"ci": "5291830", "correo": "fabiola.dorado@outlook.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 73051234", "id_paciente": 98, "tipo_paciente": "VIP", "nombre_completo": "Fabiola Patricia Dorado Melgar", "fecha_nacimiento": "1992-11-04", "contacto_emergencia": "Mauricio Dorado (Hermano)", "direccion_domicilio": "Av. San Aurelio 4to Anillo", "telefono_emergencia": "+591 73051235", "informacion_medica_basica": "Embarazo de 28 semanas de evolución normal."}}	2026-11-12 20:04:35.547707
212	1	INSERT_CITA	cita_medica	91	{"origen": "POBLACION_CLINICA"}	2026-11-06 17:00:00
142	\N	UPDATE	paciente	99	{"nuevo": {"ci": "4918205", "correo": "alvaro.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Sulfamidas", "telefono": "+591 74162345", "id_paciente": 99, "tipo_paciente": "Convenio", "nombre_completo": "Álvaro René Banegas Aguilera", "fecha_nacimiento": "1980-07-16", "contacto_emergencia": "Cecilia Banegas (Esposa)", "direccion_domicilio": "Av. Santos Dumont #1024", "telefono_emergencia": "+591 74162346", "informacion_medica_basica": "Hipercolesterolemia familiar."}, "anterior": {"ci": "4918205", "correo": "alvaro.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Sulfamidas", "telefono": "+591 74162345", "id_paciente": 99, "tipo_paciente": "Convenio", "nombre_completo": "Álvaro René Banegas Aguilera", "fecha_nacimiento": "1980-07-16", "contacto_emergencia": "Cecilia Banegas (Esposa)", "direccion_domicilio": "Av. Santos Dumont #1024", "telefono_emergencia": "+591 74162346", "informacion_medica_basica": "Hipercolesterolemia familiar."}}	2026-11-12 20:04:35.547707
143	\N	UPDATE	paciente	100	{"nuevo": {"ci": "4381920", "correo": "lourdes.justiniano@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 75273456", "id_paciente": 100, "tipo_paciente": "General", "nombre_completo": "Lourdes Marina Justiniano Roca", "fecha_nacimiento": "1977-02-28", "contacto_emergencia": "Karina Justiniano (Hija)", "direccion_domicilio": "Barrio Ramafa, C. Cañoto #15", "telefono_emergencia": "+591 75273457", "informacion_medica_basica": "Gastritis erosiva superficial."}, "anterior": {"ci": "4381920", "correo": "lourdes.justiniano@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 75273456", "id_paciente": 100, "tipo_paciente": "General", "nombre_completo": "Lourdes Marina Justiniano Roca", "fecha_nacimiento": "1977-02-28", "contacto_emergencia": "Karina Justiniano (Hija)", "direccion_domicilio": "Barrio Ramafa, C. Cañoto #15", "telefono_emergencia": "+591 75273457", "informacion_medica_basica": "Gastritis erosiva superficial."}}	2026-11-12 20:04:35.547707
144	\N	UPDATE	paciente	101	{"nuevo": {"ci": "7918204", "correo": "esteban.pinto@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76384567", "id_paciente": 101, "tipo_paciente": "General", "nombre_completo": "Esteban David Pinto Saucedo", "fecha_nacimiento": "2000-12-14", "contacto_emergencia": "David Pinto (Padre)", "direccion_domicilio": "Av. Piraí entre 2do y 3er Anillo", "telefono_emergencia": "+591 76384568", "informacion_medica_basica": "Chequeo médico universitario anual."}, "anterior": {"ci": "7918204", "correo": "esteban.pinto@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76384567", "id_paciente": 101, "tipo_paciente": "General", "nombre_completo": "Esteban David Pinto Saucedo", "fecha_nacimiento": "2000-12-14", "contacto_emergencia": "David Pinto (Padre)", "direccion_domicilio": "Av. Piraí entre 2do y 3er Anillo", "telefono_emergencia": "+591 76384568", "informacion_medica_basica": "Chequeo médico universitario anual."}}	2026-11-12 20:04:35.547707
145	\N	UPDATE	paciente	102	{"nuevo": {"ci": "7481920", "correo": "valeria.vaca@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Cefalosporinas", "telefono": "+591 77495678", "id_paciente": 102, "tipo_paciente": "VIP", "nombre_completo": "Valeria Inés Vaca Antelo", "fecha_nacimiento": "1997-04-09", "contacto_emergencia": "Lucía Antelo (Madre)", "direccion_domicilio": "Av. Monseñor Rivero #320", "telefono_emergencia": "+591 77495679", "informacion_medica_basica": "Acné inflamatorio grado II."}, "anterior": {"ci": "7481920", "correo": "valeria.vaca@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Cefalosporinas", "telefono": "+591 77495678", "id_paciente": 102, "tipo_paciente": "VIP", "nombre_completo": "Valeria Inés Vaca Antelo", "fecha_nacimiento": "1997-04-09", "contacto_emergencia": "Lucía Antelo (Madre)", "direccion_domicilio": "Av. Monseñor Rivero #320", "telefono_emergencia": "+591 77495679", "informacion_medica_basica": "Acné inflamatorio grado II."}}	2026-11-12 20:04:35.547707
146	\N	UPDATE	paciente	103	{"nuevo": {"ci": "6918203", "correo": "rene.melgar@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78506789", "id_paciente": 103, "tipo_paciente": "Asegurado", "nombre_completo": "René Alejandro Melgar Justiniano", "fecha_nacimiento": "1993-01-23", "contacto_emergencia": "Beatriz Melgar (Esposa)", "direccion_domicilio": "Condominio La Casona casa 4", "telefono_emergencia": "+591 78506780", "informacion_medica_basica": "Esguince de tobillo derecho en rehabilitación."}, "anterior": {"ci": "6918203", "correo": "rene.melgar@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78506789", "id_paciente": 103, "tipo_paciente": "Asegurado", "nombre_completo": "René Alejandro Melgar Justiniano", "fecha_nacimiento": "1993-01-23", "contacto_emergencia": "Beatriz Melgar (Esposa)", "direccion_domicilio": "Condominio La Casona casa 4", "telefono_emergencia": "+591 78506780", "informacion_medica_basica": "Esguince de tobillo derecho en rehabilitación."}}	2026-11-12 20:04:35.547707
147	\N	UPDATE	paciente	104	{"nuevo": {"ci": "6381920", "correo": "carla.aguilera@yahoo.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 79617890", "id_paciente": 104, "tipo_paciente": "General", "nombre_completo": "Carla Jimena Aguilera Paz", "fecha_nacimiento": "1990-08-17", "contacto_emergencia": "Gonzalo Paz (Padre)", "direccion_domicilio": "Av. Alemania 5to Anillo", "telefono_emergencia": "+591 79617891", "informacion_medica_basica": "Migrañas catameniales periódicas."}, "anterior": {"ci": "6381920", "correo": "carla.aguilera@yahoo.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 79617890", "id_paciente": 104, "tipo_paciente": "General", "nombre_completo": "Carla Jimena Aguilera Paz", "fecha_nacimiento": "1990-08-17", "contacto_emergencia": "Gonzalo Paz (Padre)", "direccion_domicilio": "Av. Alemania 5to Anillo", "telefono_emergencia": "+591 79617891", "informacion_medica_basica": "Migrañas catameniales periódicas."}}	2026-11-12 20:04:35.547707
148	\N	UPDATE	paciente	105	{"nuevo": {"ci": "5619283", "correo": "gustavo.ribera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Metamizol", "telefono": "+591 70728901", "id_paciente": 105, "tipo_paciente": "Convenio", "nombre_completo": "Gustavo Adolfo Ribera Soria", "fecha_nacimiento": "1986-06-05", "contacto_emergencia": "Elena Ribera (Hermana)", "direccion_domicilio": "Av. 2 de Agosto #880", "telefono_emergencia": "+591 70728902", "informacion_medica_basica": "Sobrepeso grado 1, consulta nutricional."}, "anterior": {"ci": "5619283", "correo": "gustavo.ribera@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Metamizol", "telefono": "+591 70728901", "id_paciente": 105, "tipo_paciente": "Convenio", "nombre_completo": "Gustavo Adolfo Ribera Soria", "fecha_nacimiento": "1986-06-05", "contacto_emergencia": "Elena Ribera (Hermana)", "direccion_domicilio": "Av. 2 de Agosto #880", "telefono_emergencia": "+591 70728902", "informacion_medica_basica": "Sobrepeso grado 1, consulta nutricional."}}	2026-11-12 20:04:35.547707
213	1	INSERT_CITA	cita_medica	92	{"origen": "POBLACION_CLINICA"}	2026-11-09 11:00:00
214	1	INSERT_CITA	cita_medica	93	{"origen": "POBLACION_CLINICA"}	2026-11-09 08:30:00
215	1	INSERT_CITA	cita_medica	94	{"origen": "POBLACION_CLINICA"}	2026-11-10 10:30:00
149	\N	UPDATE	paciente	106	{"nuevo": {"ci": "5129840", "correo": "daniela.suarez@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 71839023", "id_paciente": 106, "tipo_paciente": "VIP", "nombre_completo": "Daniela Elizabeth Suárez Vaca", "fecha_nacimiento": "1983-10-12", "contacto_emergencia": "Martín Suárez (Esposo)", "direccion_domicilio": "Av. Virgen de Cotoca 3er Anillo", "telefono_emergencia": "+591 71839024", "informacion_medica_basica": "Control mamográfico preventivo birads 1."}, "anterior": {"ci": "5129840", "correo": "daniela.suarez@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 71839023", "id_paciente": 106, "tipo_paciente": "VIP", "nombre_completo": "Daniela Elizabeth Suárez Vaca", "fecha_nacimiento": "1983-10-12", "contacto_emergencia": "Martín Suárez (Esposo)", "direccion_domicilio": "Av. Virgen de Cotoca 3er Anillo", "telefono_emergencia": "+591 71839024", "informacion_medica_basica": "Control mamográfico preventivo birads 1."}}	2026-11-12 20:04:35.547707
150	\N	UPDATE	paciente	107	{"nuevo": {"ci": "4719283", "correo": "oscar.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72940134", "id_paciente": 107, "tipo_paciente": "Asegurado", "nombre_completo": "Óscar Manuel Antelo Banegas", "fecha_nacimiento": "1978-03-30", "contacto_emergencia": "Susana Antelo (Esposa)", "direccion_domicilio": "Barrio La Morita, C. Los Helechos", "telefono_emergencia": "+591 72940135", "informacion_medica_basica": "Insuficiencia venosa periférica bilateral."}, "anterior": {"ci": "4719283", "correo": "oscar.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 72940134", "id_paciente": 107, "tipo_paciente": "Asegurado", "nombre_completo": "Óscar Manuel Antelo Banegas", "fecha_nacimiento": "1978-03-30", "contacto_emergencia": "Susana Antelo (Esposa)", "direccion_domicilio": "Barrio La Morita, C. Los Helechos", "telefono_emergencia": "+591 72940135", "informacion_medica_basica": "Insuficiencia venosa periférica bilateral."}}	2026-11-12 20:04:35.547707
151	\N	UPDATE	paciente	108	{"nuevo": {"ci": "4192840", "correo": "martha.roca@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Eritromicina", "telefono": "+591 73051245", "id_paciente": 108, "tipo_paciente": "General", "nombre_completo": "Martha Cecilia Roca Melgar", "fecha_nacimiento": "1974-12-01", "contacto_emergencia": "Raúl Roca (Hijo)", "direccion_domicilio": "Av. Tres Pasos al Frente #540", "telefono_emergencia": "+591 73051246", "informacion_medica_basica": "Artrosis de manos y nódulos de Heberden."}, "anterior": {"ci": "4192840", "correo": "martha.roca@hotmail.com", "estado": true, "genero": "Femenino", "alergias": "Eritromicina", "telefono": "+591 73051245", "id_paciente": 108, "tipo_paciente": "General", "nombre_completo": "Martha Cecilia Roca Melgar", "fecha_nacimiento": "1974-12-01", "contacto_emergencia": "Raúl Roca (Hijo)", "direccion_domicilio": "Av. Tres Pasos al Frente #540", "telefono_emergencia": "+591 73051246", "informacion_medica_basica": "Artrosis de manos y nódulos de Heberden."}}	2026-11-12 20:04:35.547707
152	\N	UPDATE	paciente	109	{"nuevo": {"ci": "3819204", "correo": "jaime.saucedo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 74162356", "id_paciente": 109, "tipo_paciente": "Convenio", "nombre_completo": "Jaime Eduardo Saucedo Justiniano", "fecha_nacimiento": "1971-07-07", "contacto_emergencia": "Gladys Saucedo (Esposa)", "direccion_domicilio": "Av. Cumavi, Barrio 10 de Octubre", "telefono_emergencia": "+591 74162357", "informacion_medica_basica": "Hipertrigliceridemia y esteatosis hepática leve."}, "anterior": {"ci": "3819204", "correo": "jaime.saucedo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 74162356", "id_paciente": 109, "tipo_paciente": "Convenio", "nombre_completo": "Jaime Eduardo Saucedo Justiniano", "fecha_nacimiento": "1971-07-07", "contacto_emergencia": "Gladys Saucedo (Esposa)", "direccion_domicilio": "Av. Cumavi, Barrio 10 de Octubre", "telefono_emergencia": "+591 74162357", "informacion_medica_basica": "Hipertrigliceridemia y esteatosis hepática leve."}}	2026-11-12 20:04:35.547707
153	\N	UPDATE	paciente	110	{"nuevo": {"ci": "3291820", "correo": "beatriz.dorado@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Penicilina", "telefono": "+591 75273467", "id_paciente": 110, "tipo_paciente": "VIP", "nombre_completo": "Beatriz Eugenia Dorado Pinto", "fecha_nacimiento": "1966-09-18", "contacto_emergencia": "Ignacio Dorado (Hijo)", "direccion_domicilio": "Barrio Petrolero, C. Las Lilas #45", "telefono_emergencia": "+591 75273468", "informacion_medica_basica": "Osteoporosis densitométrica en tratamiento."}, "anterior": {"ci": "3291820", "correo": "beatriz.dorado@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Penicilina", "telefono": "+591 75273467", "id_paciente": 110, "tipo_paciente": "VIP", "nombre_completo": "Beatriz Eugenia Dorado Pinto", "fecha_nacimiento": "1966-09-18", "contacto_emergencia": "Ignacio Dorado (Hijo)", "direccion_domicilio": "Barrio Petrolero, C. Las Lilas #45", "telefono_emergencia": "+591 75273468", "informacion_medica_basica": "Osteoporosis densitométrica en tratamiento."}}	2026-11-12 20:04:35.547707
154	\N	UPDATE	paciente	111	{"nuevo": {"ci": "2819204", "correo": "felix.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76384578", "id_paciente": 111, "tipo_paciente": "Asegurado", "nombre_completo": "Félix Rolando Banegas Ortiz", "fecha_nacimiento": "1961-05-14", "contacto_emergencia": "Hilda Banegas (Esposa)", "direccion_domicilio": "Av. San Martín 2do Anillo", "telefono_emergencia": "+591 76384579", "informacion_medica_basica": "Reemplazo total de cadera derecha en 2023."}, "anterior": {"ci": "2819204", "correo": "felix.banegas@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 76384578", "id_paciente": 111, "tipo_paciente": "Asegurado", "nombre_completo": "Félix Rolando Banegas Ortiz", "fecha_nacimiento": "1961-05-14", "contacto_emergencia": "Hilda Banegas (Esposa)", "direccion_domicilio": "Av. San Martín 2do Anillo", "telefono_emergencia": "+591 76384579", "informacion_medica_basica": "Reemplazo total de cadera derecha en 2023."}}	2026-11-12 20:04:35.547707
155	\N	UPDATE	paciente	112	{"nuevo": {"ci": "2391820", "correo": "rosaelva.justiniano@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Sulfas", "telefono": "+591 77495689", "id_paciente": 112, "tipo_paciente": "General", "nombre_completo": "Rosa Elva Justiniano vda. de Paz", "fecha_nacimiento": "1956-11-29", "contacto_emergencia": "Carolina Paz (Hija)", "direccion_domicilio": "C. Sucre #220", "telefono_emergencia": "+591 77495680", "informacion_medica_basica": "Glaucoma de ángulo abierto tratado con gotas."}, "anterior": {"ci": "2391820", "correo": "rosaelva.justiniano@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Sulfas", "telefono": "+591 77495689", "id_paciente": 112, "tipo_paciente": "General", "nombre_completo": "Rosa Elva Justiniano vda. de Paz", "fecha_nacimiento": "1956-11-29", "contacto_emergencia": "Carolina Paz (Hija)", "direccion_domicilio": "C. Sucre #220", "telefono_emergencia": "+591 77495680", "informacion_medica_basica": "Glaucoma de ángulo abierto tratado con gotas."}}	2026-11-12 20:04:35.547707
216	1	INSERT_CITA	cita_medica	95	{"origen": "POBLACION_CLINICA"}	2026-11-10 09:00:00
217	1	INSERT_CITA	cita_medica	96	{"origen": "POBLACION_CLINICA"}	2026-11-11 17:00:00
156	\N	UPDATE	paciente	113	{"nuevo": {"ci": "1829104", "correo": "armando.aguilera@yahoo.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78506790", "id_paciente": 113, "tipo_paciente": "VIP", "nombre_completo": "Armando César Aguilera Roca", "fecha_nacimiento": "1950-04-03", "contacto_emergencia": "César Aguilera (Hijo)", "direccion_domicilio": "Av. Cañoto #112", "telefono_emergencia": "+591 78506791", "informacion_medica_basica": "Secuela de infarto agudo de miocardio 2019 estable."}, "anterior": {"ci": "1829104", "correo": "armando.aguilera@yahoo.com", "estado": true, "genero": "Masculino", "alergias": "Ninguna", "telefono": "+591 78506790", "id_paciente": 113, "tipo_paciente": "VIP", "nombre_completo": "Armando César Aguilera Roca", "fecha_nacimiento": "1950-04-03", "contacto_emergencia": "César Aguilera (Hijo)", "direccion_domicilio": "Av. Cañoto #112", "telefono_emergencia": "+591 78506791", "informacion_medica_basica": "Secuela de infarto agudo de miocardio 2019 estable."}}	2026-11-12 20:04:35.547707
157	\N	UPDATE	paciente	114	{"nuevo": {"ci": "1591820", "correo": "ines.soria@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 79617801", "id_paciente": 114, "tipo_paciente": "Asegurado", "nombre_completo": "Inés Magdalena Soria Melgar", "fecha_nacimiento": "1947-08-16", "contacto_emergencia": "Andrea Soria (Nieta)", "direccion_domicilio": "Av. Argandoña #85", "telefono_emergencia": "+591 79617802", "informacion_medica_basica": "Demencia vascular en fase inicial con cuidador."}, "anterior": {"ci": "1591820", "correo": "ines.soria@gmail.com", "estado": true, "genero": "Femenino", "alergias": "Ninguna", "telefono": "+591 79617801", "id_paciente": 114, "tipo_paciente": "Asegurado", "nombre_completo": "Inés Magdalena Soria Melgar", "fecha_nacimiento": "1947-08-16", "contacto_emergencia": "Andrea Soria (Nieta)", "direccion_domicilio": "Av. Argandoña #85", "telefono_emergencia": "+591 79617802", "informacion_medica_basica": "Demencia vascular en fase inicial con cuidador."}}	2026-11-12 20:04:35.547707
158	\N	UPDATE	paciente	115	{"nuevo": {"ci": "1291820", "correo": "julio.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Aspirina", "telefono": "+591 70728912", "id_paciente": 115, "tipo_paciente": "Convenio", "nombre_completo": "Julio César Antelo Vaca", "fecha_nacimiento": "1943-02-20", "contacto_emergencia": "Mariano Antelo (Hijo)", "direccion_domicilio": "Barrio El Trompillo, C. 5 #10", "telefono_emergencia": "+591 70728913", "informacion_medica_basica": "Portador de marcapasos bicameral definitivo."}, "anterior": {"ci": "1291820", "correo": "julio.antelo@gmail.com", "estado": true, "genero": "Masculino", "alergias": "Aspirina", "telefono": "+591 70728912", "id_paciente": 115, "tipo_paciente": "Convenio", "nombre_completo": "Julio César Antelo Vaca", "fecha_nacimiento": "1943-02-20", "contacto_emergencia": "Mariano Antelo (Hijo)", "direccion_domicilio": "Barrio El Trompillo, C. 5 #10", "telefono_emergencia": "+591 70728913", "informacion_medica_basica": "Portador de marcapasos bicameral definitivo."}}	2026-11-12 20:04:35.547707
159	1	INSERT_CITA	cita_medica	38	{"origen": "POBLACION_CLINICA"}	2026-10-01 08:00:00
160	1	INSERT_CITA	cita_medica	39	{"origen": "POBLACION_CLINICA"}	2026-10-01 09:30:00
161	1	INSERT_CITA	cita_medica	40	{"origen": "POBLACION_CLINICA"}	2026-10-02 14:00:00
162	1	INSERT_CITA	cita_medica	41	{"origen": "POBLACION_CLINICA"}	2026-10-02 15:00:00
163	1	INSERT_CITA	cita_medica	42	{"origen": "POBLACION_CLINICA"}	2026-10-05 11:00:00
164	1	INSERT_CITA	cita_medica	43	{"origen": "POBLACION_CLINICA"}	2026-10-05 08:00:00
165	1	INSERT_CITA	cita_medica	44	{"origen": "POBLACION_CLINICA"}	2026-10-06 10:30:00
166	1	INSERT_CITA	cita_medica	45	{"origen": "POBLACION_CLINICA"}	2026-10-06 11:00:00
167	1	INSERT_CITA	cita_medica	46	{"origen": "POBLACION_CLINICA"}	2026-10-07 14:30:00
168	1	INSERT_CITA	cita_medica	47	{"origen": "POBLACION_CLINICA"}	2026-10-07 10:00:00
169	1	INSERT_CITA	cita_medica	48	{"origen": "POBLACION_CLINICA"}	2026-10-08 10:00:00
170	1	INSERT_CITA	cita_medica	49	{"origen": "POBLACION_CLINICA"}	2026-10-08 16:30:00
171	1	INSERT_CITA	cita_medica	50	{"origen": "POBLACION_CLINICA"}	2026-10-09 10:30:00
172	1	INSERT_CITA	cita_medica	51	{"origen": "POBLACION_CLINICA"}	2026-10-09 08:30:00
173	1	INSERT_CITA	cita_medica	52	{"origen": "POBLACION_CLINICA"}	2026-10-12 09:00:00
174	1	INSERT_CITA	cita_medica	53	{"origen": "POBLACION_CLINICA"}	2026-10-12 17:00:00
175	1	INSERT_CITA	cita_medica	54	{"origen": "POBLACION_CLINICA"}	2026-10-13 09:00:00
176	1	INSERT_CITA	cita_medica	55	{"origen": "POBLACION_CLINICA"}	2026-10-13 08:00:00
177	1	INSERT_CITA	cita_medica	56	{"origen": "POBLACION_CLINICA"}	2026-10-14 14:00:00
178	1	INSERT_CITA	cita_medica	57	{"origen": "POBLACION_CLINICA"}	2026-10-14 08:00:00
179	1	INSERT_CITA	cita_medica	58	{"origen": "POBLACION_CLINICA"}	2026-10-15 14:30:00
180	1	INSERT_CITA	cita_medica	59	{"origen": "POBLACION_CLINICA"}	2026-10-15 17:00:00
181	1	INSERT_CITA	cita_medica	60	{"origen": "POBLACION_CLINICA"}	2026-10-16 08:00:00
182	1	INSERT_CITA	cita_medica	61	{"origen": "POBLACION_CLINICA"}	2026-10-16 15:00:00
183	1	INSERT_CITA	cita_medica	62	{"origen": "POBLACION_CLINICA"}	2026-10-19 15:00:00
184	1	INSERT_CITA	cita_medica	63	{"origen": "POBLACION_CLINICA"}	2026-10-19 10:00:00
185	1	INSERT_CITA	cita_medica	64	{"origen": "POBLACION_CLINICA"}	2026-10-20 09:00:00
186	1	INSERT_CITA	cita_medica	65	{"origen": "POBLACION_CLINICA"}	2026-10-20 10:30:00
187	1	INSERT_CITA	cita_medica	66	{"origen": "POBLACION_CLINICA"}	2026-10-21 08:30:00
188	1	INSERT_CITA	cita_medica	67	{"origen": "POBLACION_CLINICA"}	2026-10-21 17:00:00
189	1	INSERT_CITA	cita_medica	68	{"origen": "POBLACION_CLINICA"}	2026-10-22 14:00:00
190	1	INSERT_CITA	cita_medica	69	{"origen": "POBLACION_CLINICA"}	2026-10-22 08:00:00
191	1	INSERT_CITA	cita_medica	70	{"origen": "POBLACION_CLINICA"}	2026-10-23 17:30:00
192	1	INSERT_CITA	cita_medica	71	{"origen": "POBLACION_CLINICA"}	2026-10-23 15:30:00
193	1	INSERT_CITA	cita_medica	72	{"origen": "POBLACION_CLINICA"}	2026-10-26 10:00:00
194	1	INSERT_CITA	cita_medica	73	{"origen": "POBLACION_CLINICA"}	2026-10-26 10:00:00
195	1	INSERT_CITA	cita_medica	74	{"origen": "POBLACION_CLINICA"}	2026-10-27 10:30:00
196	1	INSERT_CITA	cita_medica	75	{"origen": "POBLACION_CLINICA"}	2026-10-27 17:00:00
218	1	INSERT_CITA	cita_medica	97	{"origen": "POBLACION_CLINICA"}	2026-11-11 17:00:00
219	1	INSERT_CITA	cita_medica	98	{"origen": "POBLACION_CLINICA"}	2026-11-13 11:30:00
220	1	INSERT_CITA	cita_medica	99	{"origen": "POBLACION_CLINICA"}	2026-11-13 10:30:00
221	1	INSERT_CITA	cita_medica	100	{"origen": "POBLACION_CLINICA"}	2026-11-16 09:30:00
222	1	INSERT_CITA	cita_medica	101	{"origen": "POBLACION_CLINICA"}	2026-11-16 10:00:00
223	1	INSERT_CITA	cita_medica	102	{"origen": "POBLACION_CLINICA"}	2026-11-17 11:30:00
224	1	INSERT_CITA	cita_medica	103	{"origen": "POBLACION_CLINICA"}	2026-11-17 10:30:00
225	1	INSERT_CITA	cita_medica	104	{"origen": "POBLACION_CLINICA"}	2026-11-18 15:00:00
226	1	INSERT_CITA	cita_medica	105	{"origen": "POBLACION_CLINICA"}	2026-11-18 10:30:00
227	1	INSERT_CITA	cita_medica	106	{"origen": "POBLACION_CLINICA"}	2026-11-19 11:30:00
228	1	INSERT_CITA	cita_medica	107	{"origen": "POBLACION_CLINICA"}	2026-11-19 10:00:00
229	1	INSERT_CITA	cita_medica	108	{"origen": "POBLACION_CLINICA"}	2026-11-20 09:30:00
230	1	INSERT_CITA	cita_medica	109	{"origen": "POBLACION_CLINICA"}	2026-11-20 16:30:00
231	1	INSERT_CITA	cita_medica	110	{"origen": "POBLACION_CLINICA"}	2026-11-23 10:30:00
232	1	INSERT_CITA	cita_medica	111	{"origen": "POBLACION_CLINICA"}	2026-11-23 14:30:00
233	1	INSERT_CITA	cita_medica	112	{"origen": "POBLACION_CLINICA"}	2026-11-24 15:30:00
234	1	INSERT_CITA	cita_medica	113	{"origen": "POBLACION_CLINICA"}	2026-11-24 15:30:00
235	1	INSERT_CITA	cita_medica	114	{"origen": "POBLACION_CLINICA"}	2026-11-25 09:30:00
236	1	INSERT_CITA	cita_medica	115	{"origen": "POBLACION_CLINICA"}	2026-11-25 15:30:00
237	1	INSERT_CITA	cita_medica	116	{"origen": "POBLACION_CLINICA"}	2026-11-26 11:30:00
238	1	INSERT_CITA	cita_medica	117	{"origen": "POBLACION_CLINICA"}	2026-11-26 17:00:00
239	1	INSERT_CITA	cita_medica	118	{"origen": "POBLACION_CLINICA"}	2026-11-27 16:30:00
240	1	INSERT_CITA	cita_medica	119	{"origen": "POBLACION_CLINICA"}	2026-11-27 15:00:00
241	1	INSERT_CITA	cita_medica	120	{"origen": "POBLACION_CLINICA"}	2026-11-30 10:00:00
242	1	INSERT_CITA	cita_medica	121	{"origen": "POBLACION_CLINICA"}	2026-11-30 10:30:00
243	1	INSERT_CITA	cita_medica	122	{"origen": "POBLACION_CLINICA"}	2026-12-01 10:00:00
244	1	INSERT_CITA	cita_medica	123	{"origen": "POBLACION_CLINICA"}	2026-12-01 08:00:00
245	1	INSERT_CITA	cita_medica	124	{"origen": "POBLACION_CLINICA"}	2026-12-02 11:30:00
246	1	INSERT_CITA	cita_medica	125	{"origen": "POBLACION_CLINICA"}	2026-12-02 16:00:00
247	1	INSERT_CITA	cita_medica	126	{"origen": "POBLACION_CLINICA"}	2026-12-03 16:30:00
248	1	INSERT_CITA	cita_medica	127	{"origen": "POBLACION_CLINICA"}	2026-12-03 14:30:00
249	1	INSERT_CITA	cita_medica	128	{"origen": "POBLACION_CLINICA"}	2026-12-04 08:30:00
250	1	INSERT_CITA	cita_medica	129	{"origen": "POBLACION_CLINICA"}	2026-12-04 14:30:00
251	1	INSERT_CITA	cita_medica	130	{"origen": "POBLACION_CLINICA"}	2026-12-07 16:30:00
252	1	INSERT_CITA	cita_medica	131	{"origen": "POBLACION_CLINICA"}	2026-12-07 10:00:00
253	1	INSERT_CITA	cita_medica	132	{"origen": "POBLACION_CLINICA"}	2026-12-08 08:00:00
254	1	INSERT_CITA	cita_medica	133	{"origen": "POBLACION_CLINICA"}	2026-12-08 09:30:00
255	1	INSERT_CITA	cita_medica	134	{"origen": "POBLACION_CLINICA"}	2026-12-09 15:30:00
256	1	INSERT_CITA	cita_medica	135	{"origen": "POBLACION_CLINICA"}	2026-12-09 08:30:00
257	1	INSERT_CITA	cita_medica	136	{"origen": "POBLACION_CLINICA"}	2026-12-10 17:30:00
258	1	INSERT_CITA	cita_medica	137	{"origen": "POBLACION_CLINICA"}	2026-12-10 17:30:00
259	1	INSERT_CITA	cita_medica	138	{"origen": "POBLACION_CLINICA"}	2026-12-11 09:00:00
260	1	INSERT_CITA	cita_medica	139	{"origen": "POBLACION_CLINICA"}	2026-12-11 10:30:00
261	1	INSERT_CITA	cita_medica	140	{"origen": "POBLACION_CLINICA"}	2026-12-14 09:00:00
262	1	INSERT_CITA	cita_medica	141	{"origen": "POBLACION_CLINICA"}	2026-12-14 08:00:00
263	1	INSERT_CITA	cita_medica	142	{"origen": "POBLACION_CLINICA"}	2026-12-15 15:00:00
264	1	INSERT_CITA	cita_medica	143	{"origen": "POBLACION_CLINICA"}	2026-12-15 09:30:00
265	1	INSERT_CITA	cita_medica	144	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-23 10:00:00
266	1	INSERT_CITA	cita_medica	145	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 16:30:00
267	1	INSERT_CITA	cita_medica	146	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 17:30:00
268	1	INSERT_CITA	cita_medica	147	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 09:00:00
269	1	INSERT_CITA	cita_medica	148	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-27 10:00:00
270	1	INSERT_CITA	cita_medica	149	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-29 17:30:00
271	1	INSERT_CITA	cita_medica	150	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-24 14:00:00
272	1	INSERT_CITA	cita_medica	151	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-24 17:30:00
273	1	INSERT_CITA	cita_medica	152	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-11 11:00:00
274	1	INSERT_CITA	cita_medica	153	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-28 16:00:00
275	1	INSERT_CITA	cita_medica	154	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-18 08:00:00
276	1	INSERT_CITA	cita_medica	155	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 10:30:00
277	1	INSERT_CITA	cita_medica	156	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 17:30:00
278	1	INSERT_CITA	cita_medica	157	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-22 15:30:00
279	1	INSERT_CITA	cita_medica	158	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 10:00:00
280	1	INSERT_CITA	cita_medica	159	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 15:30:00
281	1	INSERT_CITA	cita_medica	160	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-25 14:30:00
282	1	INSERT_CITA	cita_medica	161	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 09:00:00
283	1	INSERT_CITA	cita_medica	162	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-23 10:30:00
284	1	INSERT_CITA	cita_medica	163	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-12 08:30:00
285	1	INSERT_CITA	cita_medica	164	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 14:00:00
286	1	INSERT_CITA	cita_medica	165	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-04 11:00:00
287	1	INSERT_CITA	cita_medica	166	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 14:00:00
288	1	INSERT_CITA	cita_medica	167	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 16:30:00
289	1	INSERT_CITA	cita_medica	168	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-16 09:30:00
290	1	INSERT_CITA	cita_medica	169	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-21 15:30:00
291	1	INSERT_CITA	cita_medica	170	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-11 08:00:00
292	1	INSERT_CITA	cita_medica	171	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 08:00:00
293	1	INSERT_CITA	cita_medica	172	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 14:00:00
294	1	INSERT_CITA	cita_medica	173	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-17 17:00:00
295	1	INSERT_CITA	cita_medica	174	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-05 14:30:00
296	1	INSERT_CITA	cita_medica	175	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-28 16:00:00
297	1	INSERT_CITA	cita_medica	176	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-08 10:00:00
298	1	INSERT_CITA	cita_medica	177	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 15:00:00
299	1	INSERT_CITA	cita_medica	178	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-25 16:30:00
300	1	INSERT_CITA	cita_medica	179	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-29 10:00:00
301	1	INSERT_CITA	cita_medica	180	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-21 17:30:00
302	1	INSERT_CITA	cita_medica	181	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-29 16:30:00
303	1	INSERT_CITA	cita_medica	182	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 08:00:00
304	1	INSERT_CITA	cita_medica	183	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 16:00:00
305	1	INSERT_CITA	cita_medica	184	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-02 10:30:00
306	1	INSERT_CITA	cita_medica	185	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-15 11:00:00
307	1	INSERT_CITA	cita_medica	186	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-16 11:00:00
308	1	INSERT_CITA	cita_medica	187	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 09:30:00
309	1	INSERT_CITA	cita_medica	188	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 15:00:00
310	1	INSERT_CITA	cita_medica	189	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 09:00:00
311	1	INSERT_CITA	cita_medica	190	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-22 15:30:00
312	1	INSERT_CITA	cita_medica	191	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-04 10:30:00
313	1	INSERT_CITA	cita_medica	192	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-30 16:30:00
314	1	INSERT_CITA	cita_medica	193	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-21 16:00:00
315	1	INSERT_CITA	cita_medica	194	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 09:00:00
316	1	INSERT_CITA	cita_medica	195	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-29 10:30:00
317	1	INSERT_CITA	cita_medica	196	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-25 14:00:00
318	1	INSERT_CITA	cita_medica	197	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-29 11:00:00
319	1	INSERT_CITA	cita_medica	198	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 16:00:00
320	1	INSERT_CITA	cita_medica	199	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-29 14:30:00
321	1	INSERT_CITA	cita_medica	200	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-18 15:30:00
322	1	INSERT_CITA	cita_medica	201	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-11 09:00:00
323	1	INSERT_CITA	cita_medica	202	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 16:00:00
324	1	INSERT_CITA	cita_medica	203	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-13 10:00:00
325	1	INSERT_CITA	cita_medica	204	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 17:30:00
326	1	INSERT_CITA	cita_medica	205	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-06 17:30:00
327	1	INSERT_CITA	cita_medica	206	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 09:30:00
328	1	INSERT_CITA	cita_medica	207	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-23 11:30:00
329	1	INSERT_CITA	cita_medica	208	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-22 17:30:00
330	1	INSERT_CITA	cita_medica	209	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-26 17:30:00
331	1	INSERT_CITA	cita_medica	210	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-23 10:30:00
332	1	INSERT_CITA	cita_medica	211	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-30 10:00:00
333	1	INSERT_CITA	cita_medica	212	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 17:00:00
334	1	INSERT_CITA	cita_medica	213	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-26 15:00:00
335	1	INSERT_CITA	cita_medica	214	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-02 08:30:00
336	1	INSERT_CITA	cita_medica	215	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-06 08:30:00
337	1	INSERT_CITA	cita_medica	216	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-22 11:00:00
338	1	INSERT_CITA	cita_medica	217	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-12 16:00:00
339	1	INSERT_CITA	cita_medica	218	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 16:30:00
340	1	INSERT_CITA	cita_medica	219	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-16 15:30:00
341	1	INSERT_CITA	cita_medica	220	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 14:00:00
342	1	INSERT_CITA	cita_medica	221	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-12 16:30:00
343	1	INSERT_CITA	cita_medica	222	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-02 15:00:00
344	1	INSERT_CITA	cita_medica	223	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-11 17:00:00
345	1	INSERT_CITA	cita_medica	224	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-15 11:30:00
346	1	INSERT_CITA	cita_medica	225	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-01 15:00:00
347	1	INSERT_CITA	cita_medica	226	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-29 15:00:00
348	1	INSERT_CITA	cita_medica	227	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-20 11:30:00
349	1	INSERT_CITA	cita_medica	228	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-23 09:30:00
350	1	INSERT_CITA	cita_medica	229	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 14:00:00
351	1	INSERT_CITA	cita_medica	230	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-06 09:30:00
352	1	INSERT_CITA	cita_medica	231	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-02 17:00:00
353	1	INSERT_CITA	cita_medica	232	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-15 11:00:00
354	1	INSERT_CITA	cita_medica	233	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-21 09:30:00
355	1	INSERT_CITA	cita_medica	234	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-20 08:00:00
356	1	INSERT_CITA	cita_medica	235	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 09:30:00
357	1	INSERT_CITA	cita_medica	236	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 08:00:00
358	1	INSERT_CITA	cita_medica	237	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 16:00:00
359	1	INSERT_CITA	cita_medica	238	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 14:30:00
360	1	INSERT_CITA	cita_medica	239	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-27 10:00:00
361	1	INSERT_CITA	cita_medica	240	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 08:30:00
362	1	INSERT_CITA	cita_medica	241	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-16 14:30:00
363	1	INSERT_CITA	cita_medica	242	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-15 17:00:00
364	1	INSERT_CITA	cita_medica	243	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 09:30:00
365	1	INSERT_CITA	cita_medica	244	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-24 08:00:00
366	1	INSERT_CITA	cita_medica	245	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-02 16:30:00
367	1	INSERT_CITA	cita_medica	246	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-17 15:00:00
368	1	INSERT_CITA	cita_medica	247	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 17:00:00
369	1	INSERT_CITA	cita_medica	248	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-16 15:30:00
370	1	INSERT_CITA	cita_medica	249	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-07 11:30:00
371	1	INSERT_CITA	cita_medica	250	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-04 09:00:00
372	1	INSERT_CITA	cita_medica	251	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 14:00:00
373	1	INSERT_CITA	cita_medica	252	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-29 17:00:00
374	1	INSERT_CITA	cita_medica	253	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-15 16:30:00
375	1	INSERT_CITA	cita_medica	254	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-12 08:00:00
376	1	INSERT_CITA	cita_medica	255	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-22 17:30:00
377	1	INSERT_CITA	cita_medica	256	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-16 17:00:00
378	1	INSERT_CITA	cita_medica	257	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-03 16:00:00
379	1	INSERT_CITA	cita_medica	258	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 14:30:00
380	1	INSERT_CITA	cita_medica	259	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-01 15:00:00
381	1	INSERT_CITA	cita_medica	260	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 17:00:00
382	1	INSERT_CITA	cita_medica	261	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-30 15:30:00
383	1	INSERT_CITA	cita_medica	262	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 11:30:00
384	1	INSERT_CITA	cita_medica	263	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 10:00:00
385	1	INSERT_CITA	cita_medica	264	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 09:30:00
386	1	INSERT_CITA	cita_medica	265	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-30 11:00:00
387	1	INSERT_CITA	cita_medica	266	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-30 17:30:00
388	1	INSERT_CITA	cita_medica	267	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-23 14:00:00
389	1	INSERT_CITA	cita_medica	268	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-20 16:00:00
390	1	INSERT_CITA	cita_medica	269	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-05 15:00:00
391	1	INSERT_CITA	cita_medica	270	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 14:30:00
392	1	INSERT_CITA	cita_medica	271	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-20 14:30:00
393	1	INSERT_CITA	cita_medica	272	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-28 08:30:00
394	1	INSERT_CITA	cita_medica	273	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 11:30:00
395	1	INSERT_CITA	cita_medica	274	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-24 16:00:00
396	1	INSERT_CITA	cita_medica	275	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-15 11:00:00
397	1	INSERT_CITA	cita_medica	276	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-05 15:30:00
398	1	INSERT_CITA	cita_medica	277	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-10 08:30:00
399	1	INSERT_CITA	cita_medica	278	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-11 15:30:00
400	1	INSERT_CITA	cita_medica	279	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-23 17:00:00
401	1	INSERT_CITA	cita_medica	280	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-29 17:30:00
402	1	INSERT_CITA	cita_medica	281	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-16 08:00:00
403	1	INSERT_CITA	cita_medica	282	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-30 14:30:00
404	1	INSERT_CITA	cita_medica	283	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-17 14:00:00
405	1	INSERT_CITA	cita_medica	284	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-21 15:30:00
406	1	INSERT_CITA	cita_medica	285	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-03 17:30:00
407	1	INSERT_CITA	cita_medica	286	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-09 08:30:00
408	1	INSERT_CITA	cita_medica	287	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-26 11:30:00
409	1	INSERT_CITA	cita_medica	288	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-29 08:00:00
410	1	INSERT_CITA	cita_medica	289	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-22 11:30:00
411	1	INSERT_CITA	cita_medica	290	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-01 16:00:00
412	1	INSERT_CITA	cita_medica	291	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 17:30:00
413	1	INSERT_CITA	cita_medica	292	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-22 11:00:00
414	1	INSERT_CITA	cita_medica	293	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-09-17 08:00:00
415	1	INSERT_CITA	cita_medica	294	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-10-19 10:00:00
416	1	INSERT_CITA	cita_medica	295	{"modulo": "ESTADISTICAS_CLINICAS"}	2026-11-10 11:30:00
\.


--
-- Data for Name: cita_medica; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.cita_medica (id_cita, codigo_unico, id_paciente, id_medico, id_especialidad, id_estado_cita, fecha_cita, hora_cita, duracion_minutos, motivo, observacion_atencion, motivo_cancelacion, fecha_hora_llegada) FROM stdin;
1	cbf3fd2f-8b69-4afd-9115-a4ce97fa170f	1	5	3	3	2026-11-02	08:00:00	30	Control cardiológico semestral	Paciente estable hemodinámicamente, se renueva Losartán 50mg.	\N	2026-11-02 07:50:00
2	975c2449-c514-4394-999b-0cde05e0403f	2	6	4	3	2026-11-05	09:00:00	30	Control prenatal semana 12	Frecuencia cardíaca fetal rítmica, ecografía normal.	\N	2026-11-05 08:55:00
3	dee66764-c982-4976-b83f-83630bf31a6e	3	1	1	3	2026-11-02	08:30:00	30	Control glucémico en ayunas	Glicemia 118 mg/dl, mantener pauta de metformina y dieta.	\N	2026-11-02 08:20:00
4	3992a950-b53e-461e-ab11-de7b86bbe826	4	7	5	3	2026-11-06	15:00:00	30	Dolor agudo de menisco	Maniobra de McMurray positiva, se solicita resonancia magnética.	\N	2026-11-06 14:52:00
5	e51edf96-dfb6-42c1-8825-da71d3fd60ce	5	3	2	3	2026-11-04	08:00:00	30	Tos con sibilancias nocturnas	Salbutamol en spray 2 puff cada 8 horas por 5 días.	\N	2026-11-04 07:54:00
6	03ba234b-3112-40ee-8b56-40c92b8e15e7	6	8	5	3	2026-11-03	14:00:00	30	Molestia en articulaciones de dedos	Signos iniciales de sinovitis, recetado paracetamol y calor local.	\N	2026-11-03 13:50:00
7	8d982dde-8197-43b3-8b83-6fb2123a2d23	7	9	6	3	2026-11-03	09:00:00	30	Brote dérmico en rostro	Rosácea grado 1, se indica fotoprotector 50+ y gel metronidazol.	\N	2026-11-03 08:48:00
8	5d242daf-e9fc-496f-abdf-52a6eac0b781	8	1	1	3	2026-11-04	09:00:00	30	Acidez gástrica y pirosis	Epigastralgia leve, pauta con omeprazol 20mg en ayunas.	\N	2026-11-04 08:58:00
9	8a4c817d-05a6-4f06-83c1-7f5384880b67	9	9	6	3	2026-11-05	09:30:00	30	Prurito intenso en pliegues	Eccema de contacto, aplicar crema emoliente dos veces al día.	\N	2026-11-05 09:20:00
10	ff1665bb-28f8-4ace-8dcb-9af1611004bb	10	16	12	3	2026-11-02	14:00:00	30	Disminución de agudeza visual de lejos	Astigmatismo miópico, se emite receta para lentes de aumento.	\N	2026-11-02 13:55:00
11	b3ad76b2-c73c-412f-a1df-bac62d495265	11	15	11	3	2026-11-03	09:00:00	30	Plan nutricional por sobrepeso	Se diseña dieta mediterránea normocalórica fraccionada.	\N	2026-11-03 08:50:00
12	c6ed65aa-7a0a-4967-9a9a-f0b13c8f864e	12	19	15	3	2026-11-04	15:00:00	30	Insomnio y estrés laboral	Sesión de psicoeducación e higiene del sueño.	\N	2026-11-04 14:55:00
13	f9ea6de1-0b73-4772-8353-b24c10559b3c	13	3	2	3	2026-11-06	08:30:00	30	Fiebre de 38 grados y rinorrea	Cuadro viral alto, hidratación abundante y antipirético.	\N	2026-11-06 08:22:00
14	19f571e4-8eea-4eb8-b6af-fcaccba55645	14	21	17	3	2026-11-04	08:00:00	30	Disuria matutina	Sedimento de orina solicitado por sospecha de infección baja.	\N	2026-11-04 07:55:00
15	27415c65-7ce5-4427-a8bb-9e90422bcaf1	15	10	7	3	2026-11-04	14:00:00	30	Cefalea hemicraneana pulsátil	Migraña común, tratamiento abortivo con triptanes.	\N	2026-11-04 13:48:00
16	69bb4d8e-2344-4d97-8e45-726bb729efff	16	12	8	4	2026-11-04	09:00:00	30	Ecografía de mama preventiva	\N	Paciente tuvo conflicto de horario laboral	\N
17	f3804f1a-0d1d-40ab-a2a0-2498e3782c7e	17	13	9	4	2026-11-03	14:00:00	30	Pesadez y dolor en pantorrillas	\N	Imposibilidad de transporte por bloqueo vecinal	\N
18	1164259c-7e7c-4385-acb7-e79a0ed5c5d2	18	14	10	4	2026-11-04	08:30:00	30	Espirometría de control	\N	Paciente posterga por viaje imprevisto a Montero	\N
19	115fea99-7854-4ddf-bc99-9a46577f57b6	19	17	13	4	2026-11-05	08:00:00	30	Sensación de oído tapado	\N	Paciente solicitó cambio para próxima semana	\N
20	ded24d9c-9627-4ba9-975c-3962fddc7692	20	18	14	4	2026-11-04	08:30:00	30	Prurito perianal y sangrado leve	\N	Reprogramada por cruce de horario	\N
21	296dc0a1-825b-4893-9203-9d43adaac0a1	21	1	1	5	2026-11-05	14:00:00	30	Chequeo médico de rutina	\N	\N	\N
22	397a17fc-1a74-4c3d-9d8c-f2ffde73e163	22	7	5	5	2026-11-06	15:30:00	30	Revisión de ligamentos tobillo	\N	\N	\N
23	ead80985-ba56-4722-b058-e48358f64d5c	23	9	6	5	2026-11-05	10:00:00	30	Revisión de nevus en torso	\N	\N	\N
24	69c47a7b-20d1-42a2-8833-5c437b3c8a82	24	13	9	5	2026-11-05	14:00:00	30	Evaluación venosa de retorno	\N	\N	\N
25	785e7c23-e059-48b8-ab69-e7f03ea94ef4	25	6	4	5	2026-11-05	10:00:00	30	Molestia pélvica ocasional	\N	\N	\N
26	643d09d2-c6e8-43bb-a972-2eb241bd88b0	26	1	1	2	2026-11-09	08:00:00	30	Chequeo laboral de admisión	\N	\N	\N
27	785a6649-8be5-46fb-bb92-96d66f046947	27	2	1	2	2026-11-09	08:30:00	30	Evaluación de fatiga crónica	\N	\N	\N
28	fa89f3d1-c519-407a-ab3f-ab4e04c1f0e6	28	3	2	2	2026-11-09	09:00:00	30	Vacunación y refuerzo pediátrico	\N	\N	\N
29	9afcecba-5875-455c-a517-9be134f5e91c	29	5	3	2	2026-11-09	08:00:00	30	Seguimiento de presión arterial	\N	\N	\N
30	0f9362fd-9819-49df-9cbd-21f38651344a	30	8	5	2	2026-11-09	14:00:00	30	Lumbalgia aguda posparto	\N	\N	\N
31	39adcee8-245a-40a0-a135-6cb7be5e816d	31	10	7	1	2026-11-11	14:00:00	30	Hormigueo en mano izquierda	\N	\N	\N
32	918625af-a22f-431b-b07f-660064c53dcb	32	11	7	1	2026-11-12	15:00:00	30	Temblor postural leve	\N	\N	\N
33	93bcede2-bd27-4969-8eac-0eb8727a9078	33	14	10	1	2026-11-11	08:30:00	30	Tos seca persistente poscovid	\N	\N	\N
34	24f57558-660b-478a-8231-25b321b90d8d	34	17	13	1	2026-11-10	08:00:00	30	Obstrucción nasal unilateral	\N	\N	\N
35	340538d9-b73f-42ad-9072-f8275df13dff	35	20	16	1	2026-11-10	09:00:00	30	Dolor articular matutino rígido	\N	\N	\N
38	e148aa27-5ca6-4216-992f-a7a437c3c568	29	13	9	4	2026-10-01	08:00:00	30	Várices en miembros inferiores.	\N	Reagendamiento solicitado por cruce de horarios laborales.	\N
39	1ab4fe30-fea7-4568-82b5-32bb8b9ef9ae	81	5	3	3	2026-10-01	09:30:00	30	Palpitaciones y disnea de esfuerzo.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-01 09:25:00
40	88df6145-e537-462e-929f-48b10c9711cc	108	1	1	3	2026-10-02	14:00:00	30	Cuadro de faringitis y fiebre.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-02 13:49:00
41	2441c027-4353-4ed8-979b-c4956ebbae8c	15	13	9	5	2026-10-02	15:00:00	30	Várices en miembros inferiores.	\N	Paciente no acudió a su cita programada.	\N
42	712b6d2c-4646-4e0c-9afa-af0279868e98	81	10	7	3	2026-10-05	11:00:00	30	Estudio de mareos y desequilibrio.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-05 10:51:00
43	f27981ce-d396-41a5-a75d-6b94f2cb9605	9	8	5	3	2026-10-05	08:00:00	30	Dolor en rodilla derecha al caminar.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-05 07:47:00
44	aa4fe06d-29e6-4e30-9775-2e3f780ecc30	102	21	17	3	2026-10-06	10:30:00	30	Chequeo urológico preventivo.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-06 10:22:00
45	f2787e58-191e-4d8a-af38-5ac873f837a3	87	6	4	3	2026-10-06	11:00:00	30	Papanicolaou preventivo anual.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-06 10:45:00
46	8a06e73f-0b71-489c-ac61-645bd1054199	26	6	4	3	2026-10-07	14:30:00	30	Papanicolaou preventivo anual.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-07 14:23:00
47	21f7e3d6-fddb-46d2-9fcf-044129887ba7	77	12	8	4	2026-10-07	10:00:00	30	Mastalgia cíclica bilateral.	\N	Reagendamiento solicitado por cruce de horarios laborales.	\N
48	4d77a483-4e2c-485f-9144-7c4344616009	6	12	8	3	2026-10-08	10:00:00	30	Mastalgia cíclica bilateral.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-08 09:54:00
49	bbb61604-7276-448f-9293-ca5d0b9f57a6	19	1	1	3	2026-10-08	16:30:00	30	Chequeo de rutina de laboratorio.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-08 16:22:00
50	ed96ec1a-cd3e-4c50-85c2-0368dbb29c57	21	18	14	4	2026-10-09	10:30:00	30	Tratamiento de hemorroides.	\N	El paciente notificó imposibilidad de acudir por lluvia intensa.	\N
51	f24229e3-48bb-4761-8699-8479b5bad36b	115	6	4	3	2026-10-09	08:30:00	30	Papanicolaou preventivo anual.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-10-09 08:16:00
52	79aba860-4590-4870-a6e2-3dc8d985297f	21	21	17	3	2026-10-12	09:00:00	30	Chequeo urológico preventivo.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-10-12 08:50:00
53	61dd331a-96b1-444a-a5f3-7e6f67da249a	34	19	15	3	2026-10-12	17:00:00	30	Manejo de estrés y ansiedad laboral.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-12 16:51:00
54	c9f779a9-706e-49c3-bb1c-0c3d8a9c01db	102	15	11	4	2026-10-13	09:00:00	30	Asesoría dietética para hipertensos.	\N	Cancelado por compromisos familiares del paciente.	\N
55	eeb61893-dfda-4b0a-b6a5-f297f0ff0125	35	16	12	5	2026-10-13	08:00:00	30	Control de agudeza visual y lentes.	\N	Paciente no acudió a su cita programada.	\N
56	17cd27e6-6f83-46b7-9fc6-9f70edac83ee	114	4	2	3	2026-10-14	14:00:00	30	Control del lactante y vacunas.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-14 13:45:00
57	eada36bd-b682-4905-98d6-610bdae3819a	14	19	15	3	2026-10-14	08:00:00	30	Sintomatología depresiva.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-10-14 07:55:00
58	0e60c13d-aa2f-4399-b8e5-6a0613c6c584	77	9	6	3	2026-10-15	14:30:00	30	Control dermatoscópico de lunares.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-15 14:23:00
59	54f4e42b-a498-4442-8063-1b13991c09f8	15	4	2	4	2026-10-15	17:00:00	30	Control del lactante y vacunas.	\N	Reagendamiento solicitado por cruce de horarios laborales.	\N
60	5b527c1f-568b-4c43-aa63-3fc1b5ffaf41	4	1	1	3	2026-10-16	08:00:00	30	Cuadro de faringitis y fiebre.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-10-16 07:55:00
61	e5cf4309-bff8-49ac-b28b-a2059f64b262	23	17	13	3	2026-10-16	15:00:00	30	Congestión nasal crónica.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-16 14:51:00
62	3be80d02-22d8-40bc-aabf-b2490f7ceba3	108	15	11	3	2026-10-19	15:00:00	30	Asesoría dietética para hipertensos.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-10-19 14:54:00
63	e45955d3-0c1f-4aef-893b-3a85b8f483b4	111	21	17	4	2026-10-19	10:00:00	30	Control de hiperplasia prostática.	\N	Reagendamiento solicitado por cruce de horarios laborales.	\N
64	946313ab-569b-414d-bc30-d041d5e6d015	104	6	4	3	2026-10-20	09:00:00	30	Control prenatal ecográfico.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-20 08:46:00
65	0a371fb4-aa5a-4bff-8661-04ccbee9812c	106	5	3	3	2026-10-20	10:30:00	30	Control de hipertensión arterial.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-20 10:24:00
66	70a3a39e-bf1c-4cb8-8ff2-72740a2aabb1	12	6	4	3	2026-10-21	08:30:00	30	Control prenatal ecográfico.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-21 08:18:00
67	14dea606-6f14-4b66-b239-57f81087f15f	18	8	5	3	2026-10-21	17:00:00	30	Dolor en rodilla derecha al caminar.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-21 16:49:00
68	6253af00-b985-4bde-a55f-f5ede3c568cf	106	3	2	5	2026-10-22	14:00:00	30	Cuadro gripal y congestión nasal.	\N	Paciente no acudió a su cita programada.	\N
69	e61b5a2e-7492-4311-a397-dfc53b63d66a	96	18	14	3	2026-10-22	08:00:00	30	Molestias ano-rectales.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-22 07:47:00
70	34f78760-efcd-4bca-871a-c0082f707169	32	17	13	3	2026-10-23	17:30:00	30	Congestión nasal crónica.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-23 17:16:00
71	b8f7cced-bc68-4d17-a830-58401c9bd86b	89	5	3	5	2026-10-23	15:30:00	30	Control de hipertensión arterial.	\N	Paciente no acudió a su cita programada.	\N
72	d0946717-09e3-4dbe-bd2c-08abca3b4462	15	8	5	3	2026-10-26	10:00:00	30	Lumbalgia post esfuerzo físico.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-26 09:50:00
73	48bcbbc7-474d-41c9-a9a4-5eb4f5acea17	91	5	3	3	2026-10-26	10:00:00	30	Palpitaciones y disnea de esfuerzo.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-26 09:50:00
74	1f991fc8-9215-4124-aa89-f27c69848e63	87	18	14	3	2026-10-27	10:30:00	30	Molestias ano-rectales.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-27 10:24:00
75	6c7a3d43-619a-4b0b-bc2e-12f997a89a6e	91	20	16	3	2026-10-27	17:00:00	30	Dolor e inflamación articular matutina.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-10-27 16:51:00
76	ad905405-d959-4aa5-92ec-c162ebf19bd1	88	15	11	5	2026-10-28	09:30:00	30	Plan nutricional para reducción de peso.	\N	Paciente no acudió a su cita programada.	\N
77	0ab7409e-23eb-4b75-a033-f3f57cac2100	111	12	8	3	2026-10-28	15:30:00	30	Mastalgia cíclica bilateral.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-10-28 15:24:00
78	78b0094e-f26e-4849-8ae3-8e46e249f803	102	1	1	3	2026-10-29	16:30:00	30	Control de salud semestral.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-10-29 16:18:00
79	e14c9c6f-e98e-45e9-9f62-048e5bec632a	98	9	6	5	2026-10-29	16:00:00	30	Control dermatoscópico de lunares.	\N	Paciente no acudió a su cita programada.	\N
80	1333f5ba-c5e7-43ae-b833-c7df55441e02	98	12	8	4	2026-10-30	17:30:00	30	Mastalgia cíclica bilateral.	\N	Cancelado por compromisos familiares del paciente.	\N
81	e9215fd1-bd10-44a6-85e7-1ef4d2ff75be	8	15	11	3	2026-10-30	16:00:00	30	Plan nutricional para reducción de peso.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-10-30 15:45:00
82	4fb4bf40-e6e2-4036-b605-a855928151e5	27	20	16	3	2026-11-02	16:30:00	30	Control de artritis.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-11-02 16:15:00
83	6e8f0104-1567-4d98-801f-6de3c095fc6d	88	18	14	5	2026-11-02	17:30:00	30	Molestias ano-rectales.	\N	Paciente no acudió a su cita programada.	\N
84	bd44323c-1a66-4385-9f96-e1bac488481e	84	14	10	5	2026-11-03	17:30:00	30	Control de asma bronquial.	\N	Paciente no acudió a su cita programada.	\N
85	f2281756-bbc8-41d9-bc70-ca9c927b807a	32	13	9	3	2026-11-03	08:30:00	30	Pesadez vespertina en piernas.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-11-03 08:24:00
86	8db5c3f8-f810-4408-8551-37cc151917dc	77	7	5	3	2026-11-04	09:00:00	30	Esguince de tobillo post deportivo.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-11-04 08:46:00
87	bead4612-6de2-4997-be4a-6f15d97cde6b	85	1	1	4	2026-11-04	10:00:00	30	Control de salud semestral.	\N	Cancelado por compromisos familiares del paciente.	\N
88	a854fdf3-7261-43ee-b4d6-1de72c474e1e	7	8	5	3	2026-11-05	14:30:00	30	Esguince de tobillo post deportivo.	Paciente evaluado en buenas condiciones generales. Tratamiento actual bien tolerado. Se ajustan pautas higiénico-dietéticas. Próximo control en 3 meses.	\N	2026-11-05 14:18:00
89	7417aeaf-e140-4e42-b329-0477aa95d6ee	34	21	17	3	2026-11-05	15:00:00	30	Control de hiperplasia prostática.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-11-05 14:51:00
90	9ba16ab7-09fc-425d-becd-b17107c4abc2	6	14	10	4	2026-11-06	11:00:00	30	Tos seca persistente post viral.	\N	Cancelado por compromisos familiares del paciente.	\N
91	eba6b138-e24d-4114-ba1a-34a2cdab581e	8	18	14	5	2026-11-06	17:00:00	30	Tratamiento de hemorroides.	\N	Paciente no acudió a su cita programada.	\N
92	e171bab1-99bf-43a0-a735-49243a53cfb2	8	13	9	5	2026-11-09	11:00:00	30	Várices en miembros inferiores.	\N	Paciente no acudió a su cita programada.	\N
93	89fec1f4-fd33-4af9-bfb5-1875fd54e95b	17	12	8	3	2026-11-09	08:30:00	30	Mastalgia cíclica bilateral.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-11-09 08:20:00
94	3888f768-1be1-4ad7-b634-5edad45bbdf8	32	18	14	3	2026-11-10	10:30:00	30	Molestias ano-rectales.	Consulta concluida con éxito. Paciente refiere notable mejoría tras indicaciones previas. Alta temporal.	\N	2026-11-10 10:22:00
95	b8f196dd-7ef1-463f-ad70-e42aa15e037e	2	3	2	3	2026-11-10	09:00:00	30	Cuadro gripal y congestión nasal.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-11-10 08:48:00
96	a61c1c5c-854e-4d23-9665-2f4fa266f6d4	10	1	1	3	2026-11-11	17:00:00	30	Control de salud semestral.	Evolución favorable del cuadro. Se renueva prescripción médica y se otorgan recomendaciones preventivas.	\N	2026-11-11 16:51:00
97	77e03b30-e8ba-4595-a98b-5b64e8270d17	26	18	14	3	2026-11-11	17:00:00	30	Tratamiento de hemorroides.	Examen clínico sin signos de alarma. Signos vitales estables. Se indican análisis complementarios y se prescribe tratamiento sintomático.	\N	2026-11-11 16:50:00
98	d04ba9e8-3dff-4350-8057-54567589cf3b	76	21	17	2	2026-11-13	11:30:00	30	Chequeo urológico preventivo.	\N	\N	\N
99	0ae7df5b-a48c-4e74-ad87-5b20a0d84459	76	16	12	1	2026-11-13	10:30:00	30	Sequedad ocular e irritación.	\N	\N	\N
100	27afc625-c333-4f0a-8b97-d2b1a624b8ef	12	3	2	1	2026-11-16	09:30:00	30	Control de crecimiento y desarrollo.	\N	\N	\N
101	7587d3f8-19e0-4112-a456-4531cec5324a	7	8	5	1	2026-11-16	10:00:00	30	Esguince de tobillo post deportivo.	\N	\N	\N
102	49e54d0b-058b-4e7a-a50d-bc226b8e2d0d	106	4	2	1	2026-11-17	11:30:00	30	Cuadro gripal y congestión nasal.	\N	\N	\N
103	fdf15aea-e654-400e-9dab-5fbdb6c49909	23	21	17	1	2026-11-17	10:30:00	30	Chequeo urológico preventivo.	\N	\N	\N
104	49329663-1690-4c9d-b36a-3cf41c84a4b5	23	3	2	2	2026-11-18	15:00:00	30	Control del lactante y vacunas.	\N	\N	\N
105	2abc3c30-9a9c-47ea-a24a-056b3c408fc1	23	11	7	2	2026-11-18	10:30:00	30	Evaluación de cefaleas recurrentes.	\N	\N	\N
106	4b2f7006-9cf8-4104-9f96-161ad8c09e69	78	10	7	2	2026-11-19	11:30:00	30	Estudio de mareos y desequilibrio.	\N	\N	\N
107	27a0f85a-52eb-45ca-81c1-a40d25cfd200	90	1	1	1	2026-11-19	10:00:00	30	Control de salud semestral.	\N	\N	\N
108	3f7664c8-6ce5-48d9-befe-b386f6771c52	9	20	16	1	2026-11-20	09:30:00	30	Control de artritis.	\N	\N	\N
109	7780809f-809f-4e0c-b753-30ebbf1efce1	88	17	13	2	2026-11-20	16:30:00	30	Congestión nasal crónica.	\N	\N	\N
110	afda5963-c76c-48bf-8d7b-8fedbae47403	4	9	6	1	2026-11-23	10:30:00	30	Tratamiento de acné facial.	\N	\N	\N
111	2ee64bc4-8661-4973-a1c3-90a478067d6b	6	13	9	1	2026-11-23	14:30:00	30	Pesadez vespertina en piernas.	\N	\N	\N
112	ebb71684-a9d0-4e94-a8bc-36a89c776d7e	3	8	5	1	2026-11-24	15:30:00	30	Esguince de tobillo post deportivo.	\N	\N	\N
113	fd29e055-feb5-440d-ae01-3e8bfbfe1937	114	9	6	1	2026-11-24	15:30:00	30	Tratamiento de acné facial.	\N	\N	\N
114	33a33fa9-cd94-4d57-aad9-982d0112b6d1	35	5	3	1	2026-11-25	09:30:00	30	Evaluación de riesgo quirúrgico.	\N	\N	\N
115	d85e29ef-375b-41c3-b263-4752c18d4b5b	32	19	15	1	2026-11-25	15:30:00	30	Manejo de estrés y ansiedad laboral.	\N	\N	\N
116	523b5fa9-3b5c-4345-be4f-1d5d3001e112	97	3	2	1	2026-11-26	11:30:00	30	Control del lactante y vacunas.	\N	\N	\N
117	e290396b-87fd-4c5f-b017-3725e1908cea	108	12	8	1	2026-11-26	17:00:00	30	Mastalgia cíclica bilateral.	\N	\N	\N
118	9dfdf2af-dc74-4ad1-a4b6-d064d820b9fe	102	18	14	1	2026-11-27	16:30:00	30	Molestias ano-rectales.	\N	\N	\N
119	50bb32f9-7352-4f31-adfd-765a8de148f9	109	14	10	1	2026-11-27	15:00:00	30	Tos seca persistente post viral.	\N	\N	\N
120	2f1051d9-e128-40e3-8617-66e75e6c4ff7	26	4	2	1	2026-11-30	10:00:00	30	Cuadro gripal y congestión nasal.	\N	\N	\N
121	4e003c8c-3ebc-41fd-bb1e-8406e6c77e08	83	16	12	1	2026-11-30	10:30:00	30	Sequedad ocular e irritación.	\N	\N	\N
122	3e8f109a-8772-4fea-a48d-7b86a8565a6d	92	13	9	1	2026-12-01	10:00:00	30	Pesadez vespertina en piernas.	\N	\N	\N
123	956fda7e-8dc0-41a6-9b7f-58e9e4854d4a	76	9	6	1	2026-12-01	08:00:00	30	Eccema y prurito en antebrazos.	\N	\N	\N
124	ce855a8d-c2fb-479e-9d97-c5f53179ce93	84	16	12	1	2026-12-02	11:30:00	30	Control de agudeza visual y lentes.	\N	\N	\N
125	6781652c-ccad-4dc3-9b27-ee790d6ed5a9	29	4	2	1	2026-12-02	16:00:00	30	Cuadro gripal y congestión nasal.	\N	\N	\N
126	c2963460-e47e-454b-a68e-e9244c07f1d4	115	9	6	1	2026-12-03	16:30:00	30	Tratamiento de acné facial.	\N	\N	\N
127	9c35fcb0-e31f-42b3-9792-d0b426549dc8	98	9	6	1	2026-12-03	14:30:00	30	Control dermatoscópico de lunares.	\N	\N	\N
128	a4198a49-32eb-4611-9a42-7c01586c84d6	79	9	6	1	2026-12-04	08:30:00	30	Eccema y prurito en antebrazos.	\N	\N	\N
129	903469c5-4640-4dc8-9b6d-99ef455bd91e	16	7	5	1	2026-12-04	14:30:00	30	Esguince de tobillo post deportivo.	\N	\N	\N
130	a6ec7c7e-1abc-4408-a0ea-1a4cea50f305	8	14	10	1	2026-12-07	16:30:00	30	Tos seca persistente post viral.	\N	\N	\N
131	9aa3b6b6-9a58-4dd1-b4c2-71d8250de607	17	19	15	1	2026-12-07	10:00:00	30	Sintomatología depresiva.	\N	\N	\N
132	65893c28-de2b-4a58-9256-ca67bf10db82	11	1	1	1	2026-12-08	08:00:00	30	Control de salud semestral.	\N	\N	\N
133	77a46fa2-c228-4dad-9750-4307a8f421d8	13	3	2	1	2026-12-08	09:30:00	30	Control de crecimiento y desarrollo.	\N	\N	\N
134	bc52c203-6843-4f37-9bb3-0d184dd4643d	5	8	5	1	2026-12-09	15:30:00	30	Lumbalgia post esfuerzo físico.	\N	\N	\N
135	05cee8d9-3e6c-4171-935d-2bd9a2838efe	102	11	7	1	2026-12-09	08:30:00	30	Estudio de mareos y desequilibrio.	\N	\N	\N
136	30ff9564-7250-4e5a-b44e-664c1bc50a66	85	2	1	1	2026-12-10	17:30:00	30	Chequeo de rutina de laboratorio.	\N	\N	\N
137	33a6bea4-5c6b-4d6c-b9ce-4f3a1351e6f3	111	19	15	1	2026-12-10	17:30:00	30	Manejo de estrés y ansiedad laboral.	\N	\N	\N
138	d7e993cf-df75-4bbb-8c5c-10dfae480e7a	20	1	1	1	2026-12-11	09:00:00	30	Cefalea tensional frecuente.	\N	\N	\N
139	fdd4bd3f-b9c5-4e5a-848d-6f335b53b0a3	1	19	15	1	2026-12-11	10:30:00	30	Sintomatología depresiva.	\N	\N	\N
140	4feee52a-0889-43a3-b064-7b064b63f3fa	22	16	12	1	2026-12-14	09:00:00	30	Control de agudeza visual y lentes.	\N	\N	\N
141	ba8a753f-7c08-47c8-80a3-0f2b8b139a36	21	11	7	1	2026-12-14	08:00:00	30	Estudio de mareos y desequilibrio.	\N	\N	\N
142	2aa53724-5e0f-4b1f-a4df-42da681acaaa	27	16	12	1	2026-12-15	15:00:00	30	Control de agudeza visual y lentes.	\N	\N	\N
143	e4fd7abc-d1a2-4c76-aea7-a02eeecbdabf	89	4	2	1	2026-12-15	09:30:00	30	Control de crecimiento y desarrollo.	\N	\N	\N
144	3ad425bc-5ab3-4014-8e9b-1268b5b8d815	85	1	1	4	2026-10-23	10:00:00	30	Control de glucemia y perfil lipídico	\N	Médico convocado de urgencia a cirugía en centro hospitalario de convenio.	\N
145	9da401b4-6777-4423-a49e-d4fc20c0a1f6	83	1	1	4	2026-09-22	16:30:00	30	Chequeo clínico general preventivo	\N	Médico convocado de urgencia a cirugía en centro hospitalario de convenio.	\N
146	db5d05c0-d179-4c63-adeb-38f3e0d36a65	3	1	1	4	2026-10-07	17:30:00	30	Cuadro gripal persistente	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
147	52cc0366-7dea-460d-937d-f2228d28ee28	96	2	1	4	2026-09-22	09:00:00	30	Control de glucemia y perfil lipídico	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
148	2a013a79-de3d-4ebc-af3a-d6c1c3ea4f91	94	1	1	3	2026-10-27	10:00:00	30	Cuadro gripal persistente	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-27 09:47:00
149	897b8dcf-0f24-412e-8806-4137b3a557c4	83	1	1	3	2026-10-29	17:30:00	30	Control de glucemia y perfil lipídico	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-29 17:15:00
150	538c4868-08e0-49d5-b5d4-6cbc10544086	92	2	1	4	2026-09-24	14:00:00	30	Evaluación de fatiga y cefaleas	\N	Paciente solicitó cambio de fecha por motivos personales.	\N
151	ec20a880-0b49-4fac-a237-ebd601546ad0	10	1	1	3	2026-09-24	17:30:00	30	Evaluación de fatiga y cefaleas	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-24 17:14:00
152	29adc9e6-c44c-4614-976a-a33d7ddfff8f	96	2	1	3	2026-11-11	11:00:00	30	Chequeo clínico general preventivo	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-11-11 10:54:00
153	573edb30-3d88-468e-8550-3de9f3905dc8	8	2	1	4	2026-10-28	16:00:00	30	Control de glucemia y perfil lipídico	\N	Paciente reportó imprevisto laboral de urgencia y no pudo asistir.	\N
154	7a9d1ace-10e3-478d-9bf9-5427e892d709	86	2	1	4	2026-09-18	08:00:00	30	Evaluación de fatiga y cefaleas	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
155	6977da9d-885a-400c-a26c-53a7f098770e	113	2	1	3	2026-09-28	10:30:00	30	Control de glucemia y perfil lipídico	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-28 10:22:00
156	a2a1e836-a953-4e16-8f8c-26bcd73793bf	114	1	1	4	2026-10-07	17:30:00	30	Evaluación de fatiga y cefaleas	\N	Imposibilidad de transporte por bloqueo y fuertes lluvias en la ciudad.	\N
157	c9e3d7c9-f9e6-400b-83c4-43d73423c177	98	1	1	3	2026-10-22	15:30:00	30	Control de glucemia y perfil lipídico	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-22 15:16:00
158	778dae41-5a7f-4c20-a480-823fd42f8ea2	88	1	1	3	2026-10-07	10:00:00	30	Cuadro gripal persistente	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-07 09:52:00
159	e3700624-5be2-46b1-bd7b-d417d37c13ed	27	1	1	4	2026-09-23	15:30:00	30	Control de glucemia y perfil lipídico	\N	Médico convocado de urgencia a cirugía en centro hospitalario de convenio.	\N
160	3b91a73e-0ec3-4b07-bc9b-ad8ea55ef6f5	32	1	1	4	2026-09-25	14:30:00	30	Cuadro gripal persistente	\N	Paciente reagendó consulta para el siguiente mes por viaje de trabajo.	\N
161	7b564993-e445-41dd-8a55-d1ecffb18e13	104	2	1	3	2026-09-22	09:00:00	30	Control de glucemia y perfil lipídico	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-22 08:54:00
162	1a86d606-03e3-44a4-83f0-9f8c5cf7e803	17	1	1	4	2026-10-23	10:30:00	30	Chequeo clínico general preventivo	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
163	adb33d18-e7fb-471b-b87a-69a02bd2b92e	28	1	1	3	2026-11-12	08:30:00	30	Evaluación de fatiga y cefaleas	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-11-12 08:23:00
164	e8da281d-4c56-4775-88b8-0ee2ba6d2a2d	11	1	1	4	2026-09-23	14:00:00	30	Control de glucemia y perfil lipídico	\N	Paciente reportó imprevisto laboral de urgencia y no pudo asistir.	\N
165	fd8bd249-af98-43cc-a8fa-72d8bb40e115	76	1	1	3	2026-11-04	11:00:00	30	Cuadro gripal persistente	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-11-04 10:55:00
166	e6f5813c-887f-4b3d-91d1-a6a1015e6974	98	1	1	5	2026-09-23	14:00:00	30	Chequeo clínico general preventivo	\N	Paciente no se presentó en horario fijado.	\N
167	7a7174d3-ab6a-4b74-a441-fae93a67e8ce	76	2	1	4	2026-10-09	16:30:00	30	Evaluación de fatiga y cefaleas	\N	Médico convocado de urgencia a cirugía en centro hospitalario de convenio.	\N
168	69e71073-dde0-45fc-b1a7-bf3c94b2dff4	78	2	1	4	2026-10-16	09:30:00	30	Evaluación de fatiga y cefaleas	\N	Paciente viajó al interior por emergencia familiar.	\N
169	cabcf8bc-dbcc-48d6-8952-c74b54eeacbb	8	2	1	3	2026-09-21	15:30:00	30	Cuadro gripal persistente	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-21 15:21:00
170	a63e22f1-8265-4396-9492-6f3fc47e5a18	34	2	1	4	2026-11-11	08:00:00	30	Chequeo clínico general preventivo	\N	El paciente notificó que presentó mejoría de sus síntomas y prefirió cancelar.	\N
171	6096a311-4815-4998-a889-cde24c2acee9	27	1	1	4	2026-10-09	08:00:00	30	Evaluación de fatiga y cefaleas	\N	Cancelada por solicitud de atención prioritaria en guardia de emergencia.	\N
172	e911bc04-262f-414a-bb72-d73404665ece	82	2	1	5	2026-09-28	14:00:00	30	Control de glucemia y perfil lipídico	\N	Paciente no se presentó en horario fijado.	\N
173	a2ef525d-f0d2-4cac-bd21-285fba2b2983	86	2	1	4	2026-09-17	17:00:00	30	Cuadro gripal persistente	\N	Cancelada por solicitud de atención prioritaria en guardia de emergencia.	\N
174	13b4fbaa-cd2a-444b-869a-64501af147c0	89	2	1	3	2026-11-05	14:30:00	30	Cuadro gripal persistente	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-11-05 14:18:00
175	de7f075e-d75d-4f0d-bbc9-512caf6be0d7	83	2	1	4	2026-10-28	16:00:00	30	Evaluación de fatiga y cefaleas	\N	Cruce de horarios laborales; el paciente solicitó reprogramación telefónica.	\N
176	7ac3b587-7c38-4821-a113-5ec814e0ee47	5	1	1	4	2026-10-08	10:00:00	30	Chequeo clínico general preventivo	\N	Paciente solicitó cambio de fecha por motivos personales.	\N
177	653484d9-2e21-4f58-b992-de0e1c3aa0aa	17	2	1	4	2026-10-07	15:00:00	30	Evaluación de fatiga y cefaleas	\N	Médico convocado de urgencia a cirugía en centro hospitalario de convenio.	\N
178	82e55e95-9541-4902-8120-51a692ae7331	83	1	1	3	2026-09-25	16:30:00	30	Control de glucemia y perfil lipídico	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-25 16:23:00
179	7dfc02f0-3aab-4655-a551-74e28dbc053c	22	3	2	4	2026-10-29	10:00:00	30	Revisión del esquema de vacunación	\N	Médico convocado de urgencia a cirugía en centro hospitalario de convenio.	\N
180	4cf68191-8179-443b-a8d1-2285a109a05e	90	3	2	4	2026-09-21	17:30:00	30	Control de talla y peso pediátrico	\N	El paciente notificó que presentó mejoría de sus síntomas y prefirió cancelar.	\N
181	919dfaa2-e1de-4ffa-8bf0-f86ddbd50036	85	4	2	5	2026-09-29	16:30:00	30	Control del lactante menor	\N	Paciente no se presentó en horario fijado.	\N
182	a7274dda-769e-4b58-8b5d-506933bb4d9f	91	3	2	3	2026-10-09	08:00:00	30	Evaluación respiratoria infantil	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-09 07:42:00
183	f432730c-5c33-4ae7-88f9-40035c227ffe	28	3	2	3	2026-10-09	16:00:00	30	Control de talla y peso pediátrico	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-09 15:49:00
184	3f28aeca-2bea-40b5-92b5-6ad611accc64	77	4	2	3	2026-10-02	10:30:00	30	Revisión del esquema de vacunación	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-02 10:23:00
185	ca6d84ed-6e39-4356-8356-c59cfbdefaa3	87	4	2	5	2026-10-15	11:00:00	30	Evaluación respiratoria infantil	\N	Paciente no se presentó en horario fijado.	\N
186	9be4296e-595d-4aed-a683-ae3496c4f4bc	97	4	2	4	2026-09-16	11:00:00	30	Control de talla y peso pediátrico	\N	Paciente reportó imprevisto laboral de urgencia y no pudo asistir.	\N
187	4c8241e1-efff-4d0e-8704-91d7244029b8	109	4	2	3	2026-09-28	09:30:00	30	Control de talla y peso pediátrico	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-28 09:25:00
188	ca573e0a-2716-4e92-aa48-223aa329f233	89	3	2	4	2026-10-07	15:00:00	30	Evaluación respiratoria infantil	\N	Imposibilidad de transporte por bloqueo y fuertes lluvias en la ciudad.	\N
189	0652ce0b-7bbe-43dd-93fe-9858ad8251ce	107	4	2	3	2026-09-22	09:00:00	30	Evaluación respiratoria infantil	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-22 08:52:00
190	5c3c7170-06d2-4038-b117-572ac8934550	17	4	2	3	2026-10-22	15:30:00	30	Evaluación respiratoria infantil	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-22 15:16:00
191	43b95f64-cf3c-482b-9074-2a9c4dd1f83b	107	4	2	5	2026-11-04	10:30:00	30	Control del lactante menor	\N	Paciente no se presentó en horario fijado.	\N
192	0d5a080d-5391-476a-9d59-0a3e8c71c944	82	4	2	3	2026-09-30	16:30:00	30	Evaluación respiratoria infantil	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-30 16:12:00
193	afe12af8-63ee-4509-b7bf-fbf59c5e1746	8	3	2	4	2026-09-21	16:00:00	30	Evaluación respiratoria infantil	\N	Cancelada por solicitud de atención prioritaria en guardia de emergencia.	\N
194	6dcc5f1d-0016-428b-92de-949da18c6ff7	107	3	2	3	2026-09-28	09:00:00	30	Revisión del esquema de vacunación	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-28 08:55:00
195	ed79b785-fe30-4c03-835c-decd153f1a42	7	3	2	3	2026-09-29	10:30:00	30	Control de talla y peso pediátrico	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-29 10:25:00
196	f0d0cd8e-9af8-4a85-9170-b87fd5198c01	12	3	2	3	2026-09-25	14:00:00	30	Control del lactante menor	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-25 13:54:00
197	38e264ad-148d-4dd5-9c9c-3009603a6fcc	14	3	2	3	2026-10-29	11:00:00	30	Control de talla y peso pediátrico	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-29 10:48:00
198	3e428187-8938-4484-ae61-dce8542dad4c	11	3	2	3	2026-09-22	16:00:00	30	Control del lactante menor	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-22 15:53:00
199	71df0b08-c7ec-4f6e-b209-e14f1604bd5d	20	4	2	3	2026-10-29	14:30:00	30	Evaluación respiratoria infantil	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-29 14:16:00
200	1e343191-2a97-4ab7-8d7e-e6c465bc302c	76	4	2	3	2026-09-18	15:30:00	30	Control del lactante menor	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-18 15:12:00
201	5c3443ed-62de-4673-89a6-97a28f9a121c	2	4	2	4	2026-11-11	09:00:00	30	Control del lactante menor	\N	Imposibilidad de transporte por bloqueo y fuertes lluvias en la ciudad.	\N
202	f5a84af2-c9a0-45ca-8666-54640cc9029f	89	4	2	3	2026-09-23	16:00:00	30	Control de talla y peso pediátrico	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-23 15:53:00
203	315f3fdc-d16a-4119-941a-ba2d8633e37b	14	4	2	3	2026-10-13	10:00:00	30	Control de talla y peso pediátrico	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-13 09:53:00
204	bfb7c4b6-95e4-4e00-a525-34de4553a019	3	3	2	5	2026-10-19	17:30:00	30	Control del lactante menor	\N	Paciente no se presentó en horario fijado.	\N
205	8fe8f9d5-e75a-4d1f-9088-6cc84bb4722f	5	3	2	4	2026-10-06	17:30:00	30	Revisión del esquema de vacunación	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
206	f73de364-76b0-4731-8240-7ea3578711c6	18	3	2	3	2026-09-23	09:30:00	30	Evaluación respiratoria infantil	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-23 09:18:00
207	84ff169d-4a04-41bf-afa3-ce32a7948ebf	107	3	2	4	2026-10-23	11:30:00	30	Control de talla y peso pediátrico	\N	El paciente notificó que presentó mejoría de sus síntomas y prefirió cancelar.	\N
208	e180c08d-811f-47a7-89da-b3e1b3083291	13	4	2	3	2026-10-22	17:30:00	30	Control de talla y peso pediátrico	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-22 17:24:00
209	ce45363f-01f4-4d6e-8596-2b849091fea7	29	5	3	3	2026-10-26	17:30:00	30	Ecocardiografía de control	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-26 17:21:00
210	fad4dd6c-a490-4399-ba67-51f04cd7029f	17	5	3	3	2026-10-23	10:30:00	30	Evaluación preoperatoria cardiológica	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-23 10:20:00
211	c46227c6-43fa-458a-8d84-1d7f0fced184	21	5	3	3	2026-10-30	10:00:00	30	Evaluación preoperatoria cardiológica	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-30 09:44:00
212	d3186f63-1d9a-48b6-ac0e-2e82d863925f	18	5	3	4	2026-10-07	17:00:00	30	Control de presión arterial e hipertensión	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
213	f9ca8164-90a6-4ffd-97f1-d0deea0abc59	88	5	3	3	2026-10-26	15:00:00	30	Monitoreo post infarto agudo	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-26 14:50:00
214	21593e27-5cbf-4ac0-ba53-56d431a6e037	85	5	3	4	2026-11-02	08:30:00	30	Control de presión arterial e hipertensión	\N	Cancelación preventiva solicitada por el asegurador o convenio empresarial.	\N
215	6205471c-300b-4c15-a4e5-6b29f2dc0a90	97	5	3	3	2026-11-06	08:30:00	30	Evaluación preoperatoria cardiológica	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-11-06 08:15:00
216	041f4257-e548-448a-899b-1438f228c8fe	14	5	3	3	2026-10-22	11:00:00	30	Monitoreo post infarto agudo	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-22 10:50:00
217	2c9e3f3e-a92d-44c5-bb69-d85087a46d96	99	5	3	3	2026-10-12	16:00:00	30	Control de presión arterial e hipertensión	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-12 15:50:00
218	8acb999e-0453-4498-b206-a931e7f31008	82	5	3	3	2026-09-23	16:30:00	30	Evaluación preoperatoria cardiológica	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-23 16:20:00
219	eefb2119-c527-4289-adef-b351bbb579bb	2	5	3	3	2026-09-16	15:30:00	30	Ecocardiografía de control	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-16 15:20:00
220	54c26939-c1ff-4c94-b792-210be540ea0a	94	5	3	3	2026-09-23	14:00:00	30	Ecocardiografía de control	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-23 13:43:00
221	f7eb7f3a-fa83-4377-90b8-4f1861bb0599	99	5	3	3	2026-10-12	16:30:00	30	Ecocardiografía de control	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-12 16:22:00
222	6dc2b08d-3cbe-4104-8d5a-18b57097e5d1	114	5	3	3	2026-11-02	15:00:00	30	Monitoreo post infarto agudo	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-11-02 14:55:00
223	fae36c63-fc74-43a4-8f0e-1f894fc9af35	109	5	3	3	2026-11-11	17:00:00	30	Ecocardiografía de control	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-11-11 16:54:00
224	f8078c05-6138-473a-a349-3671b21578de	94	5	3	3	2026-10-15	11:30:00	30	Evaluación preoperatoria cardiológica	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-15 11:17:00
225	05c1778f-5549-4045-b6ca-ea301ac58f6b	91	5	3	3	2026-10-01	15:00:00	30	Monitoreo post infarto agudo	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-01 14:46:00
226	d9cac38b-090c-475e-96aa-a6e721f510f9	7	5	3	3	2026-10-29	15:00:00	30	Ecocardiografía de control	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-29 14:50:00
227	24180928-870e-452f-977d-f6d572d74fb5	13	5	3	3	2026-10-20	11:30:00	30	Monitoreo post infarto agudo	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-20 11:20:00
228	573356fb-d760-4094-ab6c-73d79934958b	106	5	3	3	2026-10-23	09:30:00	30	Monitoreo post infarto agudo	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-23 09:17:00
229	df8ea4cf-ab85-4b36-8564-57ed12e628b3	6	5	3	5	2026-10-19	14:00:00	30	Control de presión arterial e hipertensión	\N	Paciente no se presentó en horario fijado.	\N
230	1b7c127e-abb7-4f22-9617-52ff9e901fd6	25	5	3	3	2026-11-06	09:30:00	30	Evaluación preoperatoria cardiológica	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-11-06 09:17:00
231	42796954-6add-4ae6-b5ad-63edbdd80850	17	5	3	3	2026-10-02	17:00:00	30	Evaluación preoperatoria cardiológica	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-02 16:50:00
232	3269f11b-483b-4acf-83ee-d0e2925610fd	34	5	3	3	2026-10-15	11:00:00	30	Evaluación preoperatoria cardiológica	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-15 10:47:00
233	3bd7debb-ccc2-412c-b28f-9fdd8edd0938	13	5	3	3	2026-09-21	09:30:00	30	Evaluación preoperatoria cardiológica	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-21 09:22:00
234	a20d3462-9a13-43f0-9c2d-755121b4bb74	27	7	5	3	2026-10-20	08:00:00	30	Gonalgia derecha al subir escaleras	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-20 07:46:00
235	ca969ba5-75e1-4e66-b226-b2b1dbf8ca92	83	7	5	3	2026-10-09	09:30:00	30	Revisión post fractura de muñeca	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-09 09:19:00
236	dbc16b1b-551d-44b3-9194-e6751ee180c2	8	8	5	3	2026-10-07	08:00:00	30	Lumbalgia aguda tras levantamiento de peso	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-07 07:46:00
237	3f6c8b1d-ec5f-4279-a3a3-4b5cd905a5cb	31	8	5	3	2026-09-28	16:00:00	30	Revisión post fractura de muñeca	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-28 15:48:00
238	7f09b82a-1c34-4921-87eb-ba8f87ce93c6	91	8	5	3	2026-09-28	14:30:00	30	Revisión post fractura de muñeca	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-28 14:22:00
239	b2003d8b-8b1d-4099-a7db-45fdfac1347d	18	7	5	3	2026-10-27	10:00:00	30	Gonalgia derecha al subir escaleras	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-27 09:48:00
240	ed7c1542-d199-48a4-a75c-5f332d2f01bb	11	7	5	3	2026-10-09	08:30:00	30	Gonalgia derecha al subir escaleras	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-09 08:15:00
241	e98d3896-b640-4a26-b760-0cf961446db4	16	7	5	5	2026-09-16	14:30:00	30	Evaluación de esguince de tobillo	\N	Paciente no se presentó en horario fijado.	\N
242	09bcf218-321b-4bbf-8ef8-8fa276d3cdc8	18	7	5	3	2026-09-15	17:00:00	30	Evaluación de esguince de tobillo	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-15 16:50:00
243	8e99cdda-7598-4bf0-a02f-0a9bfa66fda0	102	8	5	3	2026-09-23	09:30:00	30	Gonalgia derecha al subir escaleras	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-23 09:19:00
244	9b68f56f-43d1-4483-bf46-09ad4877b923	30	7	5	3	2026-09-24	08:00:00	30	Evaluación de esguince de tobillo	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-24 07:42:00
245	6f584d8e-dfd9-47ac-a38a-cbf4c65bba9d	94	7	5	3	2026-10-02	16:30:00	30	Lumbalgia aguda tras levantamiento de peso	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-02 16:14:00
246	6887d804-17b1-4ae7-bdcf-4d9eaa3b96ac	110	7	5	3	2026-09-17	15:00:00	30	Evaluación de esguince de tobillo	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-17 14:45:00
247	f59115b4-f5bf-4108-8397-ccaa6a524e33	100	8	5	3	2026-10-07	17:00:00	30	Revisión post fractura de muñeca	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-07 16:46:00
248	b08827cf-f776-44c3-9f4a-bad7e1131db1	80	8	5	3	2026-09-16	15:30:00	30	Evaluación de esguince de tobillo	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-16 15:22:00
249	1b790351-0280-4900-82aa-2efa985a4a9e	23	8	5	3	2026-10-07	11:30:00	30	Lumbalgia aguda tras levantamiento de peso	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-07 11:14:00
250	3f2850be-99e6-4695-b1cf-8201ad901ae6	111	8	5	3	2026-11-04	09:00:00	30	Lumbalgia aguda tras levantamiento de peso	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-11-04 08:49:00
251	ddc24239-f76f-4902-ae7c-9393e15cfe8b	79	8	5	3	2026-10-19	14:00:00	30	Evaluación de esguince de tobillo	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-19 13:42:00
252	149cd3f2-e282-4a55-99b0-5e2d1e5fdb5a	77	8	5	3	2026-09-29	17:00:00	30	Revisión post fractura de muñeca	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-29 16:45:00
253	0e8ab41c-e945-4da4-8778-97e1caeb481b	16	7	5	3	2026-10-15	16:30:00	30	Lumbalgia aguda tras levantamiento de peso	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-15 16:14:00
254	6a10d9d3-817c-437d-8793-9b133b542306	18	6	4	3	2026-10-12	08:00:00	30	Papanicolaou de control	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-12 07:52:00
255	02cf76ac-8025-4123-a6ec-7938550b98f7	81	6	4	5	2026-10-22	17:30:00	30	Papanicolaou de control	\N	Paciente no se presentó en horario fijado.	\N
256	9f48e2a1-c5ef-4898-b0ee-acc629a93010	22	6	4	3	2026-09-16	17:00:00	30	Control prenatal ecográfico 2do trimestre	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-16 16:49:00
257	26f9566d-5ac0-473d-9d06-4a7ba01fd3ec	88	6	4	5	2026-11-03	16:00:00	30	Control prenatal ecográfico 2do trimestre	\N	Paciente no se presentó en horario fijado.	\N
258	b758e45a-49f3-4be8-85fc-8558160c5d54	77	6	4	3	2026-10-09	14:30:00	30	Control puerperal y lactancia	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-09 14:18:00
259	1e2a0265-8a2a-46ae-a952-8cf278e84e31	106	6	4	5	2026-10-01	15:00:00	30	Control prenatal ecográfico 2do trimestre	\N	Paciente no se presentó en horario fijado.	\N
260	c0e3202c-c3ca-4528-a7ca-cd988329f38d	96	6	4	3	2026-09-22	17:00:00	30	Control prenatal ecográfico 2do trimestre	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-22 16:49:00
261	5b15f70f-a32c-45a0-8b34-c8e07f81755e	23	6	4	3	2026-09-30	15:30:00	30	Evaluación ginecológica preventiva anual	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-30 15:15:00
262	ae07a305-d39d-4ddd-95e5-36edeeef54b9	111	6	4	3	2026-10-19	11:30:00	30	Papanicolaou de control	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-19 11:17:00
263	85d2d67b-27c9-484c-9d4a-4f2ea200fc91	87	6	4	5	2026-10-19	10:00:00	30	Control prenatal ecográfico 2do trimestre	\N	Paciente no se presentó en horario fijado.	\N
264	31b4aa00-982b-4d51-a5ce-4c73abc1cca1	28	6	4	3	2026-09-22	09:30:00	30	Control prenatal ecográfico 2do trimestre	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-22 09:25:00
265	d0cda0a8-06ed-4c8f-a463-ac449157b5dc	92	6	4	3	2026-10-30	11:00:00	30	Papanicolaou de control	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-30 10:44:00
266	a440ca38-afd8-4769-8e31-2d11c2bdc135	7	6	4	3	2026-09-30	17:30:00	30	Papanicolaou de control	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-30 17:17:00
267	4584cd66-0231-41d2-addb-82e79168a059	30	6	4	3	2026-10-23	14:00:00	30	Control prenatal ecográfico 2do trimestre	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-23 13:52:00
268	e2b773b4-1bbb-4980-986f-cd78bebf3f33	112	6	4	3	2026-10-20	16:00:00	30	Evaluación ginecológica preventiva anual	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-20 15:51:00
269	139ba614-841b-467a-be3f-cdf911d7f264	93	6	4	3	2026-11-05	15:00:00	30	Evaluación ginecológica preventiva anual	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-11-05 14:53:00
270	25fb8079-f0fe-4e8e-88a3-71479f3c7924	80	6	4	3	2026-09-23	14:30:00	30	Papanicolaou de control	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-23 14:22:00
271	e1a1ce80-96cf-4545-8cd6-0416b9b42f2c	12	6	4	3	2026-10-20	14:30:00	30	Papanicolaou de control	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-20 14:14:00
272	d64ab341-bf3d-44b8-a865-570289e7e497	5	9	6	3	2026-09-28	08:30:00	30	Evaluación de lesiones pigmentadas y lunares	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-28 08:16:00
273	2e01f404-d40e-4087-92af-a2bcf70f0fb8	107	9	6	3	2026-10-19	11:30:00	30	Evaluación de lesiones pigmentadas y lunares	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-19 11:17:00
274	734a555e-eaba-486c-8e5d-c7fa9216b3c2	25	9	6	3	2026-09-24	16:00:00	30	Tratamiento de dermatitis atópica	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-24 15:42:00
275	00ffe0a4-99ea-4330-9c47-f2619c68b754	81	9	6	3	2026-10-15	11:00:00	30	Tratamiento de dermatitis atópica	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-15 10:49:00
276	7d442331-8a62-4e95-bc72-bdf691620548	12	9	6	3	2026-11-05	15:30:00	30	Tratamiento de dermatitis atópica	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-11-05 15:22:00
277	2c3ebbbb-7d82-4479-a678-afb27d36d5cf	81	9	6	3	2026-11-10	08:30:00	30	Tratamiento de dermatitis atópica	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-11-10 08:17:00
278	4272a056-a906-4769-ab52-0534483a0a43	84	9	6	3	2026-11-11	15:30:00	30	Tratamiento de dermatitis atópica	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-11-11 15:25:00
279	418dba00-0393-4e32-99f6-090c90938404	111	9	6	3	2026-09-23	17:00:00	30	Evaluación de lesiones pigmentadas y lunares	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-23 16:54:00
280	cf507113-cfdc-46d5-b01c-0ab37fddce8c	94	9	6	3	2026-10-29	17:30:00	30	Brote de acné inflamatorio juvenil	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-29 17:18:00
281	cb7bb2e6-aaca-4baf-80fc-ea554f3791dc	97	9	6	3	2026-09-16	08:00:00	30	Evaluación de lesiones pigmentadas y lunares	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-16 07:54:00
282	27a01617-c644-42bb-b84c-d983dcc587b7	7	10	7	3	2026-09-30	14:30:00	30	Monitoreo de medicación neurológica	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-30 14:16:00
283	86d3edcd-f6ce-4acc-9c48-47bd9cb6cc36	16	11	7	3	2026-09-17	14:00:00	30	Monitoreo de medicación neurológica	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-17 13:45:00
284	9a185d7c-884e-46bb-8395-e521dafbd019	78	10	7	3	2026-09-21	15:30:00	30	Monitoreo de medicación neurológica	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-09-21 15:15:00
285	c13968a7-d782-44ca-b80c-fde1d2d14630	83	10	7	3	2026-11-03	17:30:00	30	Cefalea migrañosa severa con fotofobia	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-11-03 17:24:00
286	56298cf8-6167-4b44-b76a-21e79ff9c941	96	10	7	3	2026-10-09	08:30:00	30	Monitoreo de medicación neurológica	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-09 08:19:00
287	efda6154-fa3e-4166-b927-aecebf563c6f	86	11	7	3	2026-10-26	11:30:00	30	Evaluación de episodios de vértigo	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-26 11:14:00
288	bd6414b8-d162-44ab-9c1f-7fca145802ff	94	10	7	3	2026-09-29	08:00:00	30	Monitoreo de medicación neurológica	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-09-29 07:47:00
289	60840921-90f9-4191-ac1b-4b348c6d3d63	87	10	7	3	2026-09-22	11:30:00	30	Cefalea migrañosa severa con fotofobia	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-09-22 11:20:00
290	aa55d110-9065-4d1d-a09f-75165caf1ae2	5	16	12	3	2026-10-01	16:00:00	30	Ardor ocular y fatiga visual frente a pantallas	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-10-01 15:48:00
291	49e4db4e-b83d-4b0e-abe7-55428a4fecec	22	16	12	3	2026-10-19	17:30:00	30	Control de presión intraocular	Evolución clínica satisfactoria. Se refuerzan medidas dietéticas y actividad física. Cita de seguimiento programada.	\N	2026-10-19 17:17:00
292	5f9a190b-bd17-43ef-8fe4-11408420acb4	90	16	12	3	2026-10-22	11:00:00	30	Control de presión intraocular	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-10-22 10:47:00
293	69688606-28d6-4d77-ac85-db7a52c754ea	16	16	12	3	2026-09-17	08:00:00	30	Ardor ocular y fatiga visual frente a pantallas	Examen segmentario sin hallazgos patológicos agudos. Se prescriben antiinflamatorios y reposo relativo por 5 días.	\N	2026-09-17 07:55:00
294	3c37a2c9-3208-4c50-991f-f21934cd12ea	1	16	12	3	2026-10-19	10:00:00	30	Ardor ocular y fatiga visual frente a pantallas	Consulta concluida. Signos vitales normales: PA 120/75, FC 70 lpm. Se entrega receta y pautas preventivas. Alta médica temporal.	\N	2026-10-19 09:47:00
295	ffa96696-ff54-456e-9df0-55cbc9911b31	16	16	12	3	2026-11-10	11:30:00	30	Control de presión intraocular	Paciente atendido en consultorio. Examen clínico adecuado. Tratamiento bien tolerado. Se ajustan dosis y se indican controles laboratoriales en 60 días.	\N	2026-11-10 11:16:00
\.


--
-- Data for Name: especialidad; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.especialidad (id_especialidad, nombre_especialidad, descripcion, estado) FROM stdin;
1	Medicina General	Atención médica primaria, evaluación inicial y diagnóstico general.	t
2	Pediatría	Atención médica integral para recién nacidos, niños y adolescentes.	t
3	Cardiología	Diagnóstico y tratamiento de afecciones cardíacas y del sistema circulatorio.	t
4	Ginecología y Obstetricia	Salud reproductiva de la mujer, control prenatal y salud ginecológica.	t
5	Traumatología y Ortopedia	Evaluación y tratamiento de lesiones del sistema osteomuscular y articulaciones.	t
6	Dermatología	Diagnóstico y tratamiento de enfermedades de la piel, cabello y uñas.	t
7	Neurología Clínica	Atención de patologías y trastornos del sistema nervioso central y periférico.	t
8	Mastología	Prevención, diagnóstico y tratamiento de patologías mamarias.	t
9	Angiología y Cirugía Vascular	Tratamiento de afecciones en vasos sanguíneos y linfáticos.	t
10	Neumología	Diagnóstico y tratamiento de enfermedades del aparato respiratorio.	t
11	Nutrición y Dietética	Evaluación nutricional, planes alimenticios y educación dietética.	t
12	Oftalmología	Cuidado de la salud visual, detección y tratamiento de enfermedades oculares.	t
13	Otorrinolaringología	Diagnóstico y tratamiento de afecciones en oído, nariz y garganta.	t
14	Proctología	Diagnóstico y tratamiento de patologías del recto y ano.	t
15	Psicología y Psiquiatría	Atención de la salud mental, evaluación psicológica y psiquiátrica.	t
16	Reumatología	Diagnóstico y tratamiento de afecciones reumáticas y autoinmunes.	t
17	Urología	Diagnóstico y tratamiento de afecciones del sistema urinario y reproductor masculino.	t
\.


--
-- Data for Name: estado_cita; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.estado_cita (id_estado_cita, nombre_estado, descripcion) FROM stdin;
1	Programada	Cita reservada y agendada correctamente
2	Confirmada	Cita reconfirmada previa a la fecha/hora de consulta
3	Atendida	Consulta médica concluida por el facultativo
4	Cancelada	Cita anulada por el paciente o administración
5	Inasistencia	El paciente no se presentó en la fecha y hora fijada
\.


--
-- Data for Name: historial_cambio_cita; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.historial_cambio_cita (id_historial, id_cita, id_usuario, estado_anterior, estado_nuevo, tipo_cambio, observacion, fecha_hora_anterior, fecha_hora_nueva, fecha_modificacion) FROM stdin;
1	1	2	1	2	CONFIRMACION	Confirmada vía llamada telefónica	\N	\N	2026-11-01 10:15:00
2	1	4	2	3	ATENCION	Atención médica concluida con éxito	\N	\N	2026-11-02 08:30:00
3	2	2	1	2	CONFIRMACION	Reconfirmada por mensaje de WhatsApp	\N	\N	2026-11-04 11:20:00
4	2	6	2	3	ATENCION	Consulta prenatal finalizada	\N	\N	2026-11-05 09:30:00
5	3	2	1	2	CONFIRMACION	Paciente confirmó asistencia presencial	\N	\N	2026-11-01 16:40:00
6	3	4	2	3	ATENCION	Control de glucemia concluido	\N	\N	2026-11-02 09:00:00
7	4	2	1	2	CONFIRMACION	Confirmada por recepcionista	\N	\N	2026-11-05 09:10:00
8	4	7	2	3	ATENCION	Evaluación de rodilla finalizada	\N	\N	2026-11-06 15:30:00
9	5	2	1	2	CONFIRMACION	Confirmación presencial de la madre	\N	\N	2026-11-03 14:00:00
10	5	5	2	3	ATENCION	Consulta de pediatría completada	\N	\N	2026-11-04 08:30:00
11	6	2	1	2	CONFIRMACION	Confirmado por recepción	\N	\N	2026-11-02 11:00:00
12	6	8	2	3	ATENCION	Consulta traumatológica finalizada	\N	\N	2026-11-03 14:30:00
13	7	2	1	2	CONFIRMACION	Confirmada por llamada	\N	\N	2026-11-02 15:30:00
14	7	9	2	3	ATENCION	Atención dermatológica realizada	\N	\N	2026-11-03 09:30:00
15	8	2	1	2	CONFIRMACION	Confirmada en ventanilla	\N	\N	2026-11-03 16:15:00
16	8	4	2	3	ATENCION	Atención médica general concluida	\N	\N	2026-11-04 09:30:00
17	9	2	1	2	CONFIRMACION	Confirmada telefónicamente	\N	\N	2026-11-04 10:45:00
18	9	9	2	3	ATENCION	Consulta de dermatología completada	\N	\N	2026-11-05 10:00:00
19	10	2	1	2	CONFIRMACION	Confirmada por WhatsApp	\N	\N	2026-11-01 17:00:00
20	10	16	2	3	ATENCION	Consulta oftalmológica finalizada	\N	\N	2026-11-02 14:30:00
21	16	2	1	4	CANCELACION	Paciente solicita anulación por razones laborales	\N	\N	2026-11-03 18:00:00
22	17	2	1	4	CANCELACION	Anulada por bloqueo en la zona norte	\N	\N	2026-11-03 08:30:00
23	18	2	1	4	CANCELACION	Paciente posterga su cita por viaje	\N	\N	2026-11-03 14:15:00
24	19	2	1	4	CANCELACION	Solicitud expresa de cancelación del paciente	\N	\N	2026-11-04 16:20:00
25	20	2	1	4	CANCELACION	Conflicto de agenda personal del paciente	\N	\N	2026-11-03 12:00:00
26	21	2	1	5	INASISTENCIA	Paciente no acudió y no contestó llamadas	\N	\N	2026-11-05 14:40:00
27	22	2	1	5	INASISTENCIA	Paciente ausente al llamado en sala de espera	\N	\N	2026-11-06 16:05:00
28	23	2	1	5	INASISTENCIA	Tiempo de tolerancia de 15 minutos vencido	\N	\N	2026-11-05 10:20:00
29	24	2	1	5	INASISTENCIA	Paciente no se presentó en horario fijado	\N	\N	2026-11-05 14:35:00
30	25	2	1	5	INASISTENCIA	Inasistencia declarada por recepción	\N	\N	2026-11-05 10:35:00
31	26	2	1	2	CONFIRMACION	Reconfirmada por ventanilla para el lunes	\N	\N	2026-11-07 10:00:00
32	27	2	1	2	CONFIRMACION	Confirmada vía mensaje institucional	\N	\N	2026-11-07 11:30:00
33	28	2	1	2	CONFIRMACION	Madre reconfirmó turno de vacunación	\N	\N	2026-11-07 12:15:00
34	29	2	1	2	CONFIRMACION	Confirmada por recepcionista de turno	\N	\N	2026-11-07 14:45:00
35	30	2	1	2	CONFIRMACION	Confirmación telefónica exitosa	\N	\N	2026-11-07 16:00:00
36	38	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-01 08:00:00
37	39	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-01 09:30:00
38	40	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-02 14:00:00
39	41	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-10-02 15:00:00
40	42	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-05 11:00:00
41	43	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-05 08:00:00
42	44	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-06 10:30:00
43	45	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-06 11:00:00
44	46	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-07 14:30:00
45	47	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-07 10:00:00
46	48	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-08 10:00:00
47	49	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-08 16:30:00
48	50	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-09 10:30:00
49	51	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-09 08:30:00
50	52	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-12 09:00:00
51	53	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-12 17:00:00
52	54	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-13 09:00:00
53	55	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-10-13 08:00:00
54	56	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-14 14:00:00
55	57	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-14 08:00:00
56	58	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-15 14:30:00
57	59	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-15 17:00:00
58	60	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-16 08:00:00
59	61	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-16 15:00:00
60	62	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-19 15:00:00
61	63	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-19 10:00:00
62	64	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-20 09:00:00
63	65	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-20 10:30:00
64	66	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-21 08:30:00
65	67	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-21 17:00:00
66	68	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-10-22 14:00:00
67	69	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-22 08:00:00
68	70	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-23 17:30:00
69	71	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-10-23 15:30:00
70	72	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-26 10:00:00
71	73	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-26 10:00:00
72	74	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-27 10:30:00
73	75	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-27 17:00:00
74	76	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-10-28 09:30:00
75	77	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-28 15:30:00
76	78	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-29 16:30:00
77	79	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-10-29 16:00:00
78	80	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-10-30 17:30:00
79	81	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-10-30 16:00:00
80	82	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-02 16:30:00
81	83	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-11-02 17:30:00
82	84	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-11-03 17:30:00
83	85	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-03 08:30:00
84	86	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-04 09:00:00
85	87	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-11-04 10:00:00
86	88	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-05 14:30:00
87	89	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-05 15:00:00
88	90	1	1	4	CANCELACION	Cita cancelada por el paciente o administración	\N	\N	2026-11-06 11:00:00
89	91	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-11-06 17:00:00
90	92	1	1	5	INASISTENCIA	Paciente inasistente registrado	\N	\N	2026-11-09 11:00:00
91	93	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-09 08:30:00
92	94	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-10 10:30:00
93	95	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-10 09:00:00
94	96	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-11 17:00:00
95	97	1	1	3	ATENCION	Atención médica concluida satisfactoriamente	\N	\N	2026-11-11 17:00:00
96	98	1	1	2	CONFIRMACION	Confirmada telefónicamente por recepción	\N	\N	2026-11-13 11:30:00
97	99	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-13 10:30:00
98	100	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-16 09:30:00
99	101	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-16 10:00:00
100	102	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-17 11:30:00
101	103	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-17 10:30:00
102	104	1	1	2	CONFIRMACION	Confirmada telefónicamente por recepción	\N	\N	2026-11-18 15:00:00
103	105	1	1	2	CONFIRMACION	Confirmada telefónicamente por recepción	\N	\N	2026-11-18 10:30:00
104	106	1	1	2	CONFIRMACION	Confirmada telefónicamente por recepción	\N	\N	2026-11-19 11:30:00
105	107	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-19 10:00:00
106	108	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-20 09:30:00
107	109	1	1	2	CONFIRMACION	Confirmada telefónicamente por recepción	\N	\N	2026-11-20 16:30:00
108	110	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-23 10:30:00
109	111	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-23 14:30:00
110	112	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-24 15:30:00
111	113	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-24 15:30:00
112	114	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-25 09:30:00
113	115	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-25 15:30:00
114	116	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-26 11:30:00
115	117	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-26 17:00:00
116	118	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-27 16:30:00
117	119	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-27 15:00:00
118	120	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-30 10:00:00
119	121	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-11-30 10:30:00
120	122	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-01 10:00:00
121	123	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-01 08:00:00
122	124	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-02 11:30:00
123	125	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-02 16:00:00
124	126	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-03 16:30:00
125	127	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-03 14:30:00
126	128	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-04 08:30:00
127	129	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-04 14:30:00
128	130	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-07 16:30:00
129	131	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-07 10:00:00
130	132	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-08 08:00:00
131	133	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-08 09:30:00
132	134	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-09 15:30:00
133	135	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-09 08:30:00
134	136	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-10 17:30:00
135	137	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-10 17:30:00
136	138	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-11 09:00:00
137	139	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-11 10:30:00
138	140	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-14 09:00:00
139	141	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-14 08:00:00
140	142	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-15 15:00:00
141	143	1	\N	1	CREACION	Cita médica agendada en el sistema	\N	\N	2026-12-15 09:30:00
142	144	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-23 10:00:00
143	145	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-22 16:30:00
144	146	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-07 17:30:00
145	147	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-22 09:00:00
146	148	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-27 10:00:00
147	149	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-29 17:30:00
148	150	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-24 14:00:00
149	151	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-24 17:30:00
150	152	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-11 11:00:00
151	153	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-28 16:00:00
152	154	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-18 08:00:00
153	155	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-28 10:30:00
154	156	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-07 17:30:00
155	157	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-22 15:30:00
156	158	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-07 10:00:00
157	159	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-23 15:30:00
158	160	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-25 14:30:00
159	161	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-22 09:00:00
160	162	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-23 10:30:00
161	163	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-12 08:30:00
162	164	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-23 14:00:00
163	165	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-04 11:00:00
164	166	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-09-23 14:00:00
165	167	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-09 16:30:00
166	168	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-16 09:30:00
167	169	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-21 15:30:00
168	170	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-11-11 08:00:00
169	171	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-09 08:00:00
170	172	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-09-28 14:00:00
171	173	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-17 17:00:00
172	174	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-05 14:30:00
173	175	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-28 16:00:00
174	176	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-08 10:00:00
175	177	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-07 15:00:00
176	178	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-25 16:30:00
177	179	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-29 10:00:00
178	180	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-21 17:30:00
179	181	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-09-29 16:30:00
180	182	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-09 08:00:00
181	183	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-09 16:00:00
182	184	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-02 10:30:00
183	185	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-10-15 11:00:00
184	186	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-16 11:00:00
185	187	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-28 09:30:00
186	188	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-07 15:00:00
187	189	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-22 09:00:00
188	190	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-22 15:30:00
189	191	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-11-04 10:30:00
190	192	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-30 16:30:00
191	193	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-09-21 16:00:00
192	194	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-28 09:00:00
193	195	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-29 10:30:00
194	196	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-25 14:00:00
195	197	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-29 11:00:00
196	198	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-22 16:00:00
197	199	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-29 14:30:00
198	200	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-18 15:30:00
199	201	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-11-11 09:00:00
200	202	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 16:00:00
201	203	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-13 10:00:00
202	204	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-10-19 17:30:00
203	205	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-06 17:30:00
204	206	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 09:30:00
205	207	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-23 11:30:00
206	208	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-22 17:30:00
207	209	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-26 17:30:00
208	210	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-23 10:30:00
209	211	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-30 10:00:00
210	212	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-10-07 17:00:00
211	213	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-26 15:00:00
212	214	1	1	4	CANCELACION	Cancelación registrada por administración	\N	\N	2026-11-02 08:30:00
213	215	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-06 08:30:00
214	216	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-22 11:00:00
215	217	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-12 16:00:00
216	218	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 16:30:00
217	219	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-16 15:30:00
218	220	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 14:00:00
219	221	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-12 16:30:00
220	222	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-02 15:00:00
221	223	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-11 17:00:00
222	224	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-15 11:30:00
223	225	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-01 15:00:00
224	226	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-29 15:00:00
225	227	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-20 11:30:00
226	228	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-23 09:30:00
227	229	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-10-19 14:00:00
228	230	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-06 09:30:00
229	231	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-02 17:00:00
230	232	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-15 11:00:00
231	233	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-21 09:30:00
232	234	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-20 08:00:00
233	235	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-09 09:30:00
234	236	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-07 08:00:00
235	237	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-28 16:00:00
236	238	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-28 14:30:00
237	239	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-27 10:00:00
238	240	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-09 08:30:00
239	241	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-09-16 14:30:00
240	242	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-15 17:00:00
241	243	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 09:30:00
242	244	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-24 08:00:00
243	245	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-02 16:30:00
244	246	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-17 15:00:00
245	247	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-07 17:00:00
246	248	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-16 15:30:00
247	249	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-07 11:30:00
248	250	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-04 09:00:00
249	251	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-19 14:00:00
250	252	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-29 17:00:00
251	253	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-15 16:30:00
252	254	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-12 08:00:00
253	255	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-10-22 17:30:00
254	256	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-16 17:00:00
255	257	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-11-03 16:00:00
256	258	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-09 14:30:00
257	259	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-10-01 15:00:00
258	260	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-22 17:00:00
259	261	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-30 15:30:00
260	262	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-19 11:30:00
261	263	1	1	5	INASISTENCIA	Inasistencia constatada	\N	\N	2026-10-19 10:00:00
262	264	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-22 09:30:00
263	265	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-30 11:00:00
264	266	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-30 17:30:00
265	267	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-23 14:00:00
266	268	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-20 16:00:00
267	269	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-05 15:00:00
268	270	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 14:30:00
269	271	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-20 14:30:00
270	272	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-28 08:30:00
271	273	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-19 11:30:00
272	274	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-24 16:00:00
273	275	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-15 11:00:00
274	276	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-05 15:30:00
275	277	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-10 08:30:00
276	278	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-11 15:30:00
277	279	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-23 17:00:00
278	280	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-29 17:30:00
279	281	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-16 08:00:00
280	282	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-30 14:30:00
281	283	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-17 14:00:00
282	284	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-21 15:30:00
283	285	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-03 17:30:00
284	286	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-09 08:30:00
285	287	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-26 11:30:00
286	288	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-29 08:00:00
287	289	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-22 11:30:00
288	290	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-01 16:00:00
289	291	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-19 17:30:00
290	292	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-22 11:00:00
291	293	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-09-17 08:00:00
292	294	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-10-19 10:00:00
293	295	1	1	3	ATENCION	Consulta médica concluida con éxito	\N	\N	2026-11-10 11:30:00
\.


--
-- Data for Name: horario_atencion; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.horario_atencion (id_horario, id_medico, dia_semana, hora_inicio, hora_fin, duracion_cita_minutos) FROM stdin;
1	1	1	08:00:00	12:00:00	30
2	1	1	14:00:00	18:00:00	30
3	1	2	08:00:00	12:00:00	30
4	1	2	14:00:00	18:00:00	30
5	1	3	08:00:00	12:00:00	30
6	1	3	14:00:00	18:00:00	30
7	1	4	08:00:00	12:00:00	30
8	1	4	14:00:00	18:00:00	30
9	1	5	08:00:00	12:00:00	30
10	1	5	14:00:00	18:00:00	30
11	2	1	08:00:00	12:00:00	30
12	2	1	14:00:00	18:00:00	30
13	2	2	08:00:00	12:00:00	30
14	2	2	14:00:00	18:00:00	30
15	2	3	08:00:00	12:00:00	30
16	2	3	14:00:00	18:00:00	30
17	2	4	08:00:00	12:00:00	30
18	2	4	14:00:00	18:00:00	30
19	2	5	08:00:00	12:00:00	30
20	2	5	14:00:00	18:00:00	30
21	3	1	08:00:00	12:00:00	30
22	3	2	14:00:00	18:00:00	30
23	3	3	08:00:00	12:00:00	30
24	3	4	14:00:00	18:00:00	30
25	3	5	08:00:00	12:00:00	30
26	4	1	10:00:00	14:00:00	30
27	4	2	10:00:00	14:00:00	30
28	4	3	10:00:00	14:00:00	30
29	4	4	10:00:00	14:00:00	30
30	4	5	10:00:00	14:00:00	30
31	5	1	08:00:00	12:00:00	30
32	5	3	08:00:00	12:00:00	30
33	6	4	09:00:00	13:00:00	30
34	7	5	15:00:00	19:00:00	30
35	8	1	14:00:00	18:00:00	30
36	8	2	14:00:00	18:00:00	30
37	8	3	14:00:00	18:00:00	30
38	8	4	14:00:00	18:00:00	30
39	8	5	14:00:00	18:00:00	30
40	9	2	09:00:00	13:00:00	30
41	9	4	09:00:00	13:00:00	30
42	10	3	14:00:00	18:00:00	30
43	10	5	14:00:00	18:00:00	30
44	11	1	15:00:00	19:00:00	30
45	11	4	15:00:00	19:00:00	30
46	12	1	09:00:00	12:00:00	30
47	12	3	09:00:00	12:00:00	30
48	13	2	14:00:00	17:00:00	30
49	13	4	14:00:00	17:00:00	30
50	14	3	08:30:00	12:30:00	30
51	14	5	08:30:00	12:30:00	30
52	15	2	09:00:00	13:00:00	30
53	15	3	09:00:00	13:00:00	30
54	15	5	09:00:00	13:00:00	30
55	16	1	14:00:00	18:00:00	30
56	16	3	14:00:00	18:00:00	30
57	16	5	14:00:00	18:00:00	30
58	17	2	08:00:00	12:00:00	30
59	17	4	08:00:00	12:00:00	30
60	18	3	08:30:00	12:00:00	30
61	18	6	08:30:00	12:00:00	30
62	19	1	15:00:00	19:00:00	30
63	19	3	15:00:00	19:00:00	30
64	19	5	15:00:00	19:00:00	30
65	20	2	09:00:00	12:00:00	30
66	20	4	09:00:00	12:00:00	30
67	21	3	08:00:00	12:00:00	30
68	21	5	08:00:00	12:00:00	30
\.


--
-- Data for Name: medico; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.medico (id_medico, nombre_completo, correo, telefono, registro_profesional, estado) FROM stdin;
1	Dr. Javier Gonzales Soria	jgonzales@clinicavidasalud.com.bo	77011001	MP-1011	t
2	Dr. Carlos Eduardo Paz	cpaz@clinicavidasalud.com.bo	77011002	MP-1012	t
3	Dra. Ana Lucía Mendoza	amendoza@clinicavidasalud.com.bo	77011003	MP-2021	t
4	Dr. Luis Fernando Claros	lclaros@clinicavidasalud.com.bo	77011004	MP-2022	t
5	Dr. Roberto Carlos Flores	rflores@clinicavidasalud.com.bo	77011005	MP-3031	t
6	Dra. Patricia Vaca Diez	pvacadiez@clinicavidasalud.com.bo	77011006	MP-4041	t
7	Dr. Mario Gutiérrez Peña	mgutierrez@clinicavidasalud.com.bo	77011007	MP-5051	t
8	Dr. Javier Mercado Roca	jmercado@clinicavidasalud.com.bo	77011008	MP-5052	t
9	Dra. Sofía Claros Rojas	sclaros@clinicavidasalud.com.bo	77011009	MP-6061	t
10	Dr. Marcos Castillo Antelo	mcastillo@clinicavidasalud.com.bo	77011010	MP-7071	t
11	Dr. Hugo Alarcón Terrazas	halarcon@clinicavidasalud.com.bo	77011011	MP-7072	t
12	Dra. Claudia Morales Vaca	cmorales@clinicavidasalud.com.bo	77011012	MP-8081	t
13	Dr. Fernando Ruiz Hurtado	fruiz@clinicavidasalud.com.bo	77011013	MP-9091	t
14	Dra. Gabriela Suárez Justiniano	gsuarez@clinicavidasalud.com.bo	77011014	MP-1001	t
15	Lic. Andrea Aguilera Méndez	aaguilera@clinicavidasalud.com.bo	77011015	MP-1101	t
16	Dr. Gonzalo Vargas Mercado	gvargas@clinicavidasalud.com.bo	77011016	MP-1201	t
17	Dra. María René Roca Pinto	mroca@clinicavidasalud.com.bo	77011017	MP-1301	t
18	Dr. Jorge Luis Banegas	jbanegas@clinicavidasalud.com.bo	77011018	MP-1401	t
19	Dra. Claudia Ramos Melgar	cramos@clinicavidasalud.com.bo	77011019	MP-1501	t
20	Dr. Ernesto Salvatierra B.	esalvatierra@clinicavidasalud.com.bo	77011020	MP-1601	t
21	Dr. Gabriel Gutiérrez Vaca	ggutierrez@clinicavidasalud.com.bo	77011021	MP-1701	t
\.


--
-- Data for Name: medico_especialidad; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.medico_especialidad (id_medico, id_especialidad) FROM stdin;
2	1
1	1
4	2
3	2
5	3
6	4
8	5
7	5
9	6
11	7
10	7
12	8
13	9
14	10
15	11
16	12
17	13
18	14
19	15
20	16
21	17
\.


--
-- Data for Name: paciente; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.paciente (id_paciente, nombre_completo, ci, fecha_nacimiento, genero, telefono, telefono_emergencia, contacto_emergencia, correo, direccion_domicilio, alergias, informacion_medica_basica, tipo_paciente, estado) FROM stdin;
1	Carlos Eduardo Mendoza Pérez	6829104 SC	1985-04-18	Masculino	77123450	77198231	Marta Mendoza (Madre)	carlos.mendoza@gmail.com	Barrio Sirari, Calle 4 #12	Penicilina	Hipertensión arterial controlada	Particular	t
2	María Elena Suárez Roca	5341209 SC	1990-11-25	Femenino	69081234	69011234	Jorge Suárez (Esposo)	maria.suarez@gmail.com	Av. Beni entre 3er y 4to anillo	Ninguna	Control prenatal primer trimestre	Particular	t
3	Juan Carlos Pérez Gómez	4845122 SC	1978-08-12	Masculino	71033400	71099881	Rosa Gómez (Hermana)	juan.perez@hotmail.com	Av. San Martín #450, Equipetrol	Sulfas	Diabetes Mellitus tipo 2	Particular	t
4	Fernando Justiniano Vaca	6214223 SC	1992-02-08	Masculino	71081851	71081850	Carlos Justiniano (Padre)	fernando.justiniano@outlook.com	Av. Santos Dumont 5to anillo	Ninguna	Evaluación traumatológica rodilla derecha	Seguro Colectivo	t
5	Lucía Marcela Gómez Saucedo	7819234 SC	2015-06-30	Femenino	78411223	78411220	Marcela Saucedo (Madre)	lucia.gomez@gmail.com	Condominio Sevilla Real #45	Ibuprofeno	Control pediátrico regular y asma leve	Particular	t
6	Ricardo Paz Soldán Suárez	3482102 SC	1968-12-05	Masculino	69013364	69013360	Andrea Paz (Hija)	ricardo.paz@gmail.com	Av. Cañoto #780	Ninguna	Artritis reumatoide en falanges	Particular	t
7	Claudia Vaca Díez Ortiz	8491021 SC	1995-09-14	Femenino	77341209	77341200	Mario Vaca Díez (Padre)	claudia.vaca@yahoo.com	Av. Bush Calle 8 Oeste	Polen, Ácaros	Rinitis alérgica estacional recurrente	Particular	t
8	Andrés Mario Aguilera Peña	5910234 SC	1982-07-22	Masculino	76044512	76044510	Patricia Aguilera (Esposa)	andres.aguilera@gmail.com	Villa 1ro de Mayo Calle 3	Aspirina	Gastritis crónica erosiva	Convenio UAGRM	t
9	Paola Andrea Ribera Chávez	9201452 SC	2001-03-11	Femenino	75033481	75033480	Juana Chávez (Madre)	paola.ribera@gmail.com	Plan 3000 Barrio El Carmen	Ninguna	Dermatitis atópica en antebrazos	Particular	t
10	Jorge Luis Antelo Hurtado	4120984 SC	1974-10-03	Masculino	68910452	68910450	Sonia Hurtado (Hermana)	jorge.antelo@gmail.com	Av. Cristo Redentor 6to anillo	Amoxicilina	Evaluación oftalmológica por miopía alta	Particular	t
11	Daniela Paz Rivero	7301948 SC	1998-05-19	Femenino	77651234	77651230	Luis Paz (Padre)	daniela.paz@gmail.com	Barrio Hamacas Calle 7	Ninguna	Control nutricional y asesoría dietética	Particular	t
12	Rodrigo Siles Escalante	6145892 SC	1989-08-27	Masculino	78100234	78100230	Mariana Siles (Hermana)	rodrigo.siles@gmail.com	Av. Monseñor Rivero Edif. El Prado	Ketorolaco	Trastorno ansioso leve reactivo	Particular	t
13	Valeria Monasterio Soliz	8812903 SC	2018-01-14	Femenino	69123478	69123470	Camila Soliz (Madre)	valeria.monasterio@gmail.com	Las Palmas Calle Paraíso #10	Ninguna	Control de crecimiento y desarrollo infantil	Particular	t
14	Mauricio Justiniano Dorado	5120349 SC	1979-11-09	Masculino	77088192	77088190	Teresa Dorado (Madre)	mauricio.justiniano@gmail.com	Av. Grigotá 3er anillo interno	Ciprofloxacino	Evaluación urológica preventiva	Particular	t
15	Silvia Lorena Cuéllar Banegas	6782341 SC	1987-04-02	Femenino	76655123	76655120	Marco Cuéllar (Esposo)	silvia.cuellar@gmail.com	Urb. Urbarí Calle Tundi #25	Dipirona	Cefaleas tensionales recurrentes	Convenio UAGRM	t
16	Mateo Salvatierra Torrico	9412098 SC	2019-09-21	Masculino	75311245	75311240	Gabriela Torrico (Madre)	mateo.salvatierra@gmail.com	Av. Radial 26 4to anillo	Ninguna	Faringoamigdalitis aguda repetitiva	Particular	t
17	Camila Alejandra Ortiz Melgar	7203456 SC	1994-12-18	Femenino	68022419	68022410	Esteban Ortiz (Hermano)	camila.ortiz@gmail.com	Av. Piraí Calle Guembé #14	Ninguna	Ecografía de control mamario y prevención	Particular	t
18	Sebastián Pinto Barba	5812904 SC	1981-06-07	Masculino	77312984	77312980	Karla Barba (Hermana)	sebastian.pinto@gmail.com	Barrio Los Sauces Calle Nogal	Tetraciclina	Várices en miembros inferiores etapa I	Particular	t
19	Natalia Franco Echeverría	8129034 SC	2000-08-30	Femenino	78433219	78433210	Rosario Echeverría (Madre)	natalia.franco@gmail.com	Av. Alemania entre 4to y 5to anillo	Paracetamol	Faringitis y disfonía persistente	Particular	t
20	Diego Armando Mercado Farah	4982103 SC	1976-03-24	Masculino	76099124	76099120	Carla Farah (Esposa)	diego.mercado@gmail.com	Av. Mutualista Calle 18	Ninguna	Sensación de pesadez y chequeo proctológico	Particular	t
21	Adriana Belén Rojas Téllez	6543210 SC	1991-10-15	Femenino	77099412	77099410	Víctor Rojas (Padre)	adriana.rojas@gmail.com	Av. Virgen de Cotoca 3er anillo	Ninguna	Dolor lumbar mecánico por sobreesfuerzo	Particular	t
22	Hugo César Justiniano Solares	5678901 SC	1984-01-29	Masculino	69100234	69100230	Carmen Solares (Madre)	hugo.justiniano@gmail.com	Av. Beni Calle Caranda	Ninguna	Control posquirúrgico de clavícula	Seguro Colectivo	t
23	Fabiola Montes Céspedes	7890123 SC	1996-07-08	Femenino	78199245	78199240	Ernesto Montes (Padre)	fabiola.montes@gmail.com	Urb. Las Misiones Manzana 12	Iodo	Acné vulgar y evaluación de cicatrices	Particular	t
24	Gustavo Adolfo Leigue Bravo	4321098 SC	1970-05-17	Masculino	76600129	76600120	Beatriz Bravo (Hermana)	gustavo.leigue@gmail.com	Av. 26 de Febrero #320	Penicilina	Insuficiencia venosa moderada bilateral	Particular	t
25	Irene Teresa Landívar Bowles	8901234 SC	1993-02-22	Femenino	75011984	75011980	Eduardo Landívar (Padre)	irene.landivar@gmail.com	Barrio Equipetrol Noroeste Calle 6	Ninguna	Control ginecológico anual y Papanicolau	Particular	t
26	Javier Ignacio Vaca Domínguez	6012345 SC	1986-11-04	Masculino	68900456	68900450	Lorena Domínguez (Madre)	javier.vaca@gmail.com	Av. San Aurelio 4to anillo	Ninguna	Monitoreo de tos persistente posinfecciosa	Particular	t
27	Carla Patricia Hurtado Egüez	9123456 SC	2002-09-01	Femenino	77300189	77300180	Pedro Hurtado (Padre)	carla.hurtado@gmail.com	Av. Cumavi Barrio 25 de Diciembre	Dipirona	Acompañamiento psicológico vocacional	Convenio UAGRM	t
28	Álvaro Marcelo Torrico Zeballos	5234567 SC	1977-12-14	Masculino	78400192	78400190	Miriam Zeballos (Hermana)	alvaro.torrico@gmail.com	Av. Radial 10 3er anillo externo	Ninguna	Dolor persistente articulación temporomandibular	Particular	t
29	Lorena Silvia Chávez Saucedo	7345678 SC	1988-06-25	Femenino	76011983	76011980	Fernando Chávez (Hermano)	lorena.chavez@gmail.com	Av. Busch Calle 4 Oeste	Sulfas	Dolor epigástrico en estudio endoscópico	Particular	t
30	Óscar René Aguilera Melgar	4456789 SC	1973-04-10	Masculino	69144512	69144510	Raquel Melgar (Madre)	oscar.aguilera@gmail.com	Av. Centenario entre 2do y 3er anillo	Ninguna	Evaluación prostática anual y PSA	Particular	t
31	Viviana Mabel Soliz Justiniano	8234567 SC	1997-03-03	Femenino	78144519	78144510	Marcos Soliz (Padre)	viviana.soliz@gmail.com	Barrio Los Chacos Calle Los Tajibos	Ninguna	Tratamiento de foliculitis en espalda	Particular	t
32	Víctor Hugo Morales Parada	6123456 SC	1983-08-16	Masculino	76699120	76699121	Elena Parada (Madre)	victor.morales@gmail.com	Av. Noel Kempff Mercado 3er anillo	Ninguna	Control ambulatorio hipertensivo	Particular	t
33	Mónica Esther Banegas Roca	9345678 SC	2003-12-01	Femenino	75099124	75099125	Jorge Banegas (Padre)	monica.banegas@gmail.com	Av. Che Guevara Calle 9	Ninguna	Evaluación refractiva y renovación de lentes	Particular	t
34	Raúl Andrés Justiniano Méndez	5345678 SC	1980-07-19	Masculino	68944512	68944513	Sonia Méndez (Madre)	raul.justiniano@gmail.com	Av. Tres Pasos al Frente 4to anillo	Penicilina	Dolor crónico lumbar con irradiación ciática	Particular	t
35	Esther Noemí Ramos Suárez	7456789 SC	1999-01-27	Femenino	77388129	77388128	David Ramos (Hermano)	esther.ramos@gmail.com	Av. Landívar Condominio Portal del Norte	Ninguna	Plan dietoterapéutico de aumento muscular	Particular	t
76	Lucas Mateo Banegas Vaca	9812401	2021-04-12	Masculino	+591 71092831	+591 71092830	Mariana Vaca (Madre)	lucas.banegas@gmail.com	Av. Banzer 6to Anillo, Condominio Sevilla	Ninguna	Controles de crecimiento al día. Vacunas completas.	General	t
77	Valentina Sofia Ribera Justiniano	9934102	2018-09-25	Femenino	+591 72183940	+591 72183941	Carlos Ribera (Padre)	valentina.ribera@gmail.com	Barrio Equipetrol Norte, Calle 9	Alérgica a Penicilina	Antecedente de bronquiolitis a los 2 años.	Asegurado	t
78	Joaquín Andrés Saucedo Roca	8923411	2015-01-14	Masculino	+591 73194051	+591 73194050	Patricia Roca (Madre)	joaquin.saucedo@hotmail.com	Av. San Martín, Condominio El Bosque	Ninguna	Rinitis alérgica estacional.	VIP	t
79	Camila Belén Melgar Antelo	8821903	2012-07-30	Femenino	+591 74102938	+591 74102937	Sofía Antelo (Madre)	camila.melgar@gmail.com	Barrio Las Palmas, C. Los Mangales #140	Intolerancia a la lactosa	Desarrollo pondoestatural adecuado.	Convenio	t
80	Nicolás Eduardo Aguilera Peña	8712394	2009-11-08	Masculino	+591 75192834	+591 75192833	Eduardo Aguilera (Padre)	nicolas.aguilera@gmail.com	Av. Roca y Coronado, Barrio Hamacas	Alergia a mariscos	Práctica fútbol juvenil competitivo.	General	t
81	Matías Sebastián Paz Soldán	7129841	2002-03-19	Masculino	+591 76281920	+591 76281929	Lucía Paz (Hermana)	matias.paz@outlook.com	Av. Cristo Redentor, Calle Los Tajibos #55	Ninguna	Evaluaciones cardiológicas deportivas.	Asegurado	t
82	Adriana Nicole Hurtado Suárez	6819203	1998-08-14	Femenino	+591 77391029	+591 77391028	Marcos Hurtado (Esposo)	adriana.hurtado@gmail.com	Condominio Las Brisas dpto 8B	Sulfas	Control ginecológico y reproductivo anual.	VIP	t
83	Leonardo Favio Justiniano Dorado	6291823	1994-12-03	Masculino	+591 78401928	+591 78401927	Gloria Dorado (Madre)	leonardo.justiniano@hotmail.com	Barrio Urbari, C. Los Pinos #78	Ninguna	Hipermetropía corregida con lentes.	General	t
84	Paola Andrea Monasterio Roca	5918204	1991-05-22	Femenino	+591 79512039	+591 79512038	Javier Monasterio (Hermano)	paola.monasterio@gmail.com	Av. Beni entre 3er y 4to Anillo	Alergia al polen	Migraña episódica sin aura.	Convenio	t
85	Gabriel Antonio Antelo Méndez	5728190	1989-10-11	Masculino	+591 70623910	+591 70623911	Verónica Méndez (Esposa)	gabriel.antelo@gmail.com	Plan 3000, Barrio Guapilo	Ninguna	Sin patologías crónicas conocidas.	General	t
86	Mirtha Rossana Vaca Diez Pinto	4829103	1982-02-17	Femenino	+591 71738291	+591 71738292	Roberto Pinto (Esposo)	mirtha.vacadiez@gmail.com	Av. Busch esq. 3er Anillo Interno	AINEs (Ibuprofeno)	Hipotiroidismo primario tratado con Levotiroxina 75mcg.	Asegurado	t
87	Juan Pablo Banegas Justiniano	4519283	1979-06-29	Masculino	+591 72849102	+591 72849103	Claudia Banegas (Hija)	juanpablo.banegas@hotmail.com	Barrio Sirari, C. Cañada Strongest #33	Ninguna	Hipertensión arterial Grado 1 en tratamiento.	VIP	t
88	Silvia Marcela Cuéllar Chávez	4291820	1976-09-08	Femenino	+591 73950213	+591 73950214	Ernesto Cuéllar (Hermano)	silvia.cuellar@gmail.com	Villa 1ro de Mayo, Calle 4 Norte #120	Ninguna	Dislipidemia mixta controlada con dieta y Atorvastatina.	General	t
89	Jorge Luis Morales Soria	3918204	1973-11-24	Masculino	+591 74061324	+591 74061325	Raquel Morales (Esposa)	jorge.morales@yahoo.com	Av. Grigotá, C. Los Cusis #90	Penicilina	Gastritis crónica antral por H. pylori tratada.	Convenio	t
90	Carmen Rosa Salvatierra Roca	3719284	1970-04-15	Femenino	+591 75172435	+591 75172436	Daniel Salvatierra (Hijo)	carmen.salvatierra@gmail.com	Condominio Portal del Urubó casa 22	Dipirona	Colecistectomía laparoscópica en 2021.	VIP	t
91	Walter Humberto Ribera Melgar	2918203	1960-01-10	Masculino	+591 76283546	+591 76283547	Sonia Ribera (Esposa)	walter.ribera@gmail.com	Av. Mutualista 3er Anillo Externo	Ninguna	Diabetes Mellitus Tipo 2, cardiopatía isquémica controlada.	Asegurado	t
92	Guillermina Suárez vda. de Roca	2481920	1955-08-03	Femenino	+591 77394657	+591 77394658	Gustavo Roca (Hijo)	guillermina.suarez@hotmail.com	Av. Irala #412	Penicilina	Osteoartrosis de rodilla bilateral, HTA esencial.	General	t
93	Alcides Fernando Flores Vaca	2198204	1951-12-19	Masculino	+591 78405768	+591 78405769	Rosa Flores (Hija)	alcides.flores@gmail.com	Barrio Santa Rosita, Calle 3	Ninguna	Hiperplasia prostática benigna, control urológico regular.	Convenio	t
94	Gladys Teresa Ortiz Aguilera	1982039	1948-05-27	Femenino	+591 79516879	+591 79516880	Mariana Ortiz (Nieta)	gladys.ortiz@gmail.com	Av. Cristóbal de Mendoza #615	Aspirina	Fibrilación auricular no valvular anticoagulada.	VIP	t
95	Ramiro Gonzalo Mendoza Justiniano	1728190	1945-10-02	Masculino	+591 70627980	+591 70627981	Pedro Mendoza (Hijo)	ramiro.mendoza@gmail.com	Calle Warnes esq. Potosí #88	Ninguna	Enfermedad pulmonar obstructiva crónica (EPOC) leve.	Asegurado	t
96	Tania Gisela Soria Galvarro	6192840	1987-03-11	Femenino	+591 71839012	+591 71839013	Fabiola Soria (Hermana)	tania.soria@gmail.com	Barrio Los Chacos, C. Las Palmeras	Mariscos y Yodo	Dermatitis atópica crónica.	General	t
97	Víctor Hugo Mercado Banegas	5829104	1984-09-21	Masculino	+591 72940123	+591 72940124	Verónica Mercado (Esposa)	victor.mercado@gmail.com	Condominio Ciudad Jardín dpto 302	Ninguna	Hernia discal L4-L5 no quirúrgica.	Asegurado	t
98	Fabiola Patricia Dorado Melgar	5291830	1992-11-04	Femenino	+591 73051234	+591 73051235	Mauricio Dorado (Hermano)	fabiola.dorado@outlook.com	Av. San Aurelio 4to Anillo	Ninguna	Embarazo de 28 semanas de evolución normal.	VIP	t
99	Álvaro René Banegas Aguilera	4918205	1980-07-16	Masculino	+591 74162345	+591 74162346	Cecilia Banegas (Esposa)	alvaro.banegas@gmail.com	Av. Santos Dumont #1024	Sulfamidas	Hipercolesterolemia familiar.	Convenio	t
100	Lourdes Marina Justiniano Roca	4381920	1977-02-28	Femenino	+591 75273456	+591 75273457	Karina Justiniano (Hija)	lourdes.justiniano@hotmail.com	Barrio Ramafa, C. Cañoto #15	Ninguna	Gastritis erosiva superficial.	General	t
101	Esteban David Pinto Saucedo	7918204	2000-12-14	Masculino	+591 76384567	+591 76384568	David Pinto (Padre)	esteban.pinto@gmail.com	Av. Piraí entre 2do y 3er Anillo	Ninguna	Chequeo médico universitario anual.	General	t
102	Valeria Inés Vaca Antelo	7481920	1997-04-09	Femenino	+591 77495678	+591 77495679	Lucía Antelo (Madre)	valeria.vaca@gmail.com	Av. Monseñor Rivero #320	Cefalosporinas	Acné inflamatorio grado II.	VIP	t
103	René Alejandro Melgar Justiniano	6918203	1993-01-23	Masculino	+591 78506789	+591 78506780	Beatriz Melgar (Esposa)	rene.melgar@gmail.com	Condominio La Casona casa 4	Ninguna	Esguince de tobillo derecho en rehabilitación.	Asegurado	t
104	Carla Jimena Aguilera Paz	6381920	1990-08-17	Femenino	+591 79617890	+591 79617891	Gonzalo Paz (Padre)	carla.aguilera@yahoo.com	Av. Alemania 5to Anillo	Ninguna	Migrañas catameniales periódicas.	General	t
105	Gustavo Adolfo Ribera Soria	5619283	1986-06-05	Masculino	+591 70728901	+591 70728902	Elena Ribera (Hermana)	gustavo.ribera@gmail.com	Av. 2 de Agosto #880	Metamizol	Sobrepeso grado 1, consulta nutricional.	Convenio	t
106	Daniela Elizabeth Suárez Vaca	5129840	1983-10-12	Femenino	+591 71839023	+591 71839024	Martín Suárez (Esposo)	daniela.suarez@gmail.com	Av. Virgen de Cotoca 3er Anillo	Ninguna	Control mamográfico preventivo birads 1.	VIP	t
107	Óscar Manuel Antelo Banegas	4719283	1978-03-30	Masculino	+591 72940134	+591 72940135	Susana Antelo (Esposa)	oscar.antelo@gmail.com	Barrio La Morita, C. Los Helechos	Ninguna	Insuficiencia venosa periférica bilateral.	Asegurado	t
108	Martha Cecilia Roca Melgar	4192840	1974-12-01	Femenino	+591 73051245	+591 73051246	Raúl Roca (Hijo)	martha.roca@hotmail.com	Av. Tres Pasos al Frente #540	Eritromicina	Artrosis de manos y nódulos de Heberden.	General	t
109	Jaime Eduardo Saucedo Justiniano	3819204	1971-07-07	Masculino	+591 74162356	+591 74162357	Gladys Saucedo (Esposa)	jaime.saucedo@gmail.com	Av. Cumavi, Barrio 10 de Octubre	Ninguna	Hipertrigliceridemia y esteatosis hepática leve.	Convenio	t
110	Beatriz Eugenia Dorado Pinto	3291820	1966-09-18	Femenino	+591 75273467	+591 75273468	Ignacio Dorado (Hijo)	beatriz.dorado@gmail.com	Barrio Petrolero, C. Las Lilas #45	Penicilina	Osteoporosis densitométrica en tratamiento.	VIP	t
111	Félix Rolando Banegas Ortiz	2819204	1961-05-14	Masculino	+591 76384578	+591 76384579	Hilda Banegas (Esposa)	felix.banegas@gmail.com	Av. San Martín 2do Anillo	Ninguna	Reemplazo total de cadera derecha en 2023.	Asegurado	t
112	Rosa Elva Justiniano vda. de Paz	2391820	1956-11-29	Femenino	+591 77495689	+591 77495680	Carolina Paz (Hija)	rosaelva.justiniano@gmail.com	C. Sucre #220	Sulfas	Glaucoma de ángulo abierto tratado con gotas.	General	t
113	Armando César Aguilera Roca	1829104	1950-04-03	Masculino	+591 78506790	+591 78506791	César Aguilera (Hijo)	armando.aguilera@yahoo.com	Av. Cañoto #112	Ninguna	Secuela de infarto agudo de miocardio 2019 estable.	VIP	t
114	Inés Magdalena Soria Melgar	1591820	1947-08-16	Femenino	+591 79617801	+591 79617802	Andrea Soria (Nieta)	ines.soria@gmail.com	Av. Argandoña #85	Ninguna	Demencia vascular en fase inicial con cuidador.	Asegurado	t
115	Julio César Antelo Vaca	1291820	1943-02-20	Masculino	+591 70728912	+591 70728913	Mariano Antelo (Hijo)	julio.antelo@gmail.com	Barrio El Trompillo, C. 5 #10	Aspirina	Portador de marcapasos bicameral definitivo.	Convenio	t
\.


--
-- Data for Name: rol; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.rol (id_rol, nombre_rol, descripcion) FROM stdin;
1	Administrador	Control total de configuración, médicos, horarios, usuarios y reportes
2	Recepcionista	Gestión operativa de pacientes, programación, reprogramación y control de citas
3	Médico	Consulta de agenda médica asignada y registro de atenciones
4	Paciente	Autogestión de perfil, consulta de disponibilidad y solicitud de citas
\.


--
-- Data for Name: usuario; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.usuario (id_usuario, id_rol, id_medico, id_paciente, nombre_usuario, correo, password_hash, estado) FROM stdin;
1	1	\N	\N	admin_uagrm	admin@ficct.uagrm.edu.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
2	2	\N	\N	recepcion_central	recepcion@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
3	2	\N	\N	recepcion_tarde	recepcion2@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
4	3	1	\N	medico_gonzales	jgonzales@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
5	3	2	\N	medico_paz	cpaz@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
6	3	3	\N	medico_mendoza	amendoza@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
7	3	4	\N	medico_claros	lclaros@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
8	3	5	\N	medico_flores	rflores@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
9	3	6	\N	medico_vacadiez	pvacadiez@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
10	3	7	\N	medico_gutierrez	mgutierrez@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
11	3	8	\N	medico_mercado	jmercado@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
12	3	9	\N	medico_clarosr	sclaros@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
13	3	10	\N	medico_castillo	mcastillo@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
14	3	11	\N	medico_alarcon	halarcon@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
15	3	12	\N	medico_morales	cmorales@clinicavidasalud.com.bo	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
16	4	\N	1	paciente_cmendoza	carlos.mendoza@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
17	4	\N	2	paciente_msuarez	maria.suarez@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
18	4	\N	3	paciente_jperez	juan.perez@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
19	4	\N	4	paciente_fjustiniano	fernando.justiniano@outlook.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
20	4	\N	5	paciente_lgomez	lucia.gomez@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
21	4	\N	6	paciente_rpaz	ricardo.paz@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
22	4	\N	7	paciente_cvaca	claudia.vaca@yahoo.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
23	4	\N	8	paciente_maguilera	andres.aguilera@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
24	4	\N	9	paciente_pribera	paola.ribera@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
25	4	\N	10	paciente_jantelo	jorge.antelo@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
26	4	\N	11	paciente_dpaz	daniela.paz@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
27	4	\N	12	paciente_rsiles	rodrigo.siles@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
28	4	\N	13	paciente_vmonasterio	valeria.monasterio@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
29	4	\N	14	paciente_mjustiniano	mauricio.justiniano@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
30	4	\N	15	paciente_scuellar	silvia.cuellar@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
31	4	\N	16	paciente_msalvatierra	mateo.salvatierra@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
32	4	\N	17	paciente_cortiz	camila.ortiz@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
33	4	\N	18	paciente_spinto	sebastian.pinto@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
34	4	\N	19	paciente_nfranco	natalia.franco@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
35	4	\N	20	paciente_dmercado	diego.mercado@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
36	1	\N	\N	admin	admin@clinica.com	$2y$10$yFakZnUI7gTUL.o3kCp0XejxkBJfO2b4u7K7KPPjV0NSv2WAurn1y	t
37	2	\N	\N	recepcion	recepcion@clinica.com	$2y$10$yFakZnUI7gTUL.o3kCp0XejxkBJfO2b4u7K7KPPjV0NSv2WAurn1y	t
78	4	\N	76	paciente_9812401	lucas.banegas@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
79	4	\N	77	paciente_9934102	valentina.ribera@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
80	4	\N	78	paciente_8923411	joaquin.saucedo@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
81	4	\N	79	paciente_8821903	camila.melgar@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
82	4	\N	80	paciente_8712394	nicolas.aguilera@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
83	4	\N	81	paciente_7129841	matias.paz@outlook.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
84	4	\N	82	paciente_6819203	adriana.hurtado@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
85	4	\N	83	paciente_6291823	leonardo.justiniano@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
86	4	\N	84	paciente_5918204	paola.monasterio@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
87	4	\N	85	paciente_5728190	gabriel.antelo@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
88	4	\N	86	paciente_4829103	mirtha.vacadiez@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
89	4	\N	87	paciente_4519283	juanpablo.banegas@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
91	4	\N	89	paciente_3918204	jorge.morales@yahoo.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
92	4	\N	90	paciente_3719284	carmen.salvatierra@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
93	4	\N	91	paciente_2918203	walter.ribera@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
94	4	\N	92	paciente_2481920	guillermina.suarez@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
95	4	\N	93	paciente_2198204	alcides.flores@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
96	4	\N	94	paciente_1982039	gladys.ortiz@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
97	4	\N	95	paciente_1728190	ramiro.mendoza@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
98	4	\N	96	paciente_6192840	tania.soria@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
99	4	\N	97	paciente_5829104	victor.mercado@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
100	4	\N	98	paciente_5291830	fabiola.dorado@outlook.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
101	4	\N	99	paciente_4918205	alvaro.banegas@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
102	4	\N	100	paciente_4381920	lourdes.justiniano@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
103	4	\N	101	paciente_7918204	esteban.pinto@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
104	4	\N	102	paciente_7481920	valeria.vaca@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
105	4	\N	103	paciente_6918203	rene.melgar@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
106	4	\N	104	paciente_6381920	carla.aguilera@yahoo.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
107	4	\N	105	paciente_5619283	gustavo.ribera@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
108	4	\N	106	paciente_5129840	daniela.suarez@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
109	4	\N	107	paciente_4719283	oscar.antelo@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
110	4	\N	108	paciente_4192840	martha.roca@hotmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
111	4	\N	109	paciente_3819204	jaime.saucedo@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
112	4	\N	110	paciente_3291820	beatriz.dorado@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
113	4	\N	111	paciente_2819204	felix.banegas@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
114	4	\N	112	paciente_2391820	rosaelva.justiniano@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
115	4	\N	113	paciente_1829104	armando.aguilera@yahoo.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
116	4	\N	114	paciente_1591820	ines.soria@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
117	4	\N	115	paciente_1291820	julio.antelo@gmail.com	$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi	t
\.


--
-- Name: auditoria_id_auditoria_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.auditoria_id_auditoria_seq', 416, true);


--
-- Name: cita_medica_id_cita_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.cita_medica_id_cita_seq', 295, true);


--
-- Name: especialidad_id_especialidad_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.especialidad_id_especialidad_seq', 17, true);


--
-- Name: estado_cita_id_estado_cita_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.estado_cita_id_estado_cita_seq', 5, true);


--
-- Name: historial_cambio_cita_id_historial_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.historial_cambio_cita_id_historial_seq', 293, true);


--
-- Name: horario_atencion_id_horario_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.horario_atencion_id_horario_seq', 68, true);


--
-- Name: medico_id_medico_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.medico_id_medico_seq', 21, true);


--
-- Name: paciente_id_paciente_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.paciente_id_paciente_seq', 155, true);


--
-- Name: rol_id_rol_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.rol_id_rol_seq', 4, true);


--
-- Name: usuario_id_usuario_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.usuario_id_usuario_seq', 157, true);


--
-- Name: auditoria auditoria_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT auditoria_pkey PRIMARY KEY (id_auditoria);


--
-- Name: cita_medica cita_medica_codigo_unico_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cita_medica
    ADD CONSTRAINT cita_medica_codigo_unico_key UNIQUE (codigo_unico);


--
-- Name: cita_medica cita_medica_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cita_medica
    ADD CONSTRAINT cita_medica_pkey PRIMARY KEY (id_cita);


--
-- Name: especialidad especialidad_nombre_especialidad_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.especialidad
    ADD CONSTRAINT especialidad_nombre_especialidad_key UNIQUE (nombre_especialidad);


--
-- Name: especialidad especialidad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.especialidad
    ADD CONSTRAINT especialidad_pkey PRIMARY KEY (id_especialidad);


--
-- Name: estado_cita estado_cita_nombre_estado_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_cita
    ADD CONSTRAINT estado_cita_nombre_estado_key UNIQUE (nombre_estado);


--
-- Name: estado_cita estado_cita_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.estado_cita
    ADD CONSTRAINT estado_cita_pkey PRIMARY KEY (id_estado_cita);


--
-- Name: historial_cambio_cita historial_cambio_cita_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_cambio_cita
    ADD CONSTRAINT historial_cambio_cita_pkey PRIMARY KEY (id_historial);


--
-- Name: horario_atencion horario_atencion_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_atencion
    ADD CONSTRAINT horario_atencion_pkey PRIMARY KEY (id_horario);


--
-- Name: medico_especialidad medico_especialidad_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medico_especialidad
    ADD CONSTRAINT medico_especialidad_pkey PRIMARY KEY (id_medico, id_especialidad);


--
-- Name: medico medico_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medico
    ADD CONSTRAINT medico_pkey PRIMARY KEY (id_medico);


--
-- Name: medico medico_registro_profesional_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medico
    ADD CONSTRAINT medico_registro_profesional_key UNIQUE (registro_profesional);


--
-- Name: paciente paciente_ci_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.paciente
    ADD CONSTRAINT paciente_ci_key UNIQUE (ci);


--
-- Name: paciente paciente_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.paciente
    ADD CONSTRAINT paciente_pkey PRIMARY KEY (id_paciente);


--
-- Name: rol rol_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.rol
    ADD CONSTRAINT rol_pkey PRIMARY KEY (id_rol);


--
-- Name: usuario usuario_correo_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_correo_key UNIQUE (correo);


--
-- Name: usuario usuario_id_medico_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_id_medico_key UNIQUE (id_medico);


--
-- Name: usuario usuario_id_paciente_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_id_paciente_key UNIQUE (id_paciente);


--
-- Name: usuario usuario_nombre_usuario_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_nombre_usuario_key UNIQUE (nombre_usuario);


--
-- Name: usuario usuario_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT usuario_pkey PRIMARY KEY (id_usuario);


--
-- Name: idx_auditoria_usuario; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_auditoria_usuario ON public.auditoria USING btree (id_usuario);


--
-- Name: idx_cita_especialidad; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cita_especialidad ON public.cita_medica USING btree (id_especialidad);


--
-- Name: idx_cita_estado; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cita_estado ON public.cita_medica USING btree (id_estado_cita);


--
-- Name: idx_cita_fecha; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cita_fecha ON public.cita_medica USING btree (fecha_cita);


--
-- Name: idx_cita_medico; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cita_medico ON public.cita_medica USING btree (id_medico);


--
-- Name: idx_cita_paciente; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_cita_paciente ON public.cita_medica USING btree (id_paciente);


--
-- Name: idx_historial_cita; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_historial_cita ON public.historial_cambio_cita USING btree (id_cita);


--
-- Name: idx_horario_medico; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_horario_medico ON public.horario_atencion USING btree (id_medico);


--
-- Name: idx_usuario_rol; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX idx_usuario_rol ON public.usuario USING btree (id_rol);


--
-- Name: citas_medicas citas_ins; Type: RULE; Schema: public; Owner: -
--

CREATE RULE citas_ins AS
    ON INSERT TO public.citas_medicas DO INSTEAD  INSERT INTO public.cita_medica (id_cita, codigo_unico, id_paciente, id_medico, id_especialidad, id_estado_cita, fecha_cita, hora_cita, duracion_minutos, motivo, observacion_atencion, motivo_cancelacion, fecha_hora_llegada)
  VALUES (new.id_cita, new.codigo_unico, new.id_paciente, new.id_medico, new.id_especialidad, new.id_estado_cita, new.fecha_cita, new.hora_cita, new.duracion_minutos, new.motivo, new.observacion_atencion, new.motivo_cancelacion, new.fecha_hora_llegada);


--
-- Name: citas_medicas citas_upd; Type: RULE; Schema: public; Owner: -
--

CREATE RULE citas_upd AS
    ON UPDATE TO public.citas_medicas DO INSTEAD  UPDATE public.cita_medica SET id_paciente = new.id_paciente, id_medico = new.id_medico, id_especialidad = new.id_especialidad, id_estado_cita = new.id_estado_cita, fecha_cita = new.fecha_cita, hora_cita = new.hora_cita, duracion_minutos = new.duracion_minutos, motivo = new.motivo, observacion_atencion = new.observacion_atencion, motivo_cancelacion = new.motivo_cancelacion, fecha_hora_llegada = new.fecha_hora_llegada
  WHERE (cita_medica.id_cita = old.id_cita);


--
-- Name: roles roles_ins; Type: RULE; Schema: public; Owner: -
--

CREATE RULE roles_ins AS
    ON INSERT TO public.roles DO INSTEAD  INSERT INTO public.rol (id_rol, nombre_rol, descripcion)
  VALUES (new.id_rol, new.nombre_rol, new.descripcion);


--
-- Name: roles roles_upd; Type: RULE; Schema: public; Owner: -
--

CREATE RULE roles_upd AS
    ON UPDATE TO public.roles DO INSTEAD  UPDATE public.rol SET nombre_rol = new.nombre_rol, descripcion = new.descripcion
  WHERE (rol.id_rol = old.id_rol);


--
-- Name: usuarios usuarios_ins; Type: RULE; Schema: public; Owner: -
--

CREATE RULE usuarios_ins AS
    ON INSERT TO public.usuarios DO INSTEAD  INSERT INTO public.usuario (id_usuario, id_rol, id_medico, id_paciente, nombre_usuario, correo, password_hash, estado)
  VALUES (new.id_usuario, new.id_rol, new.id_medico, new.id_paciente, new.nombre_usuario, new.correo, new.password_hash, new.estado);


--
-- Name: usuarios usuarios_upd; Type: RULE; Schema: public; Owner: -
--

CREATE RULE usuarios_upd AS
    ON UPDATE TO public.usuarios DO INSTEAD  UPDATE public.usuario SET id_rol = new.id_rol, id_medico = new.id_medico, id_paciente = new.id_paciente, nombre_usuario = new.nombre_usuario, correo = new.correo, password_hash = new.password_hash, estado = new.estado
  WHERE (usuario.id_usuario = old.id_usuario);


--
-- Name: cita_medica trg_auditar_cambios_cita; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_auditar_cambios_cita AFTER UPDATE ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_auditar_cambios_cita();


--
-- Name: paciente trg_auditoria_paciente; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_auditoria_paciente AFTER INSERT OR DELETE OR UPDATE ON public.paciente FOR EACH ROW EXECUTE FUNCTION public.fn_trg_auditoria_paciente();


--
-- Name: cita_medica trg_registrar_hora_llegada_automatica; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_registrar_hora_llegada_automatica BEFORE UPDATE OF id_estado_cita ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_registrar_hora_llegada_automatica();


--
-- Name: cita_medica trg_restringir_cambios_citas_finalizadas; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_restringir_cambios_citas_finalizadas BEFORE UPDATE ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_restringir_cambios_citas_finalizadas();


--
-- Name: cita_medica trg_validar_disponibilidad_paciente; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_validar_disponibilidad_paciente BEFORE INSERT OR UPDATE OF fecha_cita, hora_cita, id_paciente, id_estado_cita ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_validar_disponibilidad_paciente();


--
-- Name: cita_medica trg_validar_entidades_activas; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_validar_entidades_activas BEFORE INSERT OR UPDATE OF id_medico, id_paciente, id_estado_cita ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_validar_entidades_activas();


--
-- Name: cita_medica trg_validar_fecha_futura_cita; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_validar_fecha_futura_cita BEFORE INSERT ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_validar_fecha_futura_cita();


--
-- Name: horario_atencion trg_validar_horarios_medico_sin_traslape; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_validar_horarios_medico_sin_traslape BEFORE INSERT OR UPDATE OF id_medico, dia_semana, hora_inicio, hora_fin ON public.horario_atencion FOR EACH ROW EXECUTE FUNCTION public.fn_trg_validar_horarios_medico_sin_traslape();


--
-- Name: cita_medica trg_validar_superposicion_cita; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trg_validar_superposicion_cita BEFORE INSERT OR UPDATE OF fecha_cita, hora_cita, id_medico, id_estado_cita ON public.cita_medica FOR EACH ROW EXECUTE FUNCTION public.fn_trg_validar_superposicion_cita();


--
-- Name: auditoria fk_auditoria_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.auditoria
    ADD CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: cita_medica fk_cita_especialidad; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cita_medica
    ADD CONSTRAINT fk_cita_especialidad FOREIGN KEY (id_especialidad) REFERENCES public.especialidad(id_especialidad) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: cita_medica fk_cita_estado; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cita_medica
    ADD CONSTRAINT fk_cita_estado FOREIGN KEY (id_estado_cita) REFERENCES public.estado_cita(id_estado_cita) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: cita_medica fk_cita_medico; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cita_medica
    ADD CONSTRAINT fk_cita_medico FOREIGN KEY (id_medico) REFERENCES public.medico(id_medico) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: cita_medica fk_cita_paciente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cita_medica
    ADD CONSTRAINT fk_cita_paciente FOREIGN KEY (id_paciente) REFERENCES public.paciente(id_paciente) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- Name: historial_cambio_cita fk_historial_cita; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_cambio_cita
    ADD CONSTRAINT fk_historial_cita FOREIGN KEY (id_cita) REFERENCES public.cita_medica(id_cita) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: historial_cambio_cita fk_historial_estado_anterior; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_cambio_cita
    ADD CONSTRAINT fk_historial_estado_anterior FOREIGN KEY (estado_anterior) REFERENCES public.estado_cita(id_estado_cita) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: historial_cambio_cita fk_historial_estado_nuevo; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_cambio_cita
    ADD CONSTRAINT fk_historial_estado_nuevo FOREIGN KEY (estado_nuevo) REFERENCES public.estado_cita(id_estado_cita) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: historial_cambio_cita fk_historial_usuario; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.historial_cambio_cita
    ADD CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario) REFERENCES public.usuario(id_usuario) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: horario_atencion fk_horario_medico; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.horario_atencion
    ADD CONSTRAINT fk_horario_medico FOREIGN KEY (id_medico) REFERENCES public.medico(id_medico) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: medico_especialidad fk_medicoesp_especialidad; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medico_especialidad
    ADD CONSTRAINT fk_medicoesp_especialidad FOREIGN KEY (id_especialidad) REFERENCES public.especialidad(id_especialidad) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: medico_especialidad fk_medicoesp_medico; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.medico_especialidad
    ADD CONSTRAINT fk_medicoesp_medico FOREIGN KEY (id_medico) REFERENCES public.medico(id_medico) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: usuario fk_usuario_medico; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT fk_usuario_medico FOREIGN KEY (id_medico) REFERENCES public.medico(id_medico) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: usuario fk_usuario_paciente; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT fk_usuario_paciente FOREIGN KEY (id_paciente) REFERENCES public.paciente(id_paciente) ON UPDATE CASCADE ON DELETE SET NULL;


--
-- Name: usuario fk_usuario_rol; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.usuario
    ADD CONSTRAINT fk_usuario_rol FOREIGN KEY (id_rol) REFERENCES public.rol(id_rol) ON UPDATE CASCADE ON DELETE RESTRICT;


--
-- PostgreSQL database dump complete
--

\unrestrict HHoQhff6drOwci3tyEzaNwMzbbLc8WCFdbDEos0xFhM68FKooBub6scyE6fiWxN

