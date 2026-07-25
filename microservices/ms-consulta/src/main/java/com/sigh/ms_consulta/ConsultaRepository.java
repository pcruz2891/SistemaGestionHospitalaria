package com.sigh.ms_consulta;

import org.springframework.data.jpa.repository.JpaRepository;
import java.util.UUID;

public interface ConsultaRepository extends JpaRepository<Consulta, UUID> {
}