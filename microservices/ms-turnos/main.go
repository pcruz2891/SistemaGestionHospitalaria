package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"log"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
	_ "github.com/lib/pq"
	"github.com/segmentio/kafka-go"
)

var db *sql.DB

type Turno struct {
	IDTurno    string `json:"id_turno,omitempty"`
	IDPaciente string `json:"id_paciente" binding:"required"`
	IDMedico   string `json:"id_medico" binding:"required"`
	FechaHora  string `json:"fecha_hora" binding:"required"`
	Estado     string `json:"estado,omitempty"`
}

type ConsultaCompletadaEvent struct {
	IDConsulta string `json:"idConsulta"`
	IDTurno    string `json:"idTurno"`
	Estado     string `json:"estado"`
}

func main() {
	connStr := "host=localhost port=5432 user=hospital password=hospital123 dbname=hospitaldb sslmode=disable"

	var err error
	db, err = sql.Open("postgres", connStr)
	if err != nil {
		log.Fatal("Error abriendo conexión a la base de datos:", err)
	}
	defer db.Close()

	if err = db.Ping(); err != nil {
		log.Fatal("No se pudo conectar a PostgreSQL:", err)
	}
	log.Println("Conectado a PostgreSQL correctamente")

	// Iniciar el consumidor de Kafka en segundo plano
	go consumirEventosConsulta()

	router := gin.Default()

	router.GET("/health", func(c *gin.Context) {
		c.JSON(http.StatusOK, gin.H{"status": "ok", "service": "ms-turnos"})
	})

	router.GET("/api/v1/turnos", listarTurnos)
	router.POST("/api/v1/turnos", crearTurno)
	router.PUT("/api/v1/turnos/:id/estado", actualizarEstadoTurno)

	log.Println("MS Turnos escuchando en el puerto 8081...")
	router.Run(":8081")
}

// consumirEventosConsulta se suscribe al topic de Kafka "consulta-completada"
// y actualiza el estado del turno correspondiente cada vez que llega un evento.
// Al ser un consumer group, si MS Turnos está caído los mensajes quedan
// retenidos en Kafka y se procesan en cuanto el servicio se reconecta.
func consumirEventosConsulta() {
	reader := kafka.NewReader(kafka.ReaderConfig{
		Brokers: []string{"localhost:9092"},
		Topic:   "consulta-completada",
		GroupID: "ms-turnos-group",
	})
	defer reader.Close()

	log.Println("Consumidor de Kafka escuchando el topic 'consulta-completada'...")

	for {
		m, err := reader.ReadMessage(context.Background())
		if err != nil {
			log.Println("Error leyendo mensaje de Kafka:", err)
			time.Sleep(2 * time.Second)
			continue
		}

		var evento ConsultaCompletadaEvent
		if err := json.Unmarshal(m.Value, &evento); err != nil {
			log.Println("Error parseando evento de Kafka:", err)
			continue
		}

		if err := actualizarEstado(evento.IDTurno, evento.Estado); err != nil {
			log.Println("Error actualizando turno desde evento Kafka:", err)
		} else {
			log.Println("Turno actualizado via Kafka -> id:", evento.IDTurno, "estado:", evento.Estado)
		}
	}
}

// actualizarEstado contiene la lógica compartida para cambiar el estado de un
// turno, usada tanto por el endpoint HTTP como por el consumidor de Kafka.
func actualizarEstado(idTurno string, estado string) error {
	_, err := db.Exec(`
		UPDATE turnos
		SET estado = $1, actualizado_en = now()
		WHERE id_turno = $2`,
		estado, idTurno,
	)
	return err
}

func listarTurnos(c *gin.Context) {
	rows, err := db.Query(`
		SELECT id_turno, id_paciente, id_medico, fecha_hora, estado
		FROM turnos
		ORDER BY fecha_hora`)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}
	defer rows.Close()

	var turnos []Turno
	for rows.Next() {
		var t Turno
		var fechaHora time.Time
		if err := rows.Scan(&t.IDTurno, &t.IDPaciente, &t.IDMedico, &fechaHora, &t.Estado); err != nil {
			c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
			return
		}
		t.FechaHora = fechaHora.Format(time.RFC3339)
		turnos = append(turnos, t)
	}

	c.JSON(http.StatusOK, turnos)
}

func crearTurno(c *gin.Context) {
	var nuevo Turno
	if err := c.ShouldBindJSON(&nuevo); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if nuevo.Estado == "" {
		nuevo.Estado = "programado"
	}

	var idGenerado string
	err := db.QueryRow(`
		INSERT INTO turnos (id_paciente, id_medico, fecha_hora, estado)
		VALUES ($1, $2, $3, $4)
		RETURNING id_turno`,
		nuevo.IDPaciente, nuevo.IDMedico, nuevo.FechaHora, nuevo.Estado,
	).Scan(&idGenerado)

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	nuevo.IDTurno = idGenerado
	c.JSON(http.StatusCreated, nuevo)
}

func actualizarEstadoTurno(c *gin.Context) {
	idTurno := c.Param("id")

	var body struct {
		Estado string `json:"estado" binding:"required"`
	}
	if err := c.ShouldBindJSON(&body); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
		return
	}

	if err := actualizarEstado(idTurno, body.Estado); err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	c.JSON(http.StatusOK, gin.H{"id_turno": idTurno, "estado": body.Estado})
}