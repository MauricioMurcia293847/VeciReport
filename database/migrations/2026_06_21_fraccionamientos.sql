-- VeciReport - migracion incremental para fraccionamientos
-- Ejecutar sobre una base existente despues de respaldar datos.

CREATE TABLE IF NOT EXISTS fraccionamientos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(120) NOT NULL,
    direccion   VARCHAR(180) DEFAULT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_fraccionamientos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO fraccionamientos (nombre, direccion, activo)
SELECT 'Fraccionamiento VeciReport', 'Ciudad Juarez, Chihuahua', 1
WHERE NOT EXISTS (
    SELECT 1 FROM fraccionamientos WHERE nombre = 'Fraccionamiento VeciReport'
);

SET @fraccionamiento_id := (
    SELECT id FROM fraccionamientos
    WHERE nombre = 'Fraccionamiento VeciReport'
    LIMIT 1
);

ALTER TABLE vecinos
    ADD COLUMN fraccionamiento_id INT UNSIGNED NULL AFTER usuario_id;

UPDATE vecinos
SET fraccionamiento_id = @fraccionamiento_id
WHERE fraccionamiento_id IS NULL;

ALTER TABLE vecinos
    MODIFY fraccionamiento_id INT UNSIGNED NOT NULL;

ALTER TABLE vecinos
    ADD CONSTRAINT fk_vecinos_fraccionamiento
    FOREIGN KEY (fraccionamiento_id) REFERENCES fraccionamientos (id)
    ON DELETE CASCADE ON UPDATE CASCADE;
