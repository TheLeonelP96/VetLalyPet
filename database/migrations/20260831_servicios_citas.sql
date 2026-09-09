-- Servicios que realmente ofrece LalyPet's mediante citas.
UPDATE servicios
SET tipoServicio = 'Vacunación',
    descripcion = 'Aplicación de vacunas para mascotas.'
WHERE tipoServicio = 'Consulta general';

INSERT INTO servicios (tipoServicio, descripcion, precio, estadoServicio)
SELECT 'Peluquería + baño', 'Servicio general de higiene y arreglo.', 0.00, 1
WHERE NOT EXISTS (
    SELECT 1 FROM servicios WHERE tipoServicio = 'Peluquería + baño'
);
