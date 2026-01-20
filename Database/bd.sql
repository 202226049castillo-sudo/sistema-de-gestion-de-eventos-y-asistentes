<<<<<<< HEAD

CREATE DATABASE IF NOT EXISTS gestion_eventos;
USE gestion_eventos;
=======
CREATE DATABASE gestion_eventos;
USE institucion_eventos;
>>>>>>> f1853e8 (Estructura del proyecto y archivos frontend/backend iniciales)


CREATE TABLE eventos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha DATE NOT NULL,
    lugar VARCHAR(100) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE asistentes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evento_id INT NOT NULL,
    asistente_id INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
    FOREIGN KEY (asistente_id) REFERENCES asistentes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_asistencia (evento_id, asistente_id)
);


CREATE INDEX idx_eventos_fecha ON eventos(fecha);
CREATE INDEX idx_asistencia_evento ON asistencia(evento_id);
CREATE INDEX idx_asistencia_asistente ON asistencia(asistente_id);


INSERT INTO eventos (nombre, fecha, lugar) VALUES
('Conferencia de Tecnología', '2026-03-15', 'Centro de Convenciones'),
('Taller de Marketing Digital', '2026-03-20', 'Aula 101'),
('Seminario de Liderazgo', '2026-04-05', 'Auditorio Principal');

INSERT INTO asistentes (nombre) VALUES
('María García'),
('Juan Pérez'),
('Ana López'),
('Carlos Rodríguez');


INSERT INTO asistencia (evento_id, asistente_id) VALUES
(1, 1), -- María en Conferencia
(1, 2), -- Juan en Conferencia
(2, 3), -- Ana en Taller
(2, 4), -- Carlos en Taller
(3, 1), -- María en Seminario
(3, 4); -- Carlos en Seminario


CREATE VIEW vista_asistencia_eventos AS
SELECT 
    e.id AS evento_id,
    e.nombre AS evento_nombre,
    e.fecha AS evento_fecha,
    e.lugar AS evento_lugar,
    a.id AS asistente_id,
    a.nombre AS asistente_nombre,
    asi.fecha_registro AS fecha_asistencia
FROM eventos e
JOIN asistencia asi ON e.id = asi.evento_id
JOIN asistentes a ON asi.asistente_id = a.id
ORDER BY e.fecha DESC, a.nombre;


CREATE VIEW vista_estadisticas_eventos AS
SELECT 
    e.id,
    e.nombre,
    e.fecha,
    e.lugar,
    COUNT(asi.id) AS total_asistentes
FROM eventos e
LEFT JOIN asistencia asi ON e.id = asi.evento_id
GROUP BY e.id
ORDER BY e.fecha DESC;


DELIMITER //
CREATE PROCEDURE registrar_asistencia(
    IN p_evento_id INT,
    IN p_asistente_id INT
)
BEGIN
    DECLARE registro_existente INT;
    

    SELECT COUNT(*) INTO registro_existente
    FROM asistencia
    WHERE evento_id = p_evento_id AND asistente_id = p_asistente_id;
    
    IF registro_existente = 0 THEN
        INSERT INTO asistencia (evento_id, asistente_id)
        VALUES (p_evento_id, p_asistente_id);
        SELECT 'Asistencia registrada correctamente' AS mensaje;
    ELSE
        SELECT 'El asistente ya está registrado en este evento' AS mensaje;
    END IF;
END //
DELIMITER ;


DELIMITER //
CREATE TRIGGER actualizar_evento
BEFORE UPDATE ON eventos
FOR EACH ROW
BEGIN
    SET NEW.fecha_creacion = CURRENT_TIMESTAMP;
END //
DELIMITER ;
