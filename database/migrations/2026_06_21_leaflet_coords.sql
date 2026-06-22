-- VeciReport - migracion a coordenadas reales para Leaflet/OpenStreetMap
-- Ejecutar sobre una base existente despues de 2026_06_21_mapa_fraccionamientos.sql.

ALTER TABLE vecinos
    ADD COLUMN ubicacion_lat DECIMAL(10,7) DEFAULT NULL AFTER ubicacion_y,
    ADD COLUMN ubicacion_lng DECIMAL(10,7) DEFAULT NULL AFTER ubicacion_lat;

UPDATE fraccionamientos
SET mapa_poligono = '[[31.741150,-106.489700],[31.742050,-106.481900],[31.738700,-106.478900],[31.734850,-106.482400],[31.735200,-106.488800]]'
WHERE nombre = 'Fraccionamiento VeciReport'
   OR mapa_poligono IS NULL
   OR mapa_poligono LIKE '[[12,18%';

UPDATE vecinos
SET ubicacion_lat = 31.7387000,
    ubicacion_lng = -106.4849000
WHERE ubicacion_lat IS NULL
   OR ubicacion_lng IS NULL;
