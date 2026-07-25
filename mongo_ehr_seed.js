// ============================================================
// Sistema de Gestión Hospitalaria (SIGH)
// Script de inicialización - MongoDB 8 (imagen: mongo:8)
// Base de datos documental para MS EHR (Registro Clínico Electrónico)
//
// Por qué MongoDB y no PostgreSQL para este microservicio:
// El EHR es de solo-anexado (append-only): cada consulta agrega un
// nuevo registro clínico, nunca se modifica ni se borra uno anterior
// (requisito de auditoría e integridad del historial médico). Además,
// el contenido clínico (síntomas, notas, resultados) varía mucho de
// una especialidad a otra, por lo que un esquema flexible tipo
// documento encaja mejor que columnas fijas.
//
// Conexión (según docker-compose.yml del proyecto):
//   Contenedor: mongodb-hospital
//   Usuario raíz: admin / admin123
//   Host:puerto: localhost:27017
//   Base de datos de la aplicación: ehr_db
//
// Para ejecutar este script dentro del contenedor:
//   docker exec -i mongodb-hospital mongosh -u admin -p admin123 \
//     --authenticationDatabase admin < mongo_ehr_seed.js
// ============================================================

use("ehr_db");

// ------------------------------------------------------------
// Colección: registros_clinicos
// Cada documento es un evento clínico INMUTABLE. Nunca se hace
// update() sobre un registro existente; una corrección se agrega
// como un nuevo documento que referencia al anterior (campo
// "registro_previo"), preservando el historial completo.
// ------------------------------------------------------------

db.createCollection("registros_clinicos", {
  validator: {
    $jsonSchema: {
      bsonType: "object",
      required: ["id_paciente", "id_consulta", "tipo_evento", "fecha_hora", "contenido"],
      properties: {
        id_paciente: {
          bsonType: "string",
          description: "UUID del paciente (debe coincidir con pacientes.id_paciente en PostgreSQL)"
        },
        id_consulta: {
          bsonType: "string",
          description: "UUID de la consulta en MS Consulta (PostgreSQL) que originó este registro"
        },
        tipo_evento: {
          enum: ["triaje", "diagnostico", "nota_evolucion", "resultado_laboratorio", "receta_emitida"],
          description: "Tipo de evento clínico registrado"
        },
        fecha_hora: {
          bsonType: "date"
        },
        contenido: {
          bsonType: "object",
          description: "Datos específicos del evento; su estructura varía según tipo_evento"
        },
        registrado_por: {
          bsonType: "string",
          description: "Cédula profesional o rol de quien generó el registro (médico/enfermera)"
        }
      }
    }
  }
});

// Índices para consultas frecuentes del historial de un paciente
db.registros_clinicos.createIndex({ id_paciente: 1, fecha_hora: -1 });
db.registros_clinicos.createIndex({ id_consulta: 1 });

// ------------------------------------------------------------
// Datos de prueba (append-only): 3 eventos ligados a la consulta
// sembrada en PostgreSQL (id_consulta = dddddddd-...-dddddddddddd,
// id_paciente = 11111111-...-111111111111, paciente Luz María)
// ------------------------------------------------------------

db.registros_clinicos.insertMany([
  {
    id_paciente: "11111111-1111-1111-1111-111111111111",
    id_consulta: "dddddddd-dddd-dddd-dddd-dddddddddddd",
    tipo_evento: "triaje",
    fecha_hora: new Date(),
    contenido: {
      temperatura_c: 37.8,
      presion_arterial: "118/76",
      frecuencia_cardiaca: 88,
      nivel_urgencia: 3,
      notas: "Paciente refiere dolor de cabeza y fiebre leve de 2 días de evolución."
    },
    registrado_por: "Enfermera - Turno matutino"
  },
  {
    id_paciente: "11111111-1111-1111-1111-111111111111",
    id_consulta: "dddddddd-dddd-dddd-dddd-dddddddddddd",
    tipo_evento: "diagnostico",
    fecha_hora: new Date(),
    contenido: {
      diagnostico: "Cefalea tensional con proceso febril leve, probable origen viral",
      cie10: "G44.2",
      plan: "Manejo sintomático y observación por 48 horas"
    },
    registrado_por: "12345678"
  },
  {
    id_paciente: "11111111-1111-1111-1111-111111111111",
    id_consulta: "dddddddd-dddd-dddd-dddd-dddddddddddd",
    tipo_evento: "receta_emitida",
    fecha_hora: new Date(),
    contenido: {
      medicamentos: [
        { nombre: "Paracetamol", dosis: "500mg cada 8 horas por 3 días" }
      ]
    },
    registrado_por: "12345678"
  }
]);

// ------------------------------------------------------------
// Verificación rápida
// ------------------------------------------------------------
print("Registros clínicos insertados:");
printjson(db.registros_clinicos.find({ id_paciente: "11111111-1111-1111-1111-111111111111" }).toArray());

// ============================================================
// Fin del script
// ============================================================
