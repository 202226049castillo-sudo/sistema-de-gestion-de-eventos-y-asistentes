# MANUAL TÉCNICO DE OPERACIONES, ESTRUCTURA Y CIBERSEGURIDAD

Este documento constituye la única fuente oficial de verdad para el desarrollo, despliegue y mantenimiento del sistema. Cualquier modificación en la jerarquía de archivos o en los protocolos de seguridad aquí descritos invalidará la integridad del proyecto.

---

## 📂 1. ARQUITECTURA DETALLADA DEL REPOSITORIO

Para que la lógica de persistencia en PHP, el motor de base de datos SQL y el renderizado del Frontend funcionen, el árbol de directorios debe ser el siguiente:

PROYECTO_RAIZ/
├── Carpeta "Database"
│   └── bd.sql                 # Esquema de base de datos con cifrado AES-256 y roles.
├── Carpeta "PAGINADANIEL"
│   ├── app.js                 # Lógica de cliente para el módulo alterno.
│   ├── descarga.jpg           # Recurso gráfico local.
│   ├── images(2).jpg          # Recurso gráfico local.
│   ├── index.html             # Interfaz de usuario del módulo alterno.
│   └── styles.css             # Estilos específicos del módulo alterno.
├── Carpeta "backend"
│   └── Subcarpeta "src"       # Lógica centralizada del servidor
│       ├── Subcarpeta "routes"
│       │   ├── eventos.routes.js
│       │   └── index.js
│       ├── .gitkeep
│       ├── config             # Archivos de configuración de entorno.
│       ├── controllers        # Lógica de procesamiento de datos.
│       ├── index.js           # Punto de entrada lógico del backend.
│       └── models             # Definición de estructuras de datos.
├── README.md                  # Descripción general del repositorio.
├── app.js                     # Motor JavaScript principal (Cifrado AES-GCM).
├── asistencias.json           # Almacenamiento persistente de registros.
├── asistentes.json            # Almacenamiento persistente de perfiles.
├── asistentes.php             # Endpoint para consulta de asistentes.
├── conexión.php               # Módulo de conexión y seguridad de archivos.
├── descarga.jpg               # Recurso gráfico raíz.
├── eventos.php                # Endpoint para consulta de eventos.
├── fondo.jpg                  # Recurso gráfico de interfaz.
├── guardar_asistencia.php     # Procesador de registros de asistencia.
├── guardar_asistente.php      # Procesador de nuevos perfiles de usuario.
├── guardar_evento.php         # Procesador de creación de eventos.
├── index.html                 # Punto de entrada principal del sistema.
└── styles.css                 # Definición visual global.

---

## 🛠️ 2. PROTOCOLOS DE INTEGRACIÓN POR EQUIPO

### EQUIPO BACKEND (Arquitectura PHP)
* **Gestión de Concurrencia:** La función `guardarJSON` en `conexión.php` implementa obligatoriamente el flag `LOCK_EX`. Esto garantiza que el sistema bloquee el archivo durante la escritura, evitando que peticiones simultáneas corrompan los datos JSON.
* **Mitigación de Denegación de Servicio (DoS):** En los archivos `guardar_*.php`, se ha establecido un límite estricto de 5KB para el cuerpo de la petición. Si un atacante intenta enviar datos masivos para agotar la memoria, el servidor rechazará la conexión automáticamente.
* **Blindaje de Cabeceras:** Todos los archivos PHP emiten cabeceras `nosniff` y `DENY` para impedir que el navegador interprete archivos de datos como scripts ejecutables.

### EQUIPO FRONTEND (Interfaz y Seguridad Cliente)
* **Sanitización Obligatoria:** Toda información capturada en los formularios debe pasar por un proceso de limpieza mediante expresiones regulares en `app.js` antes de ser enviada al servidor.
* **Neutralización de XSS:** Se prohíbe el uso de la propiedad `.innerHTML` para mostrar datos que provengan de los archivos JSON. Se debe utilizar únicamente `.textContent`, lo cual desactiva cualquier etiqueta script maliciosa.
* **Privacidad en Tránsito:** Los datos de identificación sensibles se cifran localmente mediante el estándar AES-GCM antes de realizar el envío POST, asegurando que la información sea ilegible incluso si el tráfico es interceptado.

### EQUIPO BASE DE DATOS (Database/ SQL)
* **Cifrado en Reposo:** El archivo `bd.sql` define el uso de `ENCRYPTION='Y'` para las tablas de InnoDB. Esto significa que los datos están cifrados a nivel de disco físico.
* **Jerarquía de Privilegios:** Se definen usuarios con permisos granulares. El acceso desde la aplicación web debe realizarse exclusivamente a través del usuario `app_service`, limitando el riesgo de inyecciones que busquen escalar privilegios.

### EQUIPO DE QA Y SOPORTE (Pruebas)
* **Validación de Persistencia:** Es crítico verificar que los archivos `.json` en la raíz tengan permisos de escritura (chmod 664 o 666 según el entorno) para que PHP pueda actualizar la información.
* **Integridad de Rutas:** El sistema depende de rutas relativas. No se deben mover los archivos `.php` fuera de la raíz, ya que esto rompería las dependencias de `require_once` y la localización de las bases de datos JSON.

---

## 🔐 3. MATRIZ DE SEGURIDAD IMPLEMENTADA

* **Seguridad en Capa de Presentación:** Uso de `textContent` y Cifrado AES-GCM. Previene ataques XSS y robo de identidad de usuario.
* **Seguridad en Capa de Aplicación (PHP):** Uso de `LOCK_EX`, límites de payload y sanitización de inputs. Previene corrupción de bases de datos y saturación de servidor.
* **Seguridad en Capa de Datos (SQL):** Cifrado AES-256 y roles de acceso limitado. Previene la filtración masiva de datos en caso de acceso no autorizado al servidor.

---

**NOTA DE CIERRE:** Este sistema ha sido diseñado para operar de forma autónoma y segura. Cualquier cambio en la estructura jerárquica descrita en el Punto 1 debe ser documentado y replicado en todos los controladores PHP para evitar errores de ejecución 404 o 500.
