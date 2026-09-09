-- Vacunación se calcula desde las vacunas aplicadas; peluquería tiene tarifa propia.
UPDATE servicios SET precio = 0.00 WHERE tipoServicio = 'Vacunación';
UPDATE servicios SET precio = 60.00 WHERE tipoServicio = 'Peluquería + baño' AND precio = 0.00;
