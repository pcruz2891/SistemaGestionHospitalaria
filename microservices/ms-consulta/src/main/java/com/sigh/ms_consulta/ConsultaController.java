package com.sigh.ms_consulta;

import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.kafka.core.KafkaTemplate;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.client.RestClientException;
import org.springframework.web.client.RestTemplate;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.UUID;

@RestController
@RequestMapping("/api/v1/consultas")
public class ConsultaController {

    private final ConsultaRepository consultaRepository;
    private final RestTemplate restTemplate = new RestTemplate();
    private final KafkaTemplate<String, String> kafkaTemplate;

    @Value("${ms.turnos.url}")
    private String msTurnosUrl;

    public ConsultaController(ConsultaRepository consultaRepository, KafkaTemplate<String, String> kafkaTemplate) {
        this.consultaRepository = consultaRepository;
        this.kafkaTemplate = kafkaTemplate;
    }

    @GetMapping
    public List<Consulta> listar() {
        return consultaRepository.findAll();
    }

    @PostMapping
    public Consulta crear(@Valid @RequestBody Consulta nuevaConsulta) {
        Consulta guardada = consultaRepository.save(nuevaConsulta);

        // Comunicación remota síncrona con MS Turnos (HTTP)
        try {
            String url = msTurnosUrl + "/api/v1/turnos/" + guardada.getIdTurno() + "/estado";
            Map<String, String> body = Map.of("estado", "atendido");
            restTemplate.put(url, body);
            System.out.println("MS Turnos notificado por HTTP: turno " + guardada.getIdTurno() + " marcado como atendido");
        } catch (RestClientException e) {
            System.out.println("Aviso: no se pudo notificar a MS Turnos por HTTP - " + e.getMessage());
        }

        // Publicación asíncrona del evento ConsultaCompletadaEvent en Kafka
        try {
            String payload = String.format(
                    "{\"idConsulta\":\"%s\",\"idTurno\":\"%s\",\"estado\":\"atendido\"}",
                    guardada.getIdConsulta(), guardada.getIdTurno()
            );
            kafkaTemplate.send("consulta-completada", guardada.getIdTurno().toString(), payload);
            System.out.println("Evento ConsultaCompletadaEvent publicado en Kafka para turno " + guardada.getIdTurno());
        } catch (Exception e) {
            System.out.println("Aviso: no se pudo publicar evento en Kafka - " + e.getMessage());
        }

        return guardada;
    }

    @PutMapping("/{id}/triaje")
    public Consulta registrarTriaje(@PathVariable UUID id, @RequestBody Map<String, Object> body) {
        Consulta consulta = consultaRepository.findById(id)
                .orElseThrow(() -> new org.springframework.web.server.ResponseStatusException(HttpStatus.NOT_FOUND, "Consulta no encontrada"));

        if (body.get("triaje_nivel") != null) {
            consulta.setTriajeNivel(((Number) body.get("triaje_nivel")).shortValue());
        }
        return consultaRepository.save(consulta);
    }

    // Convierte los errores de validación (@NotNull, @NotBlank) en una respuesta
    // 400 Bad Request clara, en vez del 500 Internal Server Error que se obtenía
    // antes cuando la petición llegaba con campos obligatorios vacíos.
    @ExceptionHandler(MethodArgumentNotValidException.class)
    public ResponseEntity<Map<String, String>> manejarErroresDeValidacion(MethodArgumentNotValidException ex) {
        Map<String, String> errores = new LinkedHashMap<>();
        ex.getBindingResult().getFieldErrors().forEach(error ->
                errores.put(error.getField(), error.getDefaultMessage())
        );
        return ResponseEntity.badRequest().body(errores);
    }
}