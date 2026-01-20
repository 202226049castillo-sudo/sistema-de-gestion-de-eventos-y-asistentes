<?php
require_once 'conectar.php';

// Validar token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validarTokenCSRF($csrf_token)) {
        enviarRespuesta(false, 'Token de seguridad inválido', [], 403);
    }
}

// Validar origen de la solicitud
validarOrigen();

// Prevenir ataques de fuerza bruta
prevenirFuerzaBruta();

// Obtener conexión segura
$conexion = new ConexionSegura();

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y validar entrada
    $input = $conexion->sanitizar($_POST);
    
    $reglas = [
        'evento_id' => ['required', 'numero'],
        'codigo_asistente' => ['required', 'min:3', 'max:50'],
        'tipo_verificacion' => ['required', 'in:qr,manual,facial']
    ];
    
    $errores = validarEntrada($input, $reglas);
    
    if (!empty($errores)) {
        registrarIntentoFuerzaBruta();
        enviarRespuesta(false, 'Errores de validación', ['errores' => $errores], 400);
    }
    
    // Obtener datos validados
    $evento_id = (int)$input['evento_id'];
    $codigo_asistente = $input['codigo_asistente'];
    $tipo_verificacion = $input['tipo_verificacion'];
    $timestamp = time();
    
    try {
        // Verificar si el evento existe y está activo
        $sql = "SELECT id, nombre, fecha, capacidad FROM eventos 
                WHERE id = :evento_id AND fecha >= CURDATE() 
                AND activo = 1";
        
        $stmt = $conexion->ejecutarConsulta($sql, [':evento_id' => $evento_id]);
        $evento = $stmt->fetch();
        
        if (!$evento) {
            registrarIntentoFuerzaBruta();
            enviarRespuesta(false, 'Evento no encontrado o inactivo');
        }
        
        // Verificar capacidad del evento
        $sql = "SELECT COUNT(*) as total FROM asistencias 
                WHERE evento_id = :evento_id";
        $stmt = $conexion->ejecutarConsulta($sql, [':evento_id' => $evento_id]);
        $asistencias_evento = $stmt->fetch();
        
        if ($asistencias_evento['total'] >= $evento['capacidad']) {
            enviarRespuesta(false, 'Evento ha alcanzado su capacidad máxima');
        }
        
        // Verificar si el asistente existe
        $sql = "SELECT id, nombre, documento, email, codigo, activo 
                FROM asistentes 
                WHERE (documento = :codigo OR codigo = :codigo) 
                AND activo = 1";
        
        $stmt = $conexion->ejecutarConsulta($sql, [':codigo' => $codigo_asistente]);
        $asistente = $stmt->fetch();
        
        if (!$asistente) {
            registrarIntentoFuerzaBruta();
            enviarRespuesta(false, 'Asistente no encontrado o inactivo');
        }
        
        $asistente_id = $asistente['id'];
        
        // Verificar si ya está registrado en este evento
        $sql = "SELECT id, fecha_hora FROM asistencias 
                WHERE evento_id = :evento_id AND asistente_id = :asistente_id 
                AND DATE(fecha_hora) = CURDATE()";
        
        $stmt = $conexion->ejecutarConsulta($sql, [
            ':evento_id' => $evento_id,
            ':asistente_id' => $asistente_id
        ]);
        
        if ($stmt->fetch()) {
            enviarRespuesta(false, 'El asistente ya está registrado en este evento hoy');
        }
        
        // Registrar la asistencia con información adicional
        $codigo_verificacion = bin2hex(random_bytes(8));
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $sql = "INSERT INTO asistencias 
                (evento_id, asistente_id, tipo_verificacion, 
                 codigo_verificacion, ip_address, user_agent, fecha_hora) 
                VALUES 
                (:evento_id, :asistente_id, :tipo_verificacion, 
                 :codigo_verificacion, :ip_address, :user_agent, NOW())";
        
        $params = [
            ':evento_id' => $evento_id,
            ':asistente_id' => $asistente_id,
            ':tipo_verificacion' => $tipo_verificacion,
            ':codigo_verificacion' => $codigo_verificacion,
            ':ip_address' => $ip_address,
            ':user_agent' => substr($user_agent, 0, 255)
        ];
        
        $stmt = $conexion->ejecutarConsulta($sql, $params);
        $asistencia_id = $conexion->getConexion()->lastInsertId();
        
        // Registrar en log de auditoría
        $sql = "INSERT INTO auditoria 
                (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha) 
                VALUES 
                (0, 'REGISTRO_ASISTENCIA', 'asistencias', :registro_id, 
                 :detalles, :ip_address, NOW())";
        
        $detalles = json_encode([
            'evento_id' => $evento_id,
            'asistente_id' => $asistente_id,
            'tipo_verificacion' => $tipo_verificacion,
            'codigo_verificacion' => $codigo_verificacion
        ]);
        
        $conexion->ejecutarConsulta($sql, [
            ':registro_id' => $asistencia_id,
            ':detalles' => $detalles,
            ':ip_address' => $ip_address
        ]);
        
        // Respuesta exitosa con datos
        $respuesta = [
            'id' => $asistencia_id,
            'codigo_verificacion' => $codigo_verificacion,
            'nombre' => $asistente['nombre'],
            'documento' => $asistente['documento'],
            'evento' => $evento['nombre'],
            'fecha_hora' => date('Y-m-d H:i:s'),
            'timestamp' => $timestamp
        ];
        
        // Limpiar intentos exitosos
        $archivo_bloqueo = __DIR__ . "/../cache/bloqueos/{$ip_address}.json";
        if (file_exists($archivo_bloqueo)) {
            unlink($archivo_bloqueo);
        }
        
        enviarRespuesta(true, 'Asistencia registrada exitosamente', $respuesta);
        
    } catch (Exception $e) {
        registrarIntentoFuerzaBruta();
        enviarRespuesta(false, 'Error al procesar la solicitud', [], 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Listar asistencias recientes (con autenticación)
    if (!isset($_GET['accion'])) {
        enviarRespuesta(false, 'Acción no especificada');
    }
    
    $accion = $conexion->sanitizar($_GET['accion']);
    
    switch ($accion) {
        case 'recientes':
            // Verificar autenticación
            if (!verificarAutenticacion()) {
                enviarRespuesta(false, 'No autorizado', [], 401);
            }
            
            $limite = (int)($_GET['limite'] ?? 50);
            $limite = min($limite, 100); // Límite máximo
            
            $sql = "SELECT a.id, e.nombre as evento, asis.nombre as asistente, 
                           a.tipo_verificacion, a.fecha_hora, a.codigo_verificacion
                    FROM asistencias a
                    JOIN eventos e ON a.evento_id = e.id
                    JOIN asistentes asis ON a.asistente_id = asis.id
                    ORDER BY a.fecha_hora DESC 
                    LIMIT :limite";
            
            $stmt = $conexion->ejecutarConsulta($sql, [':limite' => $limite]);
            $asistencias = $stmt->fetchAll();
            
            enviarRespuesta(true, 'Asistencias obtenidas', ['asistencias' => $asistencias]);
            break;
            
        case 'verificar':
            // Verificar asistencia por código
            $codigo = $conexion->sanitizar($_GET['codigo'] ?? '');
            
            if (empty($codigo)) {
                enviarRespuesta(false, 'Código requerido');
            }
            
            $sql = "SELECT a.id, a.fecha_hora, e.nombre as evento, 
                           asis.nombre as asistente, asis.documento,
                           a.codigo_verificacion, a.tipo_verificacion
                    FROM asistencias a
                    JOIN eventos e ON a.evento_id = e.id
                    JOIN asistentes asis ON a.asistente_id = asis.id
                    WHERE a.codigo_verificacion = :codigo 
                    OR asis.codigo = :codigo";
            
            $stmt = $conexion->ejecutarConsulta($sql, [':codigo' => $codigo]);
            $verificacion = $stmt->fetch();
            
            if ($verificacion) {
                enviarRespuesta(true, 'Asistencia verificada', $verificacion);
            } else {
                enviarRespuesta(false, 'Código no encontrado');
            }
            break;
            
        default:
            enviarRespuesta(false, 'Acción no válida');
    }
} else {
    enviarRespuesta(false, 'Método no permitido', [], 405);
}

// Función para verificar autenticación (simplificada)
function verificarAutenticacion() {
    // En una implementación real, verificarías tokens JWT o sesiones
    $auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (strpos($auth_header, 'Bearer ') === 0) {
        $token = str_replace('Bearer ', '', $auth_header);
        // Verificar token aquí
        return true; // Simplificado para el ejemplo
    }
    
    return false;
}
?>
