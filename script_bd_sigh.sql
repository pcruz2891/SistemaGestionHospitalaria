-- ============================================================
-- Sistema de Gestión Hospitalaria (SIGH)
-- Script de base de datos - PostgreSQL 17 (imagen: postgres:17)
-- Cubre las tablas relacionales de MS Turnos, MS Consulta y MS Farmacia
-- Nota: MS EHR utiliza MongoDB (documentos), por lo que no requiere
-- script SQL; su modelo de datos se documenta por separado.
--
-- Conexión (según docker-compose.yml del proyecto):
--   Base de datos: hospitaldb
--   Usuario:       hospital
--   Host:puerto:   localhost:5432
--
-- Para ejecutar este script dentro del contenedor:
--   docker exec -i postgres-hospital psql -U hospital -d hospitaldb < script_bd_sigh.sql
-- ============================================================

-- Extensión para generar UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ------------------------------------------------------------
-- Entidades compartidas
-- ------------------------------------------------------------

CREATE TABLE pacientes (
    id_paciente     UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    curp            VARCHAR(18) UNIQUE NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    apellido_paterno VARCHAR(80) NOT NULL,
    apellido_materno VARCHAR(80),
    fecha_nacimiento DATE NOT NULL,
    sexo            CHAR(1) CHECK (sexo IN ('M', 'F')),
    telefono        VARCHAR(20),
    creado_en       TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE medicos (
    id_medico       UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    cedula_profesional VARCHAR(20) UNIQUE NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    especialidad    VARCHAR(80) NOT NULL,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMP NOT NULL DEFAULT now()
);

-- ------------------------------------------------------------
-- MS Turnos (Go / Gin)
-- ------------------------------------------------------------

CREATE TABLE turnos (
    id_turno        UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_paciente     UUID NOT NULL REFERENCES pacientes(id_paciente),
    id_medico       UUID NOT NULL REFERENCES medicos(id_medico),
    fecha_hora      TIMESTAMP NOT NULL,
    estado          VARCHAR(20) NOT NULL DEFAULT 'programado'
                    CHECK (estado IN ('programado', 'confirmado', 'atendido', 'cancelado')),
    creado_en       TIMESTAMP NOT NULL DEFAULT now(),
    actualizado_en  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_turnos_paciente ON turnos(id_paciente);
CREATE INDEX idx_turnos_medico_fecha ON turnos(id_medico, fecha_hora);

-- ------------------------------------------------------------
-- MS Consulta (Java / Spring Boot)
-- ------------------------------------------------------------

CREATE TABLE consultas (
    id_consulta     UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_turno        UUID NOT NULL REFERENCES turnos(id_turno),
    id_paciente     UUID NOT NULL REFERENCES pacientes(id_paciente),
    id_medico       UUID NOT NULL REFERENCES medicos(id_medico),
    motivo          TEXT NOT NULL,
    diagnostico     TEXT,
    triaje_nivel    SMALLINT CHECK (triaje_nivel BETWEEN 1 AND 5),
    estado          VARCHAR(20) NOT NULL DEFAULT 'en_curso'
                    CHECK (estado IN ('en_curso', 'completada', 'derivada')),
    fecha_atencion  TIMESTAMP NOT NULL DEFAULT now()
);

CREATE INDEX idx_consultas_paciente ON consultas(id_paciente);
CREATE INDEX idx_consultas_turno ON consultas(id_turno);

-- ------------------------------------------------------------
-- MS Farmacia (Go / Gin)
-- ------------------------------------------------------------

CREATE TABLE medicamentos (
    id_medicamento  UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nombre          VARCHAR(150) NOT NULL,
    presentacion    VARCHAR(80),
    existencia      INTEGER NOT NULL DEFAULT 0 CHECK (existencia >= 0)
);

CREATE TABLE recetas (
    id_receta       UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_consulta     UUID NOT NULL REFERENCES consultas(id_consulta),
    id_paciente     UUID NOT NULL REFERENCES pacientes(id_paciente),
    estado          VARCHAR(20) NOT NULL DEFAULT 'emitida'
                    CHECK (estado IN ('emitida', 'surtida', 'cancelada')),
    creado_en       TIMESTAMP NOT NULL DEFAULT now()
);

CREATE TABLE receta_detalle (
    id_receta_detalle UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    id_receta       UUID NOT NULL REFERENCES recetas(id_receta),
    id_medicamento  UUID NOT NULL REFERENCES medicamentos(id_medicamento),
    dosis           VARCHAR(120) NOT NULL,
    cantidad        INTEGER NOT NULL CHECK (cantidad > 0)
);

CREATE INDEX idx_recetas_paciente ON recetas(id_paciente);
CREATE INDEX idx_receta_detalle_receta ON receta_detalle(id_receta);

-- ============================================================
-- Fin del script
-- ============================================================

-- ============================================================
-- DATOS DE PRUEBA (seed data) para la demostración funcional
-- Estos INSERT permiten que el sistema no se vea vacío durante
-- la presentación del sábado.
-- ============================================================

-- Pacientes de prueba (IDs fijos para poder referenciarlos desde MongoDB/MS EHR)
INSERT INTO pacientes (id_paciente, curp, nombre, apellido_paterno, apellido_materno, fecha_nacimiento, sexo, telefono) VALUES
('11111111-1111-1111-1111-111111111111', 'MAAL900101HDFRRS01', 'Luz María', 'Márquez', 'Agúndez', '1990-01-01', 'F', '5551234567'),
('22222222-2222-2222-2222-222222222222', 'GARC850615HDFRRR02', 'Carlos', 'García', 'Ramírez', '1985-06-15', 'M', '5559876543'),
('33333333-3333-3333-3333-333333333333', 'LOPM920330MDFPRR03', 'María', 'López', 'Prieto', '1992-03-30', 'F', '5554567890');

-- Médicos de prueba (IDs fijos)
INSERT INTO medicos (id_medico, cedula_profesional, nombre, especialidad) VALUES
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', '12345678', 'Dr. Iván Ortiz', 'Medicina General'),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', '87654321', 'Dra. Sofía Hernández', 'Pediatría');

-- Turno de prueba (ID fijo, referencia al primer paciente y primer médico)
INSERT INTO turnos (id_turno, id_paciente, id_medico, fecha_hora, estado) VALUES
('cccccccc-cccc-cccc-cccc-cccccccccccc', '11111111-1111-1111-1111-111111111111', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', now() + interval '1 day', 'programado');

-- Consulta de prueba (ID fijo, referencia el turno anterior; esta es la que
-- MS EHR enlazará en MongoDB mediante id_consulta)
INSERT INTO consultas (id_consulta, id_turno, id_paciente, id_medico, motivo, triaje_nivel, estado) VALUES
('dddddddd-dddd-dddd-dddd-dddddddddddd', 'cccccccc-cccc-cccc-cccc-cccccccccccc', '11111111-1111-1111-1111-111111111111', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'Dolor de cabeza persistente y fiebre leve', 3, 'en_curso');

-- Medicamentos de prueba (cuadro básico)
INSERT INTO medicamentos (nombre, presentacion, existencia) VALUES
('Paracetamol', 'Tabletas 500mg', 200),
('Amoxicilina', 'Cápsulas 500mg', 150),
('Ibuprofeno', 'Tabletas 400mg', 180);

-- ============================================================
-- Fin de datos de prueba
-- ============================================================
