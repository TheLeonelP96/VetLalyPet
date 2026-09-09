-- Evita aplicaciones duplicadas para la misma mascota, vacuna y fecha.
ALTER TABLE historialvac
    ADD CONSTRAINT uq_historialvac_mascota_vacuna_fecha
        UNIQUE (IDmascota, IDvacuna, fechaAplicacion),
    ADD CONSTRAINT chk_historialvac_proxima_dosis
        CHECK (fechaProximaDosis IS NULL OR fechaProximaDosis >= fechaAplicacion);
