# Sistema de Gestión Hospitalaria (SIGH)

Proyecto del Seminario de Desarrollo de Sistemas Distribuidos — Universidad del Sur.
Docente: Dr. Iván Ortiz Ramírez.

## Equipo

- Pedro Cruz Rivas
- Ileana Berenice Santiago
- Luz María Márquez Agúndez
- José Eduardo

## Arquitectura

Cuatro microservicios:

- **MS Turnos** (Go / Gin) — gestión de turnos, PostgreSQL
- **MS Consulta** (Java / Spring Boot) — gestión de consultas médicas, PostgreSQL
- **MS EHR** (Java / Spring Boot) — expediente clínico, MongoDB, gRPC interno, append-only
- **MS Farmacia** (Go / Gin) — recetas y medicamentos, PostgreSQL

Comunicación síncrona vía HTTP/API Gateway (Kong), y asíncrona vía Kafka (`ConsultaCompletadaEvent`, `RecetaEmitidaEvent`).

## Base de datos

El script oficial de base de datos relacional es **`script_bd_sigh.sql`**, que cubre MS Turnos, MS Consulta y MS Farmacia sobre PostgreSQL. MS EHR usa MongoDB (ver `mongo_ehr_seed.js`) por sus restricciones de inmutabilidad y agregado independiente, por lo que no tiene script SQL.

> **Nota:** existe un script anterior, `docs/legacy/script_base_datos.sh`, de una etapa temprana del diseño (modelo monolítico con IDs seriales, antes de separar MS EHR en MongoDB). Se conserva solo como referencia histórica y **no debe usarse** para levantar el sistema actual.

## Cómo levantar el entorno

    docker-compose up -d
    docker exec -i postgres-hospital psql -U hospital -d hospitaldb < script_bd_sigh.sql
