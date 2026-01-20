-- Crear base de datos
CREATE DATABASE IF NOT EXISTS eventos_masivos;
USE eventos_masivos;

-- Tabla de eventos
CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    fecha DATE NOT NULL,
    ubicacion VARCHAR(255) NOT NULL,
    descripcion TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de asistentes
CREATE TABLE asistentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    documento VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100),
    telefono VARCHAR(20),
    codigo VARCHAR(50) NOT NULL UNIQUE,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de asistencias
CREATE TABLE asistencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    asistente_id INT NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (asistente_id) REFERENCES asistentes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asistencia (evento_id, asistente_id)
);

-- Insertar datos de ejemplo
INSERT INTO eventos (nombre, fecha, ubicacion, descripcion) VALUES
('Conferencia de Tecnología 2023', '2023-12-15', 'Centro de Convenciones Principal', 'Conferencia anual sobre las últimas tendencias tecnológicas'),
('Workshop de Marketing Digital', '2023-12-20', 'Auditorio Norte', 'Taller práctico de marketing digital para emprendedores');

INSERT INTO asistentes (nombre, documento, email, telefono, codigo) VALUES
('Juan Pérez', '12345678', 'juan@email.com', '555-1234', 'ASIS-ABC123'),
('María García', '87654321', 'maria@email.com', '555-5678', 'ASIS-DEF456');

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_eventos_fecha ON eventos(fecha);
CREATE INDEX idx_asistentes_documento ON asistentes(documento);
CREATE INDEX idx_asistencias_fecha ON asistencias(fecha_hora);

-- Crear usuario para la aplicación (ajusta la contraseña según tus necesidades)
CREATE USER IF NOT EXISTS 'app_eventos'@'localhost' IDENTIFIED BY 'password_seguro';
GRANT SELECT, INSERT, UPDATE ON eventos_masivos.* TO 'app_eventos'@'localhost';
FLUSH PRIVILEGES;