-- VeciReport - Datos demo para portafolio
-- Ejecutar sobre una base existente despues de las migraciones.


START TRANSACTION;

INSERT INTO fraccionamientos (nombre, direccion, mapa_poligono, activo)
VALUES (
    'Fraccionamiento VeciReport',
    'Ciudad Juarez, Chihuahua',
    '[[31.741150,-106.489700],[31.742050,-106.481900],[31.738700,-106.478900],[31.734850,-106.482400],[31.735200,-106.488800]]',
    1
)
ON DUPLICATE KEY UPDATE
    direccion = VALUES(direccion),
    mapa_poligono = VALUES(mapa_poligono),
    activo = 1;

INSERT INTO usuarios (nombre, apellidos, correo, password_hash, rol, estado)
VALUES (
    'Vecino',
    'Demo',
    'vecino.demo@vecireport.com',
    '$2y$12$taBh0kiYx5MQgayCJLek9ubD2UOqOLYCaTGeCJWrxMWfNoAgFQtCK',
    'vecino',
    'activo'
)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    apellidos = VALUES(apellidos),
    password_hash = VALUES(password_hash),
    rol = 'vecino',
    estado = 'activo';

SET @fraccionamiento_id = (
    SELECT id FROM fraccionamientos WHERE nombre = 'Fraccionamiento VeciReport' LIMIT 1
);

SET @usuario_vecino_id = (
    SELECT id FROM usuarios WHERE correo = 'vecino.demo@vecireport.com' LIMIT 1
);

INSERT INTO vecinos (usuario_id, fraccionamiento_id, num_calle, num_casa, color_casa, ubicacion_x, ubicacion_y, ubicacion_lat, ubicacion_lng, comprobante_path)
VALUES (
    @usuario_vecino_id,
    @fraccionamiento_id,
    'Calle Roble',
    '24',
    'Blanco',
    50.000,
    50.000,
    31.7387000,
    -106.4849000,
    'uploads/comprobantes/demo-comprobante.pdf'
)
ON DUPLICATE KEY UPDATE
    fraccionamiento_id = VALUES(fraccionamiento_id),
    num_calle = VALUES(num_calle),
    num_casa = VALUES(num_casa),
    color_casa = VALUES(color_casa),
    ubicacion_x = VALUES(ubicacion_x),
    ubicacion_y = VALUES(ubicacion_y),
    ubicacion_lat = VALUES(ubicacion_lat),
    ubicacion_lng = VALUES(ubicacion_lng);

SET @vecino_id = (
    SELECT id FROM vecinos WHERE usuario_id = @usuario_vecino_id LIMIT 1
);

DELETE a
FROM asignaciones a
JOIN reportes r ON r.id = a.reporte_id
WHERE r.vecino_id = @vecino_id;

DELETE FROM reportes WHERE vecino_id = @vecino_id;

SET @trabajador_en_proceso = (
    SELECT id FROM trabajadores WHERE especialidad = 'electricista' ORDER BY id LIMIT 1
);

SET @trabajador_atendido = (
    SELECT id FROM trabajadores WHERE especialidad = 'plomero' ORDER BY id LIMIT 1
);

INSERT INTO reportes (vecino_id, trabajador_id, categoria, tipo, descripcion, color_casa, num_casa, foto_path, estado)
VALUES (
    @vecino_id,
    NULL,
    'agua',
    'colectivo',
    'Fuga de agua constante cerca de la entrada principal del fraccionamiento.',
    'Blanco',
    '24',
    NULL,
    'pendiente'
);

INSERT INTO reportes (vecino_id, trabajador_id, categoria, tipo, descripcion, color_casa, num_casa, foto_path, estado)
VALUES (
    @vecino_id,
    @trabajador_en_proceso,
    'luz',
    'individual',
    'Lampara exterior intermitente durante la noche frente a la casa.',
    'Blanco',
    '24',
    NULL,
    'en_proceso'
);

SET @reporte_en_proceso = LAST_INSERT_ID();

INSERT INTO reportes (vecino_id, trabajador_id, categoria, tipo, descripcion, color_casa, num_casa, foto_path, estado)
VALUES (
    @vecino_id,
    @trabajador_atendido,
    'otros',
    'colectivo',
    'Bache atendido en vialidad interna despues de varios reportes vecinos.',
    'Blanco',
    '24',
    NULL,
    'atendido'
);

UPDATE trabajadores SET disponibilidad = 'ocupado' WHERE id = @trabajador_en_proceso;
UPDATE trabajadores SET disponibilidad = 'disponible' WHERE id = @trabajador_atendido;

INSERT INTO asignaciones (reporte_id, trabajador_id, admin_id, notas)
SELECT @reporte_en_proceso, @trabajador_en_proceso, id, 'Asignacion demo para mostrar el flujo en proceso'
FROM usuarios
WHERE correo = 'admin@vecireport.com'
LIMIT 1;

INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
SELECT id, 'sistema', 'Datos demo cargados para portafolio', '127.0.0.1'
FROM usuarios
WHERE correo = 'admin@vecireport.com'
LIMIT 1;

COMMIT;
