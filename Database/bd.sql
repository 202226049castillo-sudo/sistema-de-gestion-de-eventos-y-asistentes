/* CIBERSEGURIDAD: Creación de la base de datos con cifrado de tablas.
   Nota: Requiere que el motor soporte cifrado (InnoDB tablespace encryption).
*/
CREATE DATABASE IF NOT EXISTS institucion_eventos;
USE institucion_eventos;

-- -----------------------------------------------------
-- 1. TABLAS CON CIFRADO Y PRIVACIDAD
-- -----------------------------------------------------

CREATE TABLE eventos (
    id_evento INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    descripcion TEXT,
    fecha DATE NOT NULL,
    hora TIME NOT NULL,
    lugar VARCHAR(150) NOT NULL,
    cupo_maximo INT NOT NULL,
    estado ENUM('Activo', 'Cancelado', 'Finalizado') DEFAULT 'Activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB ENCRYPTION='Y'; /* CIBERSEGURIDAD: Cifrado en reposo */

CREATE TABLE asistentes (
    id_asistente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    /* CIBERSEGURIDAD: Almacenamiento de correo cifrado para cumplimiento de leyes de privacidad */
    correo VARBINARY(255) UNIQUE NOT NULL, 
    telefono VARBINARY(100),
    institucion VARCHAR(150),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB ENCRYPTION='Y';

CREATE TABLE asistencia (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_asistente INT NOT NULL,
    fecha_asistencia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Registrado', 'Asistió', 'No asistió') DEFAULT 'Registrado',
    
    /* CIBERSEGURIDAD: Restricciones de integridad para evitar registros huérfanos */
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_asistente) REFERENCES asistentes(id_asistente) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE (id_evento, id_asistente)
) ENGINE=InnoDB ENCRYPTION='Y';

-- -----------------------------------------------------
-- 2. CONTROL DE ACCESO (PRIVILEGIO MÍNIMO)
-- -----------------------------------------------------

/* CIBERSEGURIDAD: Eliminación de accesos anónimos y privilegios excesivos */
DELETE FROM mysql.user WHERE User='';
FLUSH PRIVILEGES;

-- Crear Rol de Administrador (Gestión total pero sin acceso a la raíz del sistema)
CREATE ROLE 'admin_rol';
GRANT ALL PRIVILEGES ON institucion_eventos.* TO 'admin_rol';

-- Crear Rol de Operador (Privilegio Mínimo: solo insertar y ver, no borrar eventos)
CREATE ROLE 'operador_rol';
GRANT SELECT, INSERT, UPDATE ON institucion_eventos.asistencia TO 'operador_rol';
GRANT SELECT, INSERT ON institucion_eventos.asistentes TO 'operador_rol';
GRANT SELECT ON institucion_eventos.eventos TO 'operador_rol';

-- -----------------------------------------------------
-- 3. USUARIOS ESPECÍFICOS CON CONEXIÓN SEGURA
-- -----------------------------------------------------

/* CIBERSEGURIDAD: Creación de usuarios con REQUIRE SSL para forzar cifrado en tránsito */
CREATE USER 'admin_user'@'localhost' IDENTIFIED BY 'Password_Seguro_123!' REQUIRE SSL;
GRANT 'admin_rol' TO 'admin_user'@'localhost';
SET DEFAULT ROLE 'admin_rol' FOR 'admin_user'@'localhost';

CREATE USER 'app_service'@'%' IDENTIFIED BY 'App_Safe_Pass_2026' REQUIRE SSL;
GRANT 'operador_rol' TO 'app_service'@'%';
SET DEFAULT ROLE 'operador_rol' FOR 'app_service'@'%';

-- -----------------------------------------------------
-- 4. PROCEDIMIENTOS DE CIFRADO (AES-256)
-- -----------------------------------------------------

/* CIBERSEGURIDAD: Función para insertar datos sensibles usando AES_ENCRYPT */
DELIMITER //
CREATE PROCEDURE insertar_asistente_seguro(
    IN p_nombre VARCHAR(100), 
    IN p_apellido VARCHAR(100), 
    IN p_correo VARCHAR(150), 
    IN p_tel VARCHAR(20),
    IN p_inst VARCHAR(150),
    IN p_key VARCHAR(32) -- Clave de cifrado simétrico
)
BEGIN
    INSERT INTO asistentes (nombre, apellido, correo, telefono, institucion)
    VALUES (
        p_nombre, 
        p_apellido, 
        AES_ENCRYPT(p_correo, p_key), -- Cifrado AES del correo
        AES_ENCRYPT(p_tel, p_key),    -- Cifrado AES del teléfono
        p_inst
    );
END //
DELIMITER ;
