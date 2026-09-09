-- Preparación del esquema vetlalypets antes de adaptar la aplicación.
-- Respaldo previo: respaldos/vetlalypets_pre_migracion_20260830_210500/
-- MariaDB 10.4 / XAMPP. Esta migración no copia datos desde lalypets.

USE vetlalypets;

-- MariaDB impide reconstruir tablas referenciadas aunque no se modifique la
-- clave referenciada. Las tablas de negocio están vacías y las FKs se
-- conservan, por lo que se suspende la comprobación solo durante los ALTER.
SET FOREIGN_KEY_CHECKS = 0;

ALTER DATABASE vetlalypets
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Normalizar los únicos datos existentes.
UPDATE rol SET nombre_Rol = TRIM(nombre_Rol);
UPDATE rol SET nombre_Rol = 'Veterinario' WHERE nombre_Rol = 'Veteinario';

ALTER TABLE rol CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE especie CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE raza CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE clientes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mascotas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE servicios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE citas CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE vacuna CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE historialvac CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE pago CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Retirar temporalmente las relaciones para que MariaDB permita reconstruir
-- tanto las tablas padre como las columnas que pasarán a ser obligatorias.
ALTER TABLE citas
    DROP FOREIGN KEY citas_ibfk_1,
    DROP FOREIGN KEY citas_ibfk_2,
    DROP FOREIGN KEY citas_ibfk_3;
ALTER TABLE historialvac
    DROP FOREIGN KEY historialvac_ibfk_1,
    DROP FOREIGN KEY historialvac_ibfk_2;
ALTER TABLE mascotas
    DROP FOREIGN KEY mascotas_ibfk_1,
    DROP FOREIGN KEY mascotas_ibfk_2;
ALTER TABLE pago DROP FOREIGN KEY pago_ibfk_1;
ALTER TABLE raza DROP FOREIGN KEY raza_ibfk_1;
ALTER TABLE usuarios DROP FOREIGN KEY usuarios_ibfk_1;
ALTER TABLE vacuna DROP FOREIGN KEY vacuna_ibfk_1;

ALTER TABLE rol
    ADD CONSTRAINT uq_rol_nombre UNIQUE (nombre_Rol);

ALTER TABLE especie
    ADD CONSTRAINT uq_especie_nombre UNIQUE (nombre_Especie);

ALTER TABLE raza
    MODIFY IDespecie INT(11) NOT NULL,
    ADD CONSTRAINT uq_raza_especie_nombre UNIQUE (IDespecie, nombre_Raza);

ALTER TABLE clientes
    MODIFY telefono_c VARCHAR(20) NOT NULL,
    MODIFY habilitadoC TINYINT(1) NOT NULL DEFAULT 1,
    ADD CONSTRAINT chk_clientes_habilitado CHECK (habilitadoC IN (0, 1));

ALTER TABLE mascotas
    MODIFY peso DECIMAL(6,2) NOT NULL,
    MODIFY sexo ENUM('Macho', 'Hembra', 'No especificado') NOT NULL,
    MODIFY habilitadoM TINYINT(1) NOT NULL DEFAULT 1,
    MODIFY descripcion VARCHAR(255) NULL,
    MODIFY color VARCHAR(50) NULL,
    MODIFY IDcliente INT(11) NOT NULL,
    MODIFY IDraza INT(11) NOT NULL,
    ADD CONSTRAINT chk_mascotas_peso CHECK (peso > 0),
    ADD CONSTRAINT chk_mascotas_habilitado CHECK (habilitadoM IN (0, 1));

-- El correo electrónico reemplaza al nombre de usuario como identificador.
-- La contraseña seguirá almacenándose como hash generado por password_hash().
ALTER TABLE usuarios
    MODIFY telefono_U VARCHAR(20) NOT NULL,
    MODIFY email_U VARCHAR(254) NOT NULL,
    MODIFY password VARCHAR(255) NOT NULL,
    MODIFY estado_U TINYINT(1) NOT NULL DEFAULT 1,
    MODIFY sesion_U TINYINT(1) NOT NULL DEFAULT 0,
    MODIFY IDrol INT(11) NOT NULL,
    ADD CONSTRAINT uq_usuarios_email UNIQUE (email_U),
    ADD CONSTRAINT chk_usuarios_estado CHECK (estado_U IN (0, 1)),
    ADD CONSTRAINT chk_usuarios_sesion CHECK (sesion_U IN (0, 1));

