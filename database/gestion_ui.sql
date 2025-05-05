-- ============================================================
-- 1. Crear la base de datos
-- ============================================================

DROP DATABASE IF EXISTS gestion_ui;
CREATE DATABASE gestion_ui DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_ui;


-- ============================================================
-- 2. Tabla: ente
-- Representa los círculos, centros o grupos.
-- Se incluye un campo opcional para email de contacto (para la vista pública)
-- ============================================================

CREATE TABLE ente (
    id_ente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('CIRCULO','CENTRO','GRUPO') NOT NULL,
    escuela ENUM('Arquitectura','Ing Civil','Ing Sistemas','Ing Ambiental','Ing Industrial','UI-FIA') NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    email_contacto VARCHAR(150),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. Tabla: usuarios
-- Para la autenticación (login) y gestión de perfiles.
-- Se incluyen campos para nombre, apellido, imagen de perfil y biografía (para la vista pública)
-- ============================================================

CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('ADMIN','DOCENTE','OTRO') NOT NULL,
    email VARCHAR(150) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    imagen VARCHAR(255),       -- URL o ruta de la foto de perfil
    bio TEXT,                  -- Breve biografía para mostrar en la vista pública
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_ente INT DEFAULT NULL,
    CONSTRAINT fk_usuario_ente FOREIGN KEY (id_ente) REFERENCES ente(id_ente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. Tabla: actividades_poi
-- Registro principal de las actividades del POI, con información de planificación,
-- financiamiento único y el estado de ejecución general.
-- ============================================================

CREATE TABLE actividades_poi (
    codigo_actividad VARCHAR(50) NOT NULL PRIMARY KEY, -- Make this the primary key
    id_ente INT NOT NULL,
    nombre_actividad VARCHAR(200) NOT NULL,
    categoria ENUM('CAPACITACION', 'INVESTIGACION', 'GESTION', 'ACTIVIDAD') DEFAULT 'CAPACITACION',
    tipo_proceso VARCHAR(100),
    meta TEXT,
    fecha_inicio DATE,
    fecha_fin DATE,
    cronograma VARCHAR(255),
    monto_financiamiento DECIMAL(12,2) NOT NULL,
    estado_ejecucion ENUM('NO_INICIADA', 'EN_PROGRESO', 'FINALIZADA', 'CANCELADA') DEFAULT 'NO_INICIADA',
    prioridad VARCHAR(50) DEFAULT NULL,
    observaciones TEXT,
    porcentaje_1t DECIMAL(5,2) DEFAULT 0, -- Porcentaje del primer trimestre
    porcentaje_2t DECIMAL(5,2) DEFAULT 0, -- Porcentaje del segundo trimestre
    porcentaje_3t DECIMAL(5,2) DEFAULT 0, -- Porcentaje del tercer trimestre
    porcentaje_4t DECIMAL(5,2) DEFAULT 0, -- Porcentaje del cuarto trimestre
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_actividad_ente FOREIGN KEY (id_ente) REFERENCES ente(id_ente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. Tabla: tareas_actividad
-- Registra las tareas asociadas a una actividad y su flujo administrativo.
-- ============================================================

CREATE TABLE tareas_actividad (
    codigo_actividad VARCHAR(50) NOT NULL,
    codigo_tarea VARCHAR(50) NOT NULL, -- Identificador único de la tarea dentro de la actividad
    descripcion TEXT,
    fecha_inicio DATE,
    fecha_fin DATE,
    estado_flujo ENUM(
        'INICIO',
        'EVAL_CGYC',
        'EVAL_UIFIA',
        'EVAL_DDA',
        'EVAL_DIPLA',
        'EVAL_ABASTECIMIENTOS',
        'APROBADO',
        'RECHAZADO',
        'OBSERVADO'
    ) DEFAULT 'INICIO',
    observado_en VARCHAR(50) DEFAULT NULL, -- NUEVO CAMPO para registrar el estado donde fue observado
    PRIMARY KEY (codigo_actividad, codigo_tarea),
    CONSTRAINT fk_tarea_actividad FOREIGN KEY (codigo_actividad) REFERENCES actividades_poi(codigo_actividad) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. Tabla: actividades_poi_historial
-- Registra cada cambio en el estado administrativo o de ejecución de una actividad.
-- ============================================================

CREATE TABLE actividades_poi_historial (
    id_historial INT AUTO_INCREMENT PRIMARY KEY,
    codigo_actividad VARCHAR(50) NOT NULL,
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_anterior VARCHAR(50),
    estado_nuevo VARCHAR(50),
    estado_ejecucion_anterior VARCHAR(50),
    estado_ejecucion_nuevo VARCHAR(50),
    observaciones TEXT,
    id_usuario INT,
    CONSTRAINT fk_historial_actividad FOREIGN KEY (codigo_actividad) REFERENCES actividades_poi(codigo_actividad),
    CONSTRAINT fk_historial_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. Tabla: comunicaciones
-- Registra comunicaciones asociadas a una actividad (por codigo_actividad).
-- ============================================================

CREATE TABLE comunicaciones (
    id_comunicacion INT AUTO_INCREMENT PRIMARY KEY,
    codigo_actividad VARCHAR(50) NOT NULL,
    tipo_comunicacion ENUM('ENVIO', 'RECEPCION') NOT NULL,
    email_origen VARCHAR(150) NOT NULL,
    email_destino VARCHAR(150) NOT NULL,
    asunto VARCHAR(255),
    mensaje TEXT,
    fecha_envio DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_recepcion DATETIME NULL,
    medio ENUM('EMAIL','DOCUMENTO') DEFAULT 'EMAIL',
    comentarios TEXT,
    CONSTRAINT fk_comunicacion_actividad FOREIGN KEY (codigo_actividad) REFERENCES actividades_poi(codigo_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. Tabla: documentos
-- Almacena archivos relacionados a cada tarea (por codigo_actividad y codigo_tarea).
-- ============================================================

CREATE TABLE documentos (
    id_documento INT AUTO_INCREMENT PRIMARY KEY,
    codigo_actividad VARCHAR(50) DEFAULT NULL,
    codigo_tarea VARCHAR(50) DEFAULT NULL,
    nombre_documento VARCHAR(200) NOT NULL,
    ruta VARCHAR(255) NOT NULL,    -- Ruta o URL donde se almacena el documento
    descripcion TEXT,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documento_tarea FOREIGN KEY (codigo_actividad, codigo_tarea) REFERENCES tareas_actividad(codigo_actividad, codigo_tarea) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. Tabla: roles_miembros
-- Define los diferentes roles posibles para los integrantes de los grupos,
-- permitiendo agregar nuevos roles sin limitaciones fijas.
-- ============================================================

CREATE TABLE roles_miembros (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre_rol VARCHAR(50) NOT NULL UNIQUE, -- Ejemplo: 'MIEMBRO', 'ASESOR', 'PRESIDENTE', 'COORDINADOR', etc.
    descripcion TEXT                         -- Descripción del rol (opcional)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar algunos roles de ejemplo:
INSERT INTO roles_miembros (nombre_rol, descripcion) VALUES 
('MIEMBRO', 'Integrante regular del grupo'),
('ASESOR', 'Experto o guía del grupo'),
('PRESIDENTE', 'Responsable de un círculo'),
('COORDINADOR', 'Responsable de un grupo');


-- ============================================================
-- 10. Tabla: miembros
-- Registra los integrantes de cada grupo/ente y los asocia a un rol definido en roles_miembros.
-- Esta tabla alimenta tanto la administración interna como la vista pública.
-- ============================================================

CREATE TABLE miembros (
    id_miembro INT AUTO_INCREMENT PRIMARY KEY,
    id_ente INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    id_rol INT NOT NULL,      -- Referencia al rol asignado (FK a roles_miembros)
    fecha_ingreso DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_miembro_ente FOREIGN KEY (id_ente) REFERENCES ente(id_ente),
    CONSTRAINT fk_miembro_rol FOREIGN KEY (id_rol) REFERENCES roles_miembros(id_rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Actualizar la tabla miembros para reflejar la relación con roles específicos
-- La validación se implementará en la lógica de la aplicación, no en la base de datos.


-- ============================================================
-- 11. Tabla: requerimientos_actividad
-- Registra los requerimientos/materiales o servicios necesarios para cada actividad.
-- ============================================================

CREATE TABLE requerimientos_actividad (
    id_requerimiento INT AUTO_INCREMENT PRIMARY KEY,
    codigo_actividad VARCHAR(50) NOT NULL,
    codigo VARCHAR(50) DEFAULT NULL,         -- Código de referencia para el requerimiento (si aplica)
    detalle VARCHAR(255) NOT NULL,             -- Descripción o detalle del ítem (Ej.: "Refrigerios para el sábado...")
    cantidad INT NOT NULL,                     -- Cantidad requerida
    costo_unitario DECIMAL(12,2) NOT NULL,       -- Costo unitario
    total DECIMAL(12,2) NOT NULL,              -- Total (puede ser calculado: cantidad * costo_unitario)
    CONSTRAINT fk_requerimiento_actividad FOREIGN KEY (codigo_actividad) REFERENCES actividades_poi(codigo_actividad)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

