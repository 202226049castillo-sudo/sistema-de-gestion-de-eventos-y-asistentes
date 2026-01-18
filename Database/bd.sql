CREATE DATABASE institucion_eventos;
USE institucion_eventos;


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
);


CREATE TABLE asistentes (
    id_asistente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    correo VARCHAR(150) UNIQUE NOT NULL,
    telefono VARCHAR(20),
    institucion VARCHAR(150),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE asistencia (
    id_asistencia INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_asistente INT NOT NULL,
    fecha_asistencia TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Registrado', 'Asistió', 'No asistió') DEFAULT 'Registrado',

    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
        ON DELETE CASCADE ON UPDATE CASCADE,

    FOREIGN KEY (id_asistente) REFERENCES asistentes(id_asistente)
        ON DELETE CASCADE ON UPDATE CASCADE,

    UNIQUE (id_evento, id_asistente)
);
