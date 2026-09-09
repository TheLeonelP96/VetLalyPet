-- Servicios iniciales disponibles para reservar una cita.
INSERT INTO servicios (tipoServicio, descripcion, precio, estadoServicio)
SELECT 'Vacunación', 'Aplicación de vacunas para mascotas.', 0.00, 1
WHERE NOT EXISTS (
    SELECT 1 FROM servicios WHERE tipoServicio = 'Vacunación'
);

INSERT INTO servicios (tipoServicio, descripcion, precio, estadoServicio)
SELECT 'Peluquería + baño', 'Servicio general de higiene y arreglo.', 60.00, 1
WHERE NOT EXISTS (
    SELECT 1 FROM servicios WHERE tipoServicio = 'Peluquería + baño'
);
