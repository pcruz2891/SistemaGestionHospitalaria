package com.sigh.ms_consulta;

import jakarta.persistence.*;
import java.time.LocalDateTime;
import java.util.UUID;

@Entity
@Table(name = "consultas")
public class Consulta {

    @Id
    @GeneratedValue
    @Column(name = "id_consulta")
    private UUID idConsulta;

    @Column(name = "id_turno", nullable = false)
    private UUID idTurno;

    @Column(name = "id_paciente", nullable = false)
    private UUID idPaciente;

    @Column(name = "id_medico", nullable = false)
    private UUID idMedico;

    @Column(name = "motivo", nullable = false)
    private String motivo;

    @Column(name = "diagnostico")
    private String diagnostico;

    @Column(name = "triaje_nivel")
    private Short triajeNivel;

    @Column(name = "estado")
    private String estado = "en_curso";

    @Column(name = "fecha_atencion")
    private LocalDateTime fechaAtencion = LocalDateTime.now();

    // Getters y setters
    public UUID getIdConsulta() { return idConsulta; }
    public void setIdConsulta(UUID idConsulta) { this.idConsulta = idConsulta; }

    public UUID getIdTurno() { return idTurno; }
    public void setIdTurno(UUID idTurno) { this.idTurno = idTurno; }

    public UUID getIdPaciente() { return idPaciente; }
    public void setIdPaciente(UUID idPaciente) { this.idPaciente = idPaciente; }

    public UUID getIdMedico() { return idMedico; }
    public void setIdMedico(UUID idMedico) { this.idMedico = idMedico; }

    public String getMotivo() { return motivo; }
    public void setMotivo(String motivo) { this.motivo = motivo; }

    public String getDiagnostico() { return diagnostico; }
    public void setDiagnostico(String diagnostico) { this.diagnostico = diagnostico; }

    public Short getTriajeNivel() { return triajeNivel; }
    public void setTriajeNivel(Short triajeNivel) { this.triajeNivel = triajeNivel; }

    public String getEstado() { return estado; }
    public void setEstado(String estado) { this.estado = estado; }

    public LocalDateTime getFechaAtencion() { return fechaAtencion; }
    public void setFechaAtencion(LocalDateTime fechaAtencion) { this.fechaAtencion = fechaAtencion; }
}