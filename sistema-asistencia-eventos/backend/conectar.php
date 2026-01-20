<?php
// Configuración de seguridad
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src \'self\' \'unsafe-inline\' https://cdnjs.cloudflare.com; img-src \'self\' data: https:; font-src \'self\' https://cdnjs.cloudflare.com;');

// Iniciar sesión para manejo de CSRF
session_start();

// Configuración de tiempo de sesión (30 minutos)
ini_set('session.gc_maxlifetime', 1800);
session_set_cookie_params(1800);

// Prevenir session fixation
if (empty($_SESSION['initiated'])) {
    session_regenerate_id();
    $_SESSION['initiated'] = true;
}

// Generar y validar token CSRF
function generarTokenCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validarTokenCSRF($token) {
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

// Validar origen de la solicitud
function validarOrigen() {
    $allowed_origins = ['http://localhost', 'https://tu-dominio.com'];
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header('Access-Control-Allow-Origin: null');
    }
    
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Para solicitudes preflight
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        exit(0);
    }
}

// Clase de conexión segura
class ConexionSegura {
    private $host;
    private $usuario;
    private $password;
    private $base_datos;
    private $conexion;
    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_PERSISTENT => false
    ];

    public function __construct() {
        // Configuración desde variables de entorno (recomendado)
        // o desde un archivo de configuración seguro
        $this->host = getenv('DB_HOST') ?: "localhost";
        $this->usuario = getenv('DB_USER') ?: "usuario_seguro";
        $this->password = getenv('DB_PASS') ?: "contraseña_compleja_123";
        $this->base_datos = getenv('DB_NAME') ?: "eventos_masivos";
        
        $this->conectar();
    }

    private function conectar() {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->base_datos};charset=utf8mb4";
            $this->conexion = new PDO($dsn, $this->usuario, $this->password, $this->options);
            
            // Configuraciones adicionales de seguridad
            $this->conexion->exec("SET time_zone = '+00:00'");
            $this->conexion->exec("SET sql_mode = 'STRICT_ALL_TABLES'");
            
        } catch (PDOException $e) {
            // Log seguro del error (sin mostrar detalles al usuario)
            $this->logError($e->getMessage());
            
            // Mensaje genérico al usuario
            die(json_encode([
                'success' => false,
                'message' => 'Error de conexión con la base de datos'
            ]));
        }
    }

    public function getConexion() {
        return $this->conexion;
    }

    // Método para consultas preparadas seguras
    public function ejecutarConsulta($sql, $params = []) {
        try {
            $stmt = $this->conexion->prepare($sql);
            
            // Bind parameters de forma segura
            foreach ($params as $key => $value) {
                $tipo = is_int($value) ? PDO::PARAM_INT : 
                       (is_bool($value) ? PDO::PARAM_BOOL : 
                       (is_null($value) ? PDO::PARAM_NULL : PDO::PARAM_STR));
                $stmt->bindValue($key, $value, $tipo);
            }
            
            $stmt->execute();
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logError($e->getMessage() . " - SQL: " . $sql);
            throw $e;
        }
    }

    // Sanitizar entrada
    public function sanitizar($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizar'], $input);
        }
        
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $input;
    }

    // Validar email
    public function validarEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Validar teléfono
    public function validarTelefono($telefono) {
        return preg_match('/^[\d\s\-\+\(\)]{8,20}$/', $telefono);
    }

    // Validar fecha
    public function validarFecha($fecha) {
        $d = DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }

    // Log seguro de errores
    private function logError($mensaje) {
        $log_file = __DIR__ . '/../logs/error_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $log_entry = "[$timestamp] [IP: $ip] [UA: $user_agent] $mensaje\n";
        
        // Escribir en archivo de log
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // También puedes enviar una notificación por email para errores críticos
        if (strpos($mensaje, 'CRITICAL') !== false) {
            $this->notificarError($mensaje);
        }
    }

    private function notificarError($mensaje) {
        // Implementar notificación por email o servicio externo
        $to = getenv('ADMIN_EMAIL') ?: 'admin@tudominio.com';
        $subject = 'Error crítico en sistema de eventos';
        $message = "Error detectado: " . substr($mensaje, 0, 500);
        $headers = 'From: sistema@tudominio.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();
        
        @mail($to, $subject, $message, $headers);
    }

    // Prevenir inyección SQL - validar consultas
    public function validarConsultaSQL($sql, $tipo = 'SELECT') {
        $sql = strtoupper(trim($sql));
        $tipoEsperado = strtoupper($tipo);
        
        // Verificar que la consulta comience con el tipo esperado
        if (strpos($sql, $tipoEsperado) !== 0) {
            throw new Exception("Consulta no válida para tipo: $tipoEsperado");
        }
        
        // Lista de palabras peligrosas
        $palabras_peligrosas = ['DROP', 'TRUNCATE', 'DELETE FROM', 'UPDATE', 'INSERT INTO'];
        
        foreach ($palabras_peligrosas as $palabra) {
            if (strpos($sql, $palabra) !== false && strpos($sql, $palabra) < 10) {
                throw new Exception("Consulta potencialmente peligrosa detectada");
            }
        }
        
        return true;
    }

    // Cerrar conexión
    public function cerrarConexion() {
        $this->conexion = null;
    }

    // Destructor
    public function __destruct() {
        $this->cerrarConexion();
    }
}

