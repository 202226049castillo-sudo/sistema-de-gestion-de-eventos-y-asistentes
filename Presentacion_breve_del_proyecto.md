# Tomorrowland llegará a Tulum con CORE  
## Sistema Web Integral de Gestión de Eventos y Asistencias

---

## 1. Introducción

El proyecto **Tomorrowland llegará a Tulum con CORE** consiste en el desarrollo de un sistema web integral orientado a la administración, control y consulta de eventos y asistentes, simulando un entorno real de organización de eventos de gran escala, tales como festivales musicales, experiencias recreativas y eventos corporativos.

El sistema fue concebido con un enfoque **académico–práctico**, priorizando la claridad en la estructura del código, la correcta separación de responsabilidades y una interfaz gráfica intuitiva que facilite la interacción del usuario.  
Esta documentación funciona como el **documento oficial del proyecto**, permitiendo comprender su funcionamiento, arquitectura y alcance sin necesidad de referencias externas.

---

## Integrantes del Proyecto

Este sistema fue desarrollado de manera colaborativa por los siguientes integrantes:

- Renata Castillo García  
- Diana Karen Velázquez Valle  
- Arturo Omar Camero Rivera  
- Edgar Atzin Aguilar Torres  
- José Gabriel Juárez Maya  
- Brandon Axel Sánchez Flores  
- Gerardo Yael Martínez Espinosa  
- Brandon Fuentes Serrano  


## 2. Objetivos del Proyecto

### 2.1 Objetivo General

Desarrollar un sistema web que permita la gestión eficiente de eventos y asistentes, así como el registro y consulta de asistencias, mediante una interfaz clara, organizada y funcional.

### 2.2 Objetivos Específicos

- Implementar la creación dinámica de eventos.
- Permitir el registro de asistentes dentro del sistema.
- Asociar asistentes a eventos específicos.
- Consultar listas de asistencia por evento.
- Aplicar buenas prácticas básicas de desarrollo web.
- Simular un sistema real de gestión de eventos con fines académicos.

---


## 3. Estructura del Proyecto

```text
sistema-asistencia-eventos
│
├── backend/
│   ├── conectar.php                 # Conexión a la base de datos
│   ├── registrar_eventos.php        # Registro de eventos
│   ├── registrar_asistentes.php     # Registro de asistentes
│   └── registrar_asistencias.php    # Registro de asistencias
│
├── database/
│   └── database.sql                 # Script de creación de la base de datos
│
├── frontend/
│   ├── index.html                   # Interfaz principal del sistema
│   ├── script.js                    # Lógica del lado del cliente
│   └── styles.css                   # Estilos del sistema
│
└── README.md                        # Documentación oficial del proyecto

