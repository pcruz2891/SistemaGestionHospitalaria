# Sistema de Gestión Hospitalaria (SIGH)

Proyecto del Seminario de Desarrollo de Sistemas Distribuidos — Universidad del Sur.
Docente: Dr. Iván Ortiz Ramírez.

## Equipo
- Pedro Cruz Rivas
- Ileana Berenice Santiago
- Luz María Márquez Agúndez
- José Eduardo

## Arquitectura

**Implementado en esta fase (Actividad 4):**
- **MS Turnos** (Go / Gin) — gestión de turnos, PostgreSQL
- **MS Consulta** (Java / Spring Boot) — gestión de consultas médicas, PostgreSQL

Comunicación síncrona vía HTTP directo entre microservicios (sin API Gateway en esta fase) y
asíncrona vía Kafka (`ConsultaCompletadaEvent`).

**Diseño original (Actividad 2), pendiente de implementar:**
- **MS EHR** (Java / Spring Boot) — expediente clínico, MongoDB, gRPC interno, append-only
- **MS Farmacia** (Go / Gin) — recetas y medicamentos, PostgreSQL
- API Gateway (Kong) para enrutamiento centralizado

## Base de datos
El script oficial de base de datos relacional es **`script_bd_sigh.sql`**, que cubre MS Turnos
y MS Consulta sobre PostgreSQL. MS EHR usaría MongoDB (ver `mongo_ehr_seed.js`) cuando se
implemente, por sus restricciones de inmutabilidad y agregado independiente.

> **Nota:** existe un script anterior, `docs/legacy/script_base_datos.sh`, de una etapa temprana
> del diseño (modelo monolítico con IDs seriales, antes de separar MS EHR en MongoDB). Se
> conserva solo como referencia histórica y **no debe usarse** para levantar el sistema actual.

## Cómo levantar el entorno
    docker-compose up -d --build
    Get-Content script_bd_sigh.sql | docker exec -i postgres-hospital psql -U hospital -d hospitaldb
