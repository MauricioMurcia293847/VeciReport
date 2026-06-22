--  VeciReport - Esquema completo de base de datos
--  Version: 2025-06
--
--  Uso: ejecutar completo en MySQL Workbench o desde consola:
--       mysql -u root -p < database/vecireport.sql
--
--  Datos incluidos:
--    - 1 usuario admin  (contrasena inicial: VeciAdmin!2026#Demo)
--    - 8 trabajadores ficticios de prueba
--
--  Tambien se incluye un vecino demo activo con reportes de ejemplo.
--
--  PRODUCCION: cambiar la contrasena del admin con:
--      UPDATE usuarios
--      SET password_hash = '<hash generado con password_hash() en PHP>'
--      WHERE correo = 'admin@vecireport.com';
--

CREATE DATABASE IF NOT EXISTS vecireport
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE vecireport;


-- Eliminar tablas existentes (orden inverso de dependencias FK)

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS bitacora;
DROP TABLE IF EXISTS asignaciones;
DROP TABLE IF EXISTS reportes;
DROP TABLE IF EXISTS vecinos;
DROP TABLE IF EXISTS trabajadores;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS fraccionamientos;

SET FOREIGN_KEY_CHECKS = 1;


-- usuarios
-- Almacena vecinos y el admin. Los vecinos quedan 'pendiente' hasta
-- que el admin los aprueba.
CREATE TABLE usuarios (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100)  NOT NULL,
    apellidos     VARCHAR(100)  NOT NULL,
    correo        VARCHAR(150)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    rol           ENUM('vecino','admin')                 NOT NULL DEFAULT 'vecino',
    estado        ENUM('pendiente','activo','bloqueado') NOT NULL DEFAULT 'pendiente',
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                         ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE  KEY uq_usuarios_correo (correo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- fraccionamientos
-- Catalogo de comunidades permitidas para registro de vecinos.
CREATE TABLE fraccionamientos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(120) NOT NULL,
    direccion   VARCHAR(180) DEFAULT NULL,
    mapa_poligono TEXT       DEFAULT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_fraccionamientos_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- vecinos
-- Perfil extendido del vecino: domicilio y comprobante. Relacion
-- 1-1 con usuarios (solo rol='vecino').
CREATE TABLE vecinos (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id        INT UNSIGNED  NOT NULL,
    fraccionamiento_id INT UNSIGNED NOT NULL,
    num_calle         VARCHAR(100)  NOT NULL,
    num_casa          VARCHAR(20)   NOT NULL,
    color_casa        VARCHAR(50)   NOT NULL,
    ubicacion_x        DECIMAL(6,3)  DEFAULT NULL,
    ubicacion_y        DECIMAL(6,3)  DEFAULT NULL,
    ubicacion_lat      DECIMAL(10,7) DEFAULT NULL,
    ubicacion_lng      DECIMAL(10,7) DEFAULT NULL,
    comprobante_path  VARCHAR(255)  DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE  KEY uq_vecinos_usuario (usuario_id),
    CONSTRAINT fk_vecinos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_vecinos_fraccionamiento
        FOREIGN KEY (fraccionamiento_id) REFERENCES fraccionamientos (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- trabajadores
-- Especialistas del fraccionamiento. No tienen cuenta en el sistema;
-- el admin los gestiona directamente en la BD.
CREATE TABLE trabajadores (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(100)  NOT NULL,
    apellidos      VARCHAR(100)  NOT NULL,
    especialidad   ENUM('electricista','plomero','albanil','jardinero','general')
                                 NOT NULL,
    telefono       VARCHAR(20)   NOT NULL,
    disponibilidad ENUM('disponible','ocupado') NOT NULL DEFAULT 'disponible',
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- reportes
-- Incidencias creadas por vecinos. El trabajador_id es NULL hasta
-- que el admin asigna un especialista.
CREATE TABLE reportes (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    vecino_id      INT UNSIGNED  NOT NULL,
    trabajador_id  INT UNSIGNED  DEFAULT NULL,
    categoria      ENUM('luz','agua','trabajadores','otros') NOT NULL,
    tipo           ENUM('individual','colectivo')            NOT NULL,
    descripcion    TEXT          NOT NULL,
    color_casa     VARCHAR(50)   NOT NULL,
    num_casa       VARCHAR(20)   NOT NULL,
    foto_path      VARCHAR(255)  DEFAULT NULL,
    estado         ENUM('pendiente','en_proceso','atendido') NOT NULL DEFAULT 'pendiente',
    created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_reportes_vecino
        FOREIGN KEY (vecino_id)     REFERENCES vecinos      (id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_reportes_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- asignaciones
-- Historial de cada vez que el admin asigno un trabajador a un
-- reporte. Permite trazabilidad completa aunque el reporte cambie.
CREATE TABLE asignaciones (
    id             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    reporte_id     INT UNSIGNED  NOT NULL,
    trabajador_id  INT UNSIGNED  NOT NULL,
    admin_id       INT UNSIGNED  NOT NULL,
    notas          TEXT          DEFAULT NULL,
    assigned_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_asignaciones_reporte
        FOREIGN KEY (reporte_id)    REFERENCES reportes     (id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_trabajador
        FOREIGN KEY (trabajador_id) REFERENCES trabajadores (id)
        ON UPDATE CASCADE,
    CONSTRAINT fk_asignaciones_admin
        FOREIGN KEY (admin_id)      REFERENCES usuarios     (id)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- bitacora
-- Registro inmutable de acciones del sistema. usuario_id puede ser
-- NULL para acciones de sistema sin sesion asociada.
CREATE TABLE bitacora (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED  DEFAULT NULL,
    tipo_accion ENUM(
        'login', 'logout', 'registro',
        'reporte_creado', 'reporte_atendido',
        'asignacion', 'vecino_aprobado', 'vecino_bloqueado',
        'sistema'
    ) NOT NULL,
    descripcion TEXT          NOT NULL,
    ip          VARCHAR(45)   NOT NULL DEFAULT '',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_bitacora_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



--  DATOS INICIALES


-- Admin
-- Contrasena inicial: VeciAdmin!2026#Demo
-- Hash generado con: password_hash('VeciAdmin!2026#Demo', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, apellidos, correo, password_hash, rol, estado)
VALUES (
    'Guardia',
    'Admin',
    'admin@vecireport.com',
    '$2y$12$.w3Wv9tRjbj.VNIH8rbXeOH3qcj8u1tvYRsnDvkhGtpAJtnv4u6CG',
    'admin',
    'activo'
);


-- Fraccionamiento demo
INSERT INTO fraccionamientos (nombre, direccion, mapa_poligono, activo)
VALUES (
    'Fraccionamiento VeciReport',
    'Ciudad Juarez, Chihuahua',
    '[[31.741150,-106.489700],[31.742050,-106.481900],[31.738700,-106.478900],[31.734850,-106.482400],[31.735200,-106.488800]]',
    1
);

-- Trabajadores (8 ficticios - 2 por especialidad + 2 generales)
-- Los telefonos usan el area 656 (Ciudad Juarez).
INSERT INTO trabajadores (nombre, apellidos, especialidad, telefono, disponibilidad)
VALUES
    ('Carlos',    'Ramirez Ortiz',     'electricista', '656-100-2201', 'disponible'),
    ('Miguel',    'Flores Herrera',    'electricista', '656-100-2202', 'disponible'),
    ('Arturo',    'Mendoza Soto',      'plomero',      '656-100-2203', 'disponible'),
    ('Roberto',   'Vargas Castillo',   'plomero',      '656-100-2204', 'disponible'),
    ('Francisco', 'Torres Jimenez',    'albanil',      '656-100-2205', 'disponible'),
    ('Ernesto',   'Luna Espinoza',     'jardinero',    '656-100-2206', 'disponible'),
    ('Hector',    'Gutierrez Ramos',   'general',      '656-100-2207', 'disponible'),
    ('Jose',      'Morales Cervantes', 'general',      '656-100-2208', 'disponible');


-- Vecino demo para portafolio
-- Password inicial: VecinoDemo!2026#Ok
INSERT INTO usuarios (nombre, apellidos, correo, password_hash, rol, estado)
VALUES (
    'Vecino',
    'Demo',
    'vecino.demo@vecireport.com',
    '$2y$12$qkdw1MXE3ySwCOuIkKNJxeMb/2yhcH06guBJ6FLD50/zni13h6xrm',
    'vecino',
    'activo'
);

INSERT INTO vecinos (usuario_id, fraccionamiento_id, num_calle, num_casa, color_casa, ubicacion_x, ubicacion_y, ubicacion_lat, ubicacion_lng, comprobante_path)
VALUES (
    (SELECT id FROM usuarios WHERE correo = 'vecino.demo@vecireport.com'),
    (SELECT id FROM fraccionamientos WHERE nombre = 'Fraccionamiento VeciReport'),
    'Calle Roble',
    '24',
    'Blanco',
    50.000,
    50.000,
    31.7387000,
    -106.4849000,
    'uploads/comprobantes/demo-comprobante.pdf'
);

INSERT INTO reportes (vecino_id, trabajador_id, categoria, tipo, descripcion, color_casa, num_casa, foto_path, estado)
VALUES
    (
        (SELECT v.id FROM vecinos v JOIN usuarios u ON u.id = v.usuario_id WHERE u.correo = 'vecino.demo@vecireport.com'),
        NULL,
        'agua',
        'colectivo',
        'Fuga de agua constante cerca de la entrada principal del fraccionamiento.',
        'Blanco',
        '24',
        NULL,
        'pendiente'
    ),
    (
        (SELECT v.id FROM vecinos v JOIN usuarios u ON u.id = v.usuario_id WHERE u.correo = 'vecino.demo@vecireport.com'),
        1,
        'luz',
        'individual',
        'Lampara exterior intermitente durante la noche frente a la casa.',
        'Blanco',
        '24',
        NULL,
        'en_proceso'
    ),
    (
        (SELECT v.id FROM vecinos v JOIN usuarios u ON u.id = v.usuario_id WHERE u.correo = 'vecino.demo@vecireport.com'),
        3,
        'otros',
        'colectivo',
        'Bache atendido en vialidad interna despues de varios reportes vecinos.',
        'Blanco',
        '24',
        NULL,
        'atendido'
    );

UPDATE trabajadores SET disponibilidad = 'ocupado' WHERE id = 1;

INSERT INTO asignaciones (reporte_id, trabajador_id, admin_id, notas)
SELECT r.id, 1, u_admin.id, 'Asignacion demo para mostrar el flujo en proceso'
FROM reportes r
JOIN vecinos v ON v.id = r.vecino_id
JOIN usuarios u_vecino ON u_vecino.id = v.usuario_id
JOIN usuarios u_admin ON u_admin.correo = 'admin@vecireport.com'
WHERE u_vecino.correo = 'vecino.demo@vecireport.com'
  AND r.estado = 'en_proceso'
LIMIT 1;

INSERT INTO bitacora (usuario_id, tipo_accion, descripcion, ip)
SELECT id, 'sistema', 'Datos demo cargados para portafolio', '127.0.0.1'
FROM usuarios
WHERE correo = 'admin@vecireport.com';