// Función para obtener conexión
function obtenerConexion() {
    static $conexion = null;
    
    if ($conexion === null) {
        $conexion = new ConexionSegura();
    }
    
    return $conexion->getConexion();
}

// Función para respuestas JSON seguras
function enviarRespuesta($success, $message, $data = [], $codigo = 200) {
    validarOrigen();
    
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    
    // Validar y sanitizar datos de salida
    $data_sanitizada = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $data_sanitizada[$key] = array_map('htmlspecialchars', $value);
        } else {
            $data_sanitizada[$key] = htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
        }
    }
    
    echo json_encode([
        'success' => (bool)$success,
        'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        'data' => $data_sanitizada,
        'timestamp' => time(),
        'csrf_token' => generarTokenCSRF()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    exit();
}

// Función para validar entrada de formulario
function validarEntrada($input, $reglas = []) {
    $conexion = new ConexionSegura();
    $errores = [];
    
    foreach ($reglas as $campo => $regla) {
        $valor = $input[$campo] ?? '';
        
        if (in_array('required', $regla) && empty($valor)) {
            $errores[$campo] = "El campo $campo es requerido";
            continue;
        }
        
        if (!empty($valor)) {
            if (in_array('email', $regla) && !$conexion->validarEmail($valor)) {
                $errores[$campo] = "Email inválido";
            }
            
            if (in_array('telefono', $regla) && !$conexion->validarTelefono($valor)) {
                $errores[$campo] = "Teléfono inválido";
            }
            
            if (in_array('fecha', $regla) && !$conexion->validarFecha($valor)) {
                $errores[$campo] = "Fecha inválida";
            }
            
            if (in_array('numero', $regla) && !is_numeric($valor)) {
                $errores[$campo] = "Debe ser un número";
            }
            
            // Validar longitud
            foreach ($regla as $r) {
                if (strpos($r, 'max:') === 0) {
                    $max = (int)str_replace('max:', '', $r);
                    if (strlen($valor) > $max) {
                        $errores[$campo] = "Máximo $max caracteres";
                    }
                }
                
                if (strpos($r, 'min:') === 0) {
                    $min = (int)str_replace('min:', '', $r);
                    if (strlen($valor) < $min) {
                        $errores[$campo] = "Mínimo $min caracteres";
                    }
                }
            }
        }
    }
    
    return $errores;
}

// Función para prevenir ataques de fuerza bruta
function prevenirFuerzaBruta($intentos = 5, $tiempo_bloqueo = 300) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $archivo_bloqueo = __DIR__ . "/../cache/bloqueos/{$ip}.json";
    
    if (file_exists($archivo_bloqueo)) {
        $datos = json_decode(file_get_contents($archivo_bloqueo), true);
        
        if (time() - $datos['timestamp'] < $tiempo_bloqueo) {
            if ($datos['intentos'] >= $intentos) {
                enviarRespuesta(false, "Demasiados intentos. Por favor espere " . 
                    ceil(($tiempo_bloqueo - (time() - $datos['timestamp'])) / 60) . " minutos.", [], 429);
            }
        } else {
            // Reiniciar contador después del tiempo de bloqueo
            unlink($archivo_bloqueo);
        }
    }
}

function registrarIntentoFuerzaBruta($intentos = 5, $tiempo_bloqueo = 300) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $directorio = __DIR__ . "/../cache/bloqueos/";
    
    if (!file_exists($directorio)) {
        mkdir($directorio, 0755, true);
    }
    
    $archivo_bloqueo = $directorio . "{$ip}.json";
    
    if (file_exists($archivo_bloqueo)) {
        $datos = json_decode(file_get_contents($archivo_bloqueo), true);
        $datos['intentos']++;
        $datos['timestamp'] = time();
    } else {
        $datos = [
            'intentos' => 1,
            'timestamp' => time()
        ];
    }
    
    file_put_contents($archivo_bloqueo, json_encode($datos), LOCK_EX);
}

// Función para validar archivos subidos
function validarArchivo($archivo, $tipos_permitidos = ['image/jpeg', 'image/png'], $tamano_maximo = 2097152) {
    $errores = [];
    
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $errores[] = "Error al subir el archivo";
        return $errores;
    }
    
    // Validar tipo MIME
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $tipos_permitidos)) {
        $errores[] = "Tipo de archivo no permitido";
    }
    
    // Validar tamaño
    if ($archivo['size'] > $tamano_maximo) {
        $errores[] = "Archivo demasiado grande. Máximo: " . ($tamano_maximo / 1048576) . "MB";
    }
    
    // Validar nombre del archivo
    $nombre = basename($archivo['name']);
    if (preg_match('/[^\w\.\-]/', $nombre)) {
        $errores[] = "Nombre de archivo inválido";
    }
    
    return $errores;
}

// Función para limpiar cache de intentos antiguos
function limpiarCacheIntentos($horas = 24) {
    $directorio = __DIR__ . "/../cache/bloqueos/";
    if (!file_exists($directorio)) return;
    
    $archivos = glob($directorio . "*.json");
    $limite = time() - ($horas * 3600);
    
    foreach ($archivos as $archivo) {
        if (filemtime($archivo) < $limite) {
            unlink($archivo);
        }
    }
}

// Ejecutar limpieza periódica (1% de probabilidad)
if (rand(1, 100) === 1) {
    limpiarCacheIntentos();
}
?>
