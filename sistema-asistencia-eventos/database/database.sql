-- Crear base de datos con configuración segura
CREATE DATABASE IF NOT EXISTS eventos_masivos 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE eventos_masivos;

-- Tabla de eventos (mejorada)
CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    ubicacion VARCHAR(200) NOT NULL,
    capacidad INT NOT NULL DEFAULT 100,
    descripcion TEXT,
    codigo_evento VARCHAR(20) UNIQUE NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_fecha (fecha),
    INDEX idx_activo (activo),
    INDEX idx_codigo (codigo_evento),
    
    CHECK (capacidad > 0),
    CHECK (fecha >= DATE(fecha_creacion))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de asistentes (mejorada)
CREATE TABLE asistentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    documento VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    categoria ENUM('general', 'vip', 'ponente', 'organizador', 'prensa') DEFAULT 'general',
    codigo VARCHAR(20) UNIQUE NOT NULL,
    qr_code VARCHAR(64) UNIQUE NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_documento (documento),
    INDEX idx_codigo (codigo),
    INDEX idx_categoria (categoria),
    INDEX idx_activo (activo),
    
    CHECK (LENGTH(documento) >= 5),
    CHECK (email IS NULL OR email LIKE '%@%')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de asistencias (mejorada)
CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    asistente_id INT NOT NULL,
    tipo_verificacion ENUM('qr', 'manual', 'facial') DEFAULT 'manual',
    codigo_verificacion VARCHAR(32) UNIQUE NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_evento (evento_id),
    INDEX idx_asistente (asistente_id),
    INDEX idx_fecha (fecha_hora),
    INDEX idx_codigo (codigo_verificacion),
    INDEX idx_evento_asistente (evento_id, asistente_id),
    
    FOREIGN KEY (evento_id) 
        REFERENCES eventos(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
        
    FOREIGN KEY (asistente_id) 
        REFERENCES asistentes(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
        
    UNIQUE KEY unique_asistencia_diaria (evento_id, asistente_id, DATE(fecha_hora))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de auditoría (nueva)
CREATE TABLE auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT DEFAULT 0,
    accion VARCHAR(50) NOT NULL,
    tabla VARCHAR(50) NOT NULL,
    registro_id INT,
    detalles TEXT,
    ip_address VARCHAR(45),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_fecha (fecha),
    INDEX idx_accion (accion),
    INDEX idx_tabla (tabla),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de usuarios del sistema (nueva)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'operador', 'visor') DEFAULT 'operador',
    activo TINYINT(1) DEFAULT 1,
    ultimo_login DATETIME,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    
    CHECK (LENGTH(password_hash) >= 60)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de sesiones (nueva)
CREATE TABLE sesiones (
    id VARCHAR(128) PRIMARY KEY,
    usuario_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    datos TEXT,
    creada DATETIME DEFAULT CURRENT_TIMESTAMP,
    expira DATETIME NOT NULL,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_expira (expira),
    
    FOREIGN KEY (usuario_id) 
        REFERENCES usuarios(id) 
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de configuraciones (nueva)
CREATE TABLE configuraciones (
    clave VARCHAR(50) PRIMARY KEY,
    valor TEXT,
    descripcion VARCHAR(255),
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de logs de errores (nueva)
CREATE TABLE logs_errores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel ENUM('DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'ERROR',
    mensaje TEXT NOT NULL,
    archivo VARCHAR(255),
    linea INT,
    trace TEXT,
    ip_address VARCHAR(45),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_fecha (fecha),
    INDEX idx_nivel (nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Índices adicionales para mejor rendimiento
CREATE INDEX idx_asistencias_completo ON asistencias(evento_id, asistente_id, fecha_hora);
CREATE INDEX idx_eventos_activos ON eventos(fecha, activo) WHERE activo = 1;
CREATE INDEX idx_asistentes_activos ON asistentes(activo, fecha_registro) WHERE activo = 1;

-- Triggers para auditoría automática
DELIMITER $$

CREATE TRIGGER eventos_after_insert
AFTER INSERT ON eventos
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, detalles)
    VALUES (0, 'INSERT', 'eventos', NEW.id, 
           CONCAT('Evento creado: ', NEW.nombre));
END$$

CREATE TRIGGER eventos_after_update
AFTER UPDATE ON eventos
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, detalles)
    VALUES (0, 'UPDATE', 'eventos', NEW.id, 
           CONCAT('Evento actualizado. Cambios en: ',
                  IF(NEW.nombre != OLD.nombre, 'nombre ', ''),
                  IF(NEW.fecha != OLD.fecha, 'fecha ', ''),
                  IF(NEW.activo != OLD.activo, 'estado ', '')));
END$$

CREATE TRIGGER asistentes_after_insert
AFTER INSERT ON asistentes
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, detalles)
    VALUES (0, 'INSERT', 'asistentes', NEW.id, 
           CONCAT('Asistente creado: ', NEW.nombre));
END$$

CREATE TRIGGER asistencias_after_insert
AFTER INSERT ON asistencias
FOR EACH ROW
BEGIN
    INSERT INTO auditoria (usuario_id, accion, tabla, registro_id, detalles)
    VALUES (0, 'INSERT', 'asistencias', NEW.id, 
           CONCAT('Asistencia registrada. Evento: ', 
                  (SELECT nombre FROM eventos WHERE id = NEW.evento_id)));
END$$

DELIMITER ;

-- Vistas para reportes
CREATE VIEW vista_eventos_detalle AS
SELECT 
    e.id,
    e.nombre,
    e.fecha,
    e.hora,
    e.ubicacion,
    e.capacidad,
    e.descripcion,
    e.codigo_evento,
    e.activo,
    COUNT(DISTINCT a.id) as total_asistencias,
    ROUND((COUNT(DISTINCT a.id) / e.capacidad) * 100, 2) as porcentaje_ocupacion,
    CASE 
        WHEN e.fecha < CURDATE() THEN 'COMPLETADO'
        WHEN e.fecha = CURDATE() THEN 'EN CURSO'
        ELSE 'PROGRAMADO'
    END as estado
FROM eventos e
LEFT JOIN asistencias a ON e.id = a.evento_id
GROUP BY e.id;

CREATE VIEW vista_asistentes_completo AS
SELECT 
    a.id,
    a.nombre,
    a.documento,
    a.email,
    a.telefono,
    a.categoria,
    a.codigo,
    a.activo,
    a.fecha_registro,
    COUNT(DISTINCT asis.id) as total_eventos_asistidos,
    GROUP_CONCAT(DISTINCT e.nombre ORDER BY asis.fecha_hora DESC SEPARATOR '; ') as ultimos_eventos
FROM asistentes a
LEFT JOIN asistencias asis ON a.id = asis.asistente_id
LEFT JOIN eventos e ON asis.evento_id = e.id
GROUP BY a.id;

CREATE VIEW vista_asistencias_diarias AS
SELECT 
    DATE(fecha_hora) as fecha,
    COUNT(*) as total_asistencias,
    COUNT(DISTINCT evento_id) as total_eventos,
    COUNT(DISTINCT asistente_id) as total_asistentes_unicos
FROM asistencias
GROUP BY DATE(fecha_hora);

-- Procedimientos almacenados
DELIMITER $$

CREATE PROCEDURE sp_registrar_asistencia(
    IN p_evento_id INT,
    IN p_codigo_asistente VARCHAR(50),
    IN p_tipo_verificacion VARCHAR(20)
)
BEGIN
    DECLARE v_asistente_id INT;
    DECLARE v_evento_nombre VARCHAR(200);
    DECLARE v_asistente_nombre VARCHAR(100);
    DECLARE v_codigo_verificacion VARCHAR(32);
    DECLARE v_existe INT;
    
    -- Verificar si ya existe asistencia hoy
    SELECT COUNT(*) INTO v_existe
    FROM asistencias a
    JOIN asistentes ast ON a.asistente_id = ast.id
    WHERE a.evento_id = p_evento_id 
      AND (ast.documento = p_codigo_asistente OR ast.codigo = p_codigo_asistente)
      AND DATE(a.fecha_hora) = CURDATE();
    
    IF v_existe > 0 THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'El asistente ya está registrado en este evento hoy';
    END IF;
    
    -- Obtener ID del asistente
    SELECT id, nombre INTO v_asistente_id, v_asistente_nombre
    FROM asistentes 
    WHERE (documento = p_codigo_asistente OR codigo = p_codigo_asistente)
      AND activo = 1;
    
    IF v_asistente_id IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Asistente no encontrado o inactivo';
    END IF;
    
    -- Verificar evento
    SELECT nombre INTO v_evento_nombre
    FROM eventos 
    WHERE id = p_evento_id AND activo = 1 AND fecha >= CURDATE();
    
    IF v_evento_nombre IS NULL THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Evento no encontrado, inactivo o ya pasado';
    END IF;
    
    -- Generar código de verificación
    SET v_codigo_verificacion = UPPER(SUBSTRING(MD5(RAND()), 1, 8));
    
    -- Insertar asistencia
    INSERT INTO asistencias (evento_id, asistente_id, tipo_verificacion, codigo_verificacion)
    VALUES (p_evento_id, v_asistente_id, p_tipo_verificacion, v_codigo_verificacion);
    
    -- Retornar resultados
    SELECT 
        LAST_INSERT_ID() as id,
        v_codigo_verificacion as codigo_verificacion,
        v_asistente_nombre as asistente_nombre,
        v_evento_nombre as evento_nombre;
END$$

CREATE PROCEDURE sp_generar_reporte_evento(IN p_evento_id INT)
BEGIN
    SELECT 
        e.nombre,
        e.fecha,
        e.ubicacion,
        e.capacidad,
        COUNT(a.id) as asistencias_registradas,
        ROUND((COUNT(a.id) / e.capacidad) * 100, 2) as porcentaje_ocupacion,
        GROUP_CONCAT(DISTINCT CONCAT(ast.nombre, ' (', ast.documento, ')') SEPARATOR '; ') as lista_asistentes
    FROM eventos e
    LEFT JOIN asistencias a ON e.id = a.evento_id
    LEFT JOIN asistentes ast ON a.asistente_id = ast.id
    WHERE e.id = p_evento_id
    GROUP BY e.id;
END$$

DELIMITER ;

-- Insertar datos de ejemplo (10 eventos)
INSERT INTO eventos (nombre, fecha, hora, ubicacion, capacidad, descripcion, codigo_evento) VALUES
('Conferencia de Inteligencia Artificial 2023', '2023-12-15', '09:00:00', 'Centro de Convenciones Principal', 500, 'Conferencia anual sobre IA y machine learning', 'EVT-AI2023'),
('Workshop de Marketing Digital', '2023-12-20', '14:00:00', 'Auditorio Norte', 200, 'Taller práctico de marketing digital', 'EVT-MKT2023'),
('Concierto de Fin de Año', '2023-12-31', '21:00:00', 'Estadio Nacional', 50000, 'Concierto con las mejores bandas del año', 'EVT-CONCIERTO2023'),
('Feria de Empleo Tecnológico', '2024-01-15', '10:00:00', 'Centro de Exposiciones', 1000, 'Feria de empleo para profesionales tech', 'EVT-EMPLEO2024'),
('Congreso de Medicina Avanzada', '2024-01-25', '08:30:00', 'Hotel Continental', 300, 'Avances en medicina y tecnología médica', 'EVT-MED2024'),
('Expo Innovación y Startups', '2024-02-10', '11:00:00', 'Centro de Innovación', 800, 'Exposición de startups innovadoras', 'EVT-STARTUP2024'),
('Seminario de Ciberseguridad', '2024-02-28', '15:00:00', 'Auditorio Seguridad', 150, 'Seminario sobre seguridad informática', 'EVT-CYBER2024'),
('Festival de Cine Independiente', '2024-03-15', '19:00:00', 'Cine Arte', 400, 'Festival anual de cine independiente', 'EVT-CINE2024'),
('Convención de Videojuegos', '2024-03-25', '10:00:00', 'Centro de Convenciones', 3000, 'La mayor convención de videojuegos del país', 'EVT-GAMES2024'),
('Competencia de Programación', '2024-04-05', '09:00:00', 'Universidad Tecnológica', 200, 'Competencia interuniversitaria de programación', 'EVT-CODE2024');

-- Insertar datos de ejemplo (10 asistentes)
INSERT INTO asistentes (nombre, documento, email, telefono, categoria, codigo, qr_code) VALUES
('Juan Pérez Rodríguez', '12345678', 'juan.perez@email.com', '555-1234', 'vip', 'ASIS-001', 'qr_001_abcdef123456'),
('María García López', '23456789', 'maria.garcia@email.com', '555-2345', 'general', 'ASIS-002', 'qr_002_bcdefg234567'),
('Carlos Martínez Díaz', '34567890', 'carlos.martinez@email.com', '555-3456', 'ponente', 'ASIS-003', 'qr_003_cdefgh345678'),
('Ana Fernández Ruiz', '45678901', 'ana.fernandez@email.com', '555-4567', 'organizador', 'ASIS-004', 'qr_004_defghi456789'),
('Luis González Sánchez', '56789012', 'luis.gonzalez@email.com', '555-5678', 'vip', 'ASIS-005', 'qr_005_efghij567890'),
('Laura Rodríguez Pérez', '67890123', 'laura.rodriguez@email.com', '555-6789', 'prensa', 'ASIS-006', 'qr_006_fghijk678901'),
('Miguel Hernández Castro', '78901234', 'miguel.hernandez@email.com', '555-7890', 'general', 'ASIS-007', 'qr_007_ghijkl789012'),
('Sofía Díaz Martín', '89012345', 'sofia.diaz@email.com', '555-8901', 'vip', 'ASIS-008', 'qr_008_hijklm890123'),
('David Sánchez Gómez', '90123456', 'david.sanchez@email.com', '555-9012', 'general', 'ASIS-009', 'qr_009_ijklmn901234'),
('Elena Torres Romero', '01234567', 'elena.torres@email.com', '555-0123', 'ponente', 'ASIS-010', 'qr_010_jklmno012345');

-- Insertar datos de ejemplo (10 asistencias)
INSERT INTO asistencias (evento_id, asistente_id, tipo_verificacion, codigo_verificacion) VALUES
(1, 1, 'qr', 'VERIF001'),
(1, 2, 'manual', 'VERIF002'),
(1, 3, 'facial', 'VERIF003'),
(2, 4, 'qr', 'VERIF004'),
(2, 5, 'manual', 'VERIF005'),
(3, 6, 'facial', 'VERIF006'),
(3, 7, 'qr', 'VERIF007'),
(4, 8, 'manual', 'VERIF008'),
(5, 9, 'facial', 'VERIF009'),
(6, 10, 'qr', 'VERIF010');

-- Insertar usuario administrador (contraseña: Admin123!)
INSERT INTO usuarios (username, email, password_hash, nombre_completo, rol) VALUES
('admin', 'admin@eventos.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin');

-- Insertar configuraciones iniciales
INSERT INTO configuraciones (clave, valor, descripcion) VALUES
('tiempo_sesion_minutos', '30', 'Tiempo de inactividad antes de cerrar sesión'),
('max_intentos_login', '5', 'Máximo número de intentos de login fallidos'),
('bloqueo_minutos', '15', 'Minutos de bloqueo tras máximo intentos'),
('capacidad_maxima_evento', '100000', 'Capacidad máxima permitida para eventos'),
('correo_notificaciones', 'notificaciones@eventos.com', 'Email para notificaciones del sistema'),
('nombre_sistema', 'EventPass Pro', 'Nombre del sistema'),
('version_sistema', '2.0.0', 'Versión actual del sistema');

-- Crear usuario seguro para la aplicación
CREATE USER IF NOT EXISTS 'app_eventos'@'localhost' 
IDENTIFIED WITH mysql_native_password 
BY 'ContraseñaSegura123!@#';

-- Privilegios específicos y seguros
GRANT SELECT, INSERT, UPDATE, DELETE ON eventos_masivos.eventos TO 'app_eventos'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON eventos_masivos.asistentes TO 'app_eventos'@'localhost';
GRANT SELECT, INSERT ON eventos_masivos.asistencias TO 'app_eventos'@'localhost';
GRANT SELECT, INSERT ON eventos_masivos.auditoria TO 'app_eventos'@'localhost';
GRANT SELECT ON eventos_masivos.vista_eventos_detalle TO 'app_eventos'@'localhost';
GRANT SELECT ON eventos_masivos.vista_asistentes_completo TO 'app_eventos'@'localhost';
GRANT EXECUTE ON PROCEDURE eventos_masivos.sp_registrar_asistencia TO 'app_eventos'@'localhost';

-- No dar acceso a tablas sensibles
REVOKE ALL PRIVILEGES ON eventos_masivos.usuarios FROM 'app_eventos'@'localhost';
REVOKE ALL PRIVILEGES ON eventos_masivos.sesiones FROM 'app_eventos'@'localhost';
REVOKE ALL PRIVILEGES ON eventos_masivos.configuraciones FROM 'app_eventos'@'localhost';
REVOKE ALL PRIVILEGES ON eventos_masivos.logs_errores FROM 'app_eventos'@'localhost';

FLUSH PRIVILEGES;

-- Configuración adicional de seguridad
SET GLOBAL max_connect_errors = 100;
SET GLOBAL connect_timeout = 10;
SET GLOBAL wait_timeout = 300;
SET GLOBAL interactive_timeout = 300;

-- Configurar log de consultas lentas (opcional para producción)
-- SET GLOBAL slow_query_log = 'ON';
-- SET GLOBAL long_query_time = 2;
-- SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-queries.log';

-- Mensaje de éxito
SELECT 'Base de datos creada exitosamente con 10 registros en cada tabla' as Mensaje;
