-- Catálogo inicial de especies y razas para vetlalypets.
-- Puede ejecutarse nuevamente: las restricciones UNIQUE evitan duplicados.

USE vetlalypets;
START TRANSACTION;

INSERT INTO especie (nombre_Especie) VALUES
    ('Felino'),
    ('Canino'),
    ('Ave'),
    ('Roedor'),
    ('Conejo')
ON DUPLICATE KEY UPDATE nombre_Especie = VALUES(nombre_Especie);

INSERT INTO raza (nombre_Raza, IDespecie) VALUES
    ('Bombay', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Felino')),
    ('Siamés', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Felino')),
    ('Persa', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Felino')),
    ('Maine Coon', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Felino')),
    ('Bengalí', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Felino')),
    ('Mestizo', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Felino')),

    ('Labrador Retriever', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Canino')),
    ('Pastor Alemán', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Canino')),
    ('Golden Retriever', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Canino')),
    ('Bulldog Francés', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Canino')),
    ('Mestizo', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Canino')),

    ('Canario', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Ave')),
    ('Periquito Australiano', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Ave')),
    ('Agapornis', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Ave')),
    ('Cacatúa', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Ave')),

    ('Hámster Sirio', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Roedor')),
    ('Cobaya Peruana', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Roedor')),
    ('Chinchilla', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Roedor')),
    ('Ratón doméstico', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Roedor')),

    ('Holandés Enano', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Conejo')),
    ('Mini Lop', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Conejo')),
    ('Cabeza de León', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Conejo')),
    ('Rex', (SELECT IDespecie FROM especie WHERE nombre_Especie = 'Conejo'))
ON DUPLICATE KEY UPDATE nombre_Raza = VALUES(nombre_Raza);

COMMIT;
