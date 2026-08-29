# LalyPets

Sistema web de gestión veterinaria desarrollado con PHP, MariaDB, Bootstrap,
jQuery y FullCalendar.

## Requisitos

- XAMPP con Apache, MariaDB, PHP 8.0 o superior y las extensiones PDO MySQL,
  GD y Fileinfo.
- Base de datos `lalypets` importada en MariaDB.

## Ejecución local

1. Coloque el proyecto en `C:\xampp\htdocs\LalyPets`.
2. Inicie Apache y MySQL desde XAMPP.
3. Abra `http://localhost/LalyPets/`.

La conexión local usa por defecto `localhost`, base `lalypets`, usuario `root`
y contraseña vacía. Para otro entorno se pueden definir:

- `LALYPETS_DB_HOST`
- `LALYPETS_DB_NAME`
- `LALYPETS_DB_USER`
- `LALYPETS_DB_PASSWORD`

## Seguridad y roles

- Las contraseñas se almacenan con `password_hash()` y se comprueban con
  `password_verify()`.
- Las operaciones de escritura están protegidas con token CSRF.
- Los endpoints AJAX exigen una sesión iniciada.
- La administración de usuarios está reservada al rol administrador (`IDrol=1`).

## Respaldo

Antes de modificar código o estructura de la base, genere una copia de ambos.
Los respaldos locales se guardan en `respaldos/` y no deben publicarse porque
pueden contener datos personales.
