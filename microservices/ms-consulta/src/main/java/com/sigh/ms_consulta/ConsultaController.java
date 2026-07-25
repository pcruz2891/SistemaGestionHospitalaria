package com.sigh.ms_consulta;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpStatus;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.client.RestClientException;
import org.springframework.web.client.RestTemplate;

import java.util.List;
import java.util.Map;
import java.util.UUID;

@RestController
@RequestMapping("/api/v1/consultas")
public class ConsultaController {

    private final ConsultaRepository consultaRepository;
    private final RestTemplate restTemplate = new RestTemplate();

    @Value("${ms.turnos.url}")
    private String msTurnosUrl;

    public ConsultaController(ConsultaRepository consultaRepository) {
        this.consultaRepository = consultaRepository;
    }

    @GetMapping
    public List<Consulta> listar() {
        return consultaRepository.findAll();
    }

    @PostMapping
    public Consulta crear(@RequestBody Consulta nuevaConsulta) {
        Consulta guardada = consultaRepository.save(nuevaConsulta);

        // Comunicación remota real con MS Turnos: al registrar la consulta,
        // se notifica al microservicio de Turnos para marcarlo como "atendido".
        try {
            String url = msTurnosUrl + "/api/v1/turnos/" + guardada.getIdTurno() + "/estado";
            Map<String, String> body = Map.of("estado", "atendido");
            restTemplate.put(url, body);
            System.out.println("MS Turnos notificado: turno " + guardada.getIdTurno() + " marcado como atendido");
        } catch (RestClientException e) {
            System.out.println("Aviso: no se pudo notificar a MS Turnos - " + e.getMessage());
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
}