ALTER TABLE servicios
    MODIFY descripcion VARCHAR(255) NOT NULL,
    MODIFY precio DECIMAL(10,2) NOT NULL,
    MODIFY estadoServicio TINYINT(1) NOT NULL DEFAULT 1,
    ADD CONSTRAINT uq_servicios_tipo UNIQUE (tipoServicio),
    ADD CONSTRAINT chk_servicios_precio CHECK (precio >= 0),
    ADD CONSTRAINT chk_servicios_estado CHECK (estadoServicio IN (0, 1));

ALTER TABLE citas
    MODIFY estadoCita VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
    MODIFY IDusuario INT(11) NOT NULL,
    MODIFY IDmascota INT(11) NOT NULL,
    DROP INDEX IDusuario,
    ADD INDEX idx_citas_disponibilidad (IDusuario, fechaAtencion, estadoCita),
    ADD CONSTRAINT chk_citas_estado
        CHECK (estadoCita IN ('Pendiente', 'Cancelada', 'Completada'));

ALTER TABLE vacuna
    MODIFY Descripcion VARCHAR(255) NOT NULL,
    MODIFY preciovac DECIMAL(10,2) NOT NULL,
    MODIFY estadoVac TINYINT(1) NOT NULL DEFAULT 1,
    MODIFY IDespecie INT(11) NOT NULL,
    ADD CONSTRAINT uq_vacuna_especie_nombre UNIQUE (IDespecie, nombreVac),
    ADD CONSTRAINT chk_vacuna_precio CHECK (preciovac >= 0),
    ADD CONSTRAINT chk_vacuna_stock CHECK (stock >= 0),
    ADD CONSTRAINT chk_vacuna_estado CHECK (estadoVac IN (0, 1));

-- Una aplicación puede registrarse desde una cita propia o desde un
-- comprobante externo, pero siempre debe identificar mascota y vacuna.
ALTER TABLE historialvac
    CHANGE fechaApli fechaAplicacion DATE NOT NULL,
    CHANGE fechaAtencion fechaProximaDosis DATE NULL,
    MODIFY observaciones VARCHAR(255) NULL,
    MODIFY IDvacuna INT(11) NOT NULL,
    ADD COLUMN IDmascota INT(11) NOT NULL AFTER IDcita,
    ADD INDEX idx_historialvac_mascota_fecha (IDmascota, fechaAplicacion);

ALTER TABLE pago
    MODIFY montopago DECIMAL(10,2) NOT NULL,
    MODIFY metodopago VARCHAR(30) NOT NULL,
    MODIFY fechapago DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ADD INDEX idx_pago_fecha (fechapago),
    ADD CONSTRAINT chk_pago_monto CHECK (montopago >= 0);

-- Reconstruir todas las relaciones con reglas explícitas.
ALTER TABLE raza
    ADD CONSTRAINT fk_raza_especie FOREIGN KEY (IDespecie)
        REFERENCES especie (IDespecie) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE mascotas
    ADD CONSTRAINT fk_mascotas_cliente FOREIGN KEY (IDcliente)
        REFERENCES clientes (IDcliente) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_mascotas_raza FOREIGN KEY (IDraza)
        REFERENCES raza (IDraza) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuarios_rol FOREIGN KEY (IDrol)
        REFERENCES rol (IDrol) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE servicios
    ADD CONSTRAINT chk_servicios_descripcion CHECK (CHAR_LENGTH(TRIM(descripcion)) > 0);
ALTER TABLE citas
    ADD CONSTRAINT fk_citas_usuario FOREIGN KEY (IDusuario)
        REFERENCES usuarios (IDusuario) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_citas_mascota FOREIGN KEY (IDmascota)
        REFERENCES mascotas (IDmascota) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_citas_servicio FOREIGN KEY (IDservicio)
        REFERENCES servicios (IDservicio) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE vacuna
    ADD CONSTRAINT fk_vacuna_especie FOREIGN KEY (IDespecie)
        REFERENCES especie (IDespecie) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE historialvac
    ADD CONSTRAINT fk_historialvac_cita FOREIGN KEY (IDcita)
        REFERENCES citas (IDcita) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_historialvac_vacuna FOREIGN KEY (IDvacuna)
        REFERENCES vacuna (IDvacuna) ON UPDATE CASCADE ON DELETE RESTRICT,
    ADD CONSTRAINT fk_historialvac_mascota FOREIGN KEY (IDmascota)
        REFERENCES mascotas (IDmascota) ON UPDATE CASCADE ON DELETE RESTRICT;
ALTER TABLE pago
    ADD CONSTRAINT fk_pago_cita FOREIGN KEY (IDcita)
        REFERENCES citas (IDcita) ON UPDATE CASCADE ON DELETE RESTRICT;

SET FOREIGN_KEY_CHECKS = 1;
