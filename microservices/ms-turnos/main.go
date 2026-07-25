package main

import (
	"database/sql"
	"log"
	"net/http"
	"time"

	"github.com/gin-gonic/gin"
	_ "github.com/lib/pq"
)

var db *sql.DB

type Turno struct {
	IDTurno    string `json:"id_turno,omitempty"`
	IDPaciente string `json:"id_paciente" binding:"required"`
	IDMedico   string `json:"id_medico" binding:"required"`
	FechaHora  string `json:"fecha_hora" binding:"required"`
	Estado     string `json:"estado,omitempty"`
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

	result, err := db.Exec(`
		UPDATE turnos
		SET estado = $1, actualizado_en = now()
		WHERE id_turno = $2`,
		body.Estado, idTurno,
	)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{"error": err.Error()})
		return
	}

	filas, _ := result.RowsAffected()
	if filas == 0 {
		c.JSON(http.StatusNotFound, gin.H{"error": "turno no encontrado"})
		return
	}

	c.JSON(http.StatusOK, gin.H{"id_turno": idTurno, "estado": body.Estado})
}