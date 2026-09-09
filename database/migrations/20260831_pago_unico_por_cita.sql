-- Cada cita completada genera como máximo un pago auditable.
ALTER TABLE pago
    ADD CONSTRAINT uq_pago_cita UNIQUE (IDcita);
