/*
=========================================================
Sistema de Gestión Hospitalaria (SIGH-Público)
Script de Base de Datos
Motor: PostgreSQL 17
=========================================================
*/

-- =============================================
-- Crear Base de Datos
-- =============================================

CREATE DATABASE sigh_hospital;

-- Conectarse posteriormente a la BD:
-- \c sigh_hospital


-- =============================================
-- Tabla Paciente
-- =============================================

CREATE TABLE paciente (

    paciente_id SERIAL PRIMARY KEY,

    curp VARCHAR(18) NOT NULL UNIQUE,

    nombre VARCHAR(80) NOT NULL,

    apellido_paterno VARCHAR(80) NOT NULL,

    apellido_materno VARCHAR(80),

    fecha_nacimiento DATE,

    sexo CHAR(1),

    telefono VARCHAR(15),

    direccion TEXT,

    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP

);


-- =============================================
-- Tabla Médico
-- =============================================

CREATE TABLE medico (

    medico_id SERIAL PRIMARY KEY,

    nombre VARCHAR(120) NOT NULL,

    especialidad VARCHAR(100),

    cedula_profesional VARCHAR(30) UNIQUE

);


-- =============================================
-- Tabla Enfermera
-- =============================================

CREATE TABLE enfermera (

    enfermera_id SERIAL PRIMARY KEY,

    nombre VARCHAR(120) NOT NULL,

    turno VARCHAR(30)

);


-- =============================================
-- Tabla Farmacia
-- =============================================

CREATE TABLE farmacia (

    farmacia_id SERIAL PRIMARY KEY,

    nombre VARCHAR(120),

    ubicacion VARCHAR(150)

);


-- =============================================
-- Tabla Turnos
-- =============================================

CREATE TABLE turno (

    turno_id SERIAL PRIMARY KEY,

    paciente_id INTEGER NOT NULL,

    medico_id INTEGER NOT NULL,

    fecha DATE NOT NULL,

    hora TIME NOT NULL,

    estado VARCHAR(30),

    CONSTRAINT fk_turno_paciente
        FOREIGN KEY(paciente_id)
        REFERENCES paciente(paciente_id),

    CONSTRAINT fk_turno_medico
        FOREIGN KEY(medico_id)
        REFERENCES medico(medico_id)

);


-- =============================================
-- Expediente Clínico
-- =============================================

CREATE TABLE expediente_clinico (

    expediente_id SERIAL PRIMARY KEY,

    paciente_id INTEGER UNIQUE,

    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    alergias TEXT,

    antecedentes TEXT,

    observaciones TEXT,

    CONSTRAINT fk_expediente_paciente
        FOREIGN KEY(paciente_id)
        REFERENCES paciente(paciente_id)

);


-- =============================================
-- Consulta Médica
-- =============================================

CREATE TABLE consulta (

    consulta_id SERIAL PRIMARY KEY,

    paciente_id INTEGER,

    medico_id INTEGER,

    expediente_id INTEGER,

    fecha_consulta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    motivo_consulta TEXT,

    diagnostico TEXT,

    tratamiento TEXT,

    CONSTRAINT fk_consulta_paciente
        FOREIGN KEY(paciente_id)
        REFERENCES paciente(paciente_id),

    CONSTRAINT fk_consulta_medico
        FOREIGN KEY(medico_id)
        REFERENCES medico(medico_id),

    CONSTRAINT fk_consulta_expediente
        FOREIGN KEY(expediente_id)
        REFERENCES expediente_clinico(expediente_id)

);


-- =============================================
-- Signos Vitales
-- =============================================

CREATE TABLE signos_vitales (

    signos_id SERIAL PRIMARY KEY,

    consulta_id INTEGER,

    enfermera_id INTEGER,

    temperatura NUMERIC(4,2),

    peso NUMERIC(5,2),

    estatura NUMERIC(4,2),

    presion_arterial VARCHAR(20),

    frecuencia_cardiaca INTEGER,

    frecuencia_respiratoria INTEGER,

    saturacion INTEGER,

    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sv_consulta
        FOREIGN KEY(consulta_id)
        REFERENCES consulta(consulta_id),

    CONSTRAINT fk_sv_enfermera
        FOREIGN KEY(enfermera_id)
        REFERENCES enfermera(enfermera_id)

);


-- =============================================
-- Medicamentos
-- =============================================

CREATE TABLE medicamento (

    medicamento_id SERIAL PRIMARY KEY,

    nombre VARCHAR(120),

    existencia INTEGER,

    precio NUMERIC(10,2)

);


-- =============================================
-- Recetas
-- =============================================

CREATE TABLE receta (

    receta_id SERIAL PRIMARY KEY,

    consulta_id INTEGER,

    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    firma_digital VARCHAR(255),

    estado VARCHAR(30),

    CONSTRAINT fk_receta_consulta
        FOREIGN KEY(consulta_id)
        REFERENCES consulta(consulta_id)

);


-- =============================================
-- Detalle Receta
-- =============================================

CREATE TABLE detalle_receta (

    detalle_id SERIAL PRIMARY KEY,

    receta_id INTEGER,

    medicamento_id INTEGER,

    cantidad INTEGER,

    dosis VARCHAR(80),

    frecuencia VARCHAR(80),

    dias INTEGER,

    CONSTRAINT fk_detalle_receta
        FOREIGN KEY(receta_id)
        REFERENCES receta(receta_id),

    CONSTRAINT fk_detalle_medicamento
        FOREIGN KEY(medicamento_id)
        REFERENCES medicamento(medicamento_id)

);


-- =============================================
-- Surtido de Medicamentos
-- =============================================

CREATE TABLE surtido_medicamento (

    surtido_id SERIAL PRIMARY KEY,

    receta_id INTEGER,

    farmacia_id INTEGER,

    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    estado VARCHAR(30),

    CONSTRAINT fk_surtido_receta
        FOREIGN KEY(receta_id)
        REFERENCES receta(receta_id),

    CONSTRAINT fk_surtido_farmacia
        FOREIGN KEY(farmacia_id)
        REFERENCES farmacia(farmacia_id)

);


-- =============================================
-- Datos Iniciales
-- =============================================

INSERT INTO medico(nombre, especialidad, cedula_profesional)
VALUES
('Juan Pérez','Medicina General','MED123456');

INSERT INTO enfermera(nombre, turno)
VALUES
('María López','Matutino');

INSERT INTO farmacia(nombre,ubicacion)
VALUES
('Farmacia Central','Hospital General');

INSERT INTO medicamento(nombre,existencia,precio)
VALUES
('Paracetamol 500mg',250,18.50),
('Ibuprofeno 400mg',180,22.00),
('Amoxicilina 500mg',100,65.00);