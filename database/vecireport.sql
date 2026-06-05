--  VeciReport — Esquema completo de base de datos
--  Versión: 2025-06
--
--  Uso: ejecutar completo en MySQL Workbench o desde consola:
--       mysql -u root -p < database/vecireport.sql
--
--  Datos incluidos:
--    · 1 usuario admin  (contraseña inicial: admin1234)
--    · 8 trabajadores ficticios de prueba
--
--  NO se incluyen vecinos. Los vecinos se registran desde la app.
--
--  ⚠ PRODUCCIÓN: cambiar la contraseña del admin con:
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


-- vecinos
-- Perfil extendido del vecino: domicilio y comprobante. Relación
-- 1-1 con usuarios (solo rol='vecino').
CREATE TABLE vecinos (
    id                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id        INT UNSIGNED  NOT NULL,
    num_calle         VARCHAR(100)  NOT NULL,
    num_casa          VARCHAR(20)   NOT NULL,
    color_casa        VARCHAR(50)   NOT NULL,
    comprobante_path  VARCHAR(255)  DEFAULT NULL,

    PRIMARY KEY (id),
    UNIQUE  KEY uq_vecinos_usuario (usuario_id),
    CONSTRAINT fk_vecinos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
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
-- Historial de cada vez que el admin asignó un trabajador a un
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
-- NULL para acciones de sistema sin sesión asociada.
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
-- Contraseña inicial: admin1234
-- Hash generado con: password_hash('admin1234', PASSWORD_DEFAULT)
INSERT INTO usuarios (nombre, apellidos, correo, password_hash, rol, estado)
VALUES (
    'Guardia',
    'Admin',
    'admin@vecireport.com',
    '$2y$12$Kx/E/9GFf23zuZLfJwLvweD3wEF9aQc842ch92.B1Gg6N0KRjmTlq',
    'admin',
    'activo'
);


-- Trabajadores (8 ficticios — 2 por especialidad + 2 generales)
-- Los teléfonos usan el área 656 (Ciudad Juárez).
INSERT INTO trabajadores (nombre, apellidos, especialidad, telefono, disponibilidad)
VALUES
    ('Carlos',    'Ramírez Ortiz',     'electricista', '656-100-2201', 'disponible'),
    ('Miguel',    'Flores Herrera',    'electricista', '656-100-2202', 'disponible'),
    ('Arturo',    'Mendoza Soto',      'plomero',      '656-100-2203', 'disponible'),
    ('Roberto',   'Vargas Castillo',   'plomero',      '656-100-2204', 'disponible'),
    ('Francisco', 'Torres Jiménez',    'albanil',      '656-100-2205', 'disponible'),
    ('Ernesto',   'Luna Espinoza',     'jardinero',    '656-100-2206', 'disponible'),
    ('Héctor',    'Gutiérrez Ramos',   'general',      '656-100-2207', 'disponible'),
    ('José',      'Morales Cervantes', 'general',      '656-100-2208', 'disponible');
