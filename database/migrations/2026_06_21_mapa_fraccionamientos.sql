-- VeciReport - migracion incremental para mapa y validacion por fraccionamiento
-- Ejecutar sobre una base existente despues de la migracion de fraccionamientos.

ALTER TABLE fraccionamientos
    ADD COLUMN mapa_poligono TEXT DEFAULT NULL AFTER direccion;

UPDATE fraccionamientos
SET mapa_poligono = '[[12,18],[88,16],[94,52],[80,88],[20,84],[8,48]]'
WHERE mapa_poligono IS NULL;

ALTER TABLE vecinos
    ADD COLUMN ubicacion_x DECIMAL(6,3) DEFAULT NULL AFTER color_casa,
    ADD COLUMN ubicacion_y DECIMAL(6,3) DEFAULT NULL AFTER ubicacion_x;

UPDATE vecinos
SET ubicacion_x = 50.000,
    ubicacion_y = 50.000
WHERE ubicacion_x IS NULL
   OR ubicacion_y IS NULL;
