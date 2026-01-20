<?php
require_once 'conectar.php';

// Validar token CSRF para métodos POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validarTokenCSRF($csrf_token)) {
        enviarRespuesta(false, 'Token de seguridad inválido', [], 403);
    }
}

// Validar origen
validarOrigen();

// Obtener conexión segura
$conexion = new ConexionSegura();

// Manejar diferentes métodos
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        registrarEvento($conexion);
        break;
        
    case 'GET':
        listarEventos($conexion);
        break;
        
    case 'PUT':
        actualizarEvento($conexion);
        break;
        
    case 'DELETE':
        eliminarEvento($conexion);
        break;
        
    default:
        enviarRespuesta(false, 'Método no permitido', [], 405);
}

function registrarEvento($conexion) {
    // Sanitizar y validar entrada
    $input = $conexion->sanitizar($_POST);
    
    $reglas = [
        'nombre_evento' => ['required', 'min:5', 'max:200'],
        'fecha_evento' => ['required', 'fecha'],
        'hora_evento' => ['required'],
        'ubicacion' => ['required', 'min:5', 'max:200'],
        'capacidad' => ['required', 'numero', 'min:1', 'max:100000'],
        'descripcion' => ['max:1000']
    ];
    
    $errores = validarEntrada($input, $reglas);
    
    // Validar fecha futura
    if (empty($errores['fecha_evento'])) {
        $fecha_evento = new DateTime($input['fecha_evento']);
        $hoy = new DateTime();
        
        if ($fecha_evento < $hoy) {
            $errores['fecha_evento'] = 'La fecha debe ser futura';
        }
    }
    
    if (!empty($errores)) {
        enviarRespuesta(false, 'Errores de validación', ['errores' => $errores], 400);
    }
    
    // Generar código único para el evento
    $codigo_evento = 'EVT-' . strtoupper(bin2hex(random_bytes(4)));
    
    // Insertar nuevo evento
    $sql = "INSERT INTO eventos 
            (nombre, fecha, hora, ubicacion, capacidad, descripcion, 
             codigo_evento, activo, fecha_creacion) 
            VALUES 
            (:nombre, :fecha, :hora, :ubicacion, :capacidad, :descripcion,
             :codigo_evento, 1, NOW())";
    
    $params = [
        ':nombre' => $input['nombre_evento'],
        ':fecha' => $input['fecha_evento'],
        ':hora' => $input['hora_evento'],
        ':ubicacion' => $input['ubicacion'],
        ':capacidad' => (int)$input['capacidad'],
        ':descripcion' => $input['descripcion'] ?? null,
        ':codigo_evento' => $codigo_evento
    ];
    
    try {
        $stmt = $conexion->ejecutarConsulta($sql, $params);
        $evento_id = $conexion->getConexion()->lastInsertId();
        
        // Registrar en auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'CREAR_EVENTO', 'eventos', :id, :detalles, :ip, NOW())";
        
        $detalles = json_encode([
            'nombre' => $input['nombre_evento'],
            'fecha' => $input['fecha_evento'],
            'capacidad' => $input['capacidad']
        ]);
        
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $evento_id,
            ':detalles' => $detalles,
            ':ip' => $ip
        ]);
        
        $respuesta = [
            'id' => $evento_id,
            'codigo_evento' => $codigo_evento,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ];
        
        enviarRespuesta(true, 'Evento creado exitosamente', $respuesta);
        
    } catch (Exception $e) {
        enviarRespuesta(false, 'Error al crear el evento', [], 500);
    }
}

function listarEventos($conexion) {
    // No requiere autenticación para listar
    $accion = $_GET['accion'] ?? 'todos';
    
    switch ($accion) {
        case 'listar':
            // Listar eventos para select
            $sql = "SELECT id, nombre, DATE_FORMAT(fecha, '%d/%m/%Y') as fecha, ubicacion, capacidad
                    FROM eventos 
                    WHERE activo = 1 AND fecha >= CURDATE()
                    ORDER BY fecha ASC";
            
            $stmt = $conexion->ejecutarConsulta($sql);
            $eventos = $stmt->fetchAll();
            
            // Sanitizar salida
            foreach ($eventos as &$evento) {
                $evento = array_map('htmlspecialchars', $evento);
            }
            
            enviarRespuesta(true, 'Eventos obtenidos', ['eventos' => $eventos]);
            break;
            
        case 'proximos':
            // Eventos próximos para dashboard
            $limite = min(10, (int)($_GET['limite'] ?? 5));
            
            $sql = "SELECT id, nombre, fecha, ubicacion, capacidad,
                           (SELECT COUNT(*) FROM asistencias WHERE evento_id = eventos.id) as asistentes_registrados
                    FROM eventos 
                    WHERE activo = 1 AND fecha >= CURDATE()
                    ORDER BY fecha ASC 
                    LIMIT :limite";
            
            $stmt = $conexion->ejecutarConsulta($sql, [':limite' => $limite]);
            $eventos = $stmt->fetchAll();
            
            enviarRespuesta(true, 'Eventos próximos obtenidos', ['eventos' => $eventos]);
            break;
            
        case 'detalle':
            // Detalle de un evento específico
            $evento_id = (int)($_GET['id'] ?? 0);
            
            if ($evento_id <= 0) {
                enviarRespuesta(false, 'ID de evento inválido');
            }
            
            $sql = "SELECT e.*, 
                           COUNT(a.id) as total_asistencias,
                           (SELECT COUNT(*) FROM asistentes WHERE activo = 1) as total_asistentes
                    FROM eventos e
                    LEFT JOIN asistencias a ON e.id = a.evento_id
                    WHERE e.id = :id
                    GROUP BY e.id";
            
            $stmt = $conexion->ejecutarConsulta($sql, [':id' => $evento_id]);
            $evento = $stmt->fetch();
            
            if (!$evento) {
                enviarRespuesta(false, 'Evento no encontrado');
            }
            
            // Sanitizar datos sensibles
            $evento_sanitizado = array_map('htmlspecialchars', $evento);
            
            enviarRespuesta(true, 'Detalle del evento', $evento_sanitizado);
            break;
            
        default:
            // Listar todos los eventos con paginación
            $pagina = max(1, (int)($_GET['pagina'] ?? 1));
            $por_pagina = min(50, max(10, (int)($_GET['por_pagina'] ?? 20)));
            $offset = ($pagina - 1) * $por_pagina;
            
            $filtros = ['activo = 1'];
            $params = [];
            
            if (isset($_GET['estado'])) {
                $estado = $conexion->sanitizar($_GET['estado']);
                if ($estado === 'pasados') {
                    $filtros[] = "fecha < CURDATE()";
                } elseif ($estado === 'futuros') {
                    $filtros[] = "fecha >= CURDATE()";
                }
            }
            
            $where = 'WHERE ' . implode(' AND ', $filtros);
            
            // Obtener total
            $sql_total = "SELECT COUNT(*) as total FROM eventos {$where}";
            $stmt_total = $conexion->ejecutarConsulta($sql_total, $params);
            $total = $stmt_total->fetch()['total'];
            $paginas = ceil($total / $por_pagina);
            
            // Obtener eventos
            $sql = "SELECT e.*, 
                           COUNT(a.id) as asistencias,
                           ROUND((COUNT(a.id) / e.capacidad) * 100, 2) as porcentaje_ocupacion
                    FROM eventos e
                    LEFT JOIN asistencias a ON e.id = a.evento_id
                    {$where}
                    GROUP BY e.id
                    ORDER BY e.fecha DESC 
                    LIMIT :offset, :limit";
            
            $params[':offset'] = $offset;
            $params[':limit'] = $por_pagina;
            
            $stmt = $conexion->ejecutarConsulta($sql, $params);
            $eventos = $stmt->fetchAll();
            
            // Sanitizar
            foreach ($eventos as &$evento) {
                $evento = array_map('htmlspecialchars', $evento);
            }
            
            $respuesta = [
                'eventos' => $eventos,
                'paginacion' => [
                    'pagina' => $pagina,
                    'por_pagina' => $por_pagina,
                    'total' => $total,
                    'paginas' => $paginas
                ]
            ];
            
            enviarRespuesta(true, 'Eventos obtenidos', $respuesta);
    }
}

function actualizarEvento($conexion) {
    // Para PUT, leer input
    $input_data = file_get_contents('php://input');
    $input = json_decode($input_data, true);
    
    if (!$input) {
        enviarRespuesta(false, 'Datos inválidos', [], 400);
    }
    
    // Validar token CSRF
    if (!isset($input['csrf_token']) || !validarTokenCSRF($input['csrf_token'])) {
        enviarRespuesta(false, 'Token de seguridad inválido', [], 403);
    }
    
    // Validar ID
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        enviarRespuesta(false, 'ID inválido', [], 400);
    }
    
    $id = (int)$input['id'];
    $input = $conexion->sanitizar($input);
    
    // Verificar que el evento existe
    $sql = "SELECT id FROM eventos WHERE id = :id";
    $stmt = $conexion->ejecutarConsulta($sql, [':id' => $id]);
    
    if (!$stmt->fetch()) {
        enviarRespuesta(false, 'Evento no encontrado');
    }
    
    // Construir consulta de actualización
    $campos = [];
    $params = [':id' => $id];
    
    $campos_permitidos = ['nombre', 'fecha', 'hora', 'ubicacion', 'capacidad', 'descripcion', 'activo'];
    
    foreach ($campos_permitidos as $campo) {
        if (isset($input[$campo])) {
            $campos[] = "{$campo} = :{$campo}";
            $params[":{$campo}"] = $input[$campo];
        }
    }
    
    if (empty($campos)) {
        enviarRespuesta(false, 'No hay campos para actualizar');
    }
    
    $sql = "UPDATE eventos SET " . implode(', ', $campos) . " WHERE id = :id";
    
    try {
        $conexion->ejecutarConsulta($sql, $params);
        
        // Auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'ACTUALIZAR_EVENTO', 'eventos', :id, :detalles, :ip, NOW())";
        
        $detalles = json_encode($input);
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $id,
            ':detalles' => $detalles,
            ':ip' => $ip
        ]);
        
        enviarRespuesta(true, 'Evento actualizado exitosamente');
        
    } catch (Exception $e) {
        enviarRespuesta(false, 'Error al actualizar el evento', [], 500);
    }
}

function eliminarEvento($conexion) {
    // Para DELETE, leer input
    $input_data = file_get_contents('php://input');
    $input = json_decode($input_data, true);
    
    if (!$input) {
        enviarRespuesta(false, 'Datos inválidos', [], 400);
    }
    
    // Validar token CSRF
    if (!isset($input['csrf_token']) || !validarTokenCSRF($input['csrf_token'])) {
        enviarRespuesta(false, 'Token de seguridad inválido', [], 403);
    }
    
    // Validar ID
    if (!isset($input['id']) || !is_numeric($input['id'])) {
        enviarRespuesta(false, 'ID inválido', [], 400);
    }
    
    $id = (int)$input['id'];
    
    // Verificar que el evento existe y no tiene asistencias
    $sql = "SELECT e.id, 
                   (SELECT COUNT(*) FROM asistencias WHERE evento_id = e.id) as total_asistencias
            FROM eventos e 
            WHERE e.id = :id";
    
    $stmt = $conexion->ejecutarConsulta($sql, [':id' => $id]);
    $evento = $stmt->fetch();
    
    if (!$evento) {
        enviarRespuesta(false, 'Evento no encontrado');
    }
    
    if ($evento['total_asistencias'] > 0) {
        // Desactivar en lugar de eliminar
        $sql = "UPDATE eventos SET activo = 0 WHERE id = :id";
        $conexion->ejecutarConsulta($sql, [':id' => $id]);
        
        // Auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'DESACTIVAR_EVENTO', 'eventos', :id, 'Desactivado por tener asistencias', :ip, NOW())";
        
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $id,
            ':ip' => $ip
        ]);
        
        enviarRespuesta(true, 'Evento desactivado (tiene asistencias registradas)');
    } else {
        // Eliminar completamente
        $sql = "DELETE FROM eventos WHERE id = :id";
        $conexion->ejecutarConsulta($sql, [':id' => $id]);
        
        // Auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'ELIMINAR_EVENTO', 'eventos', :id, 'Eliminado completamente', :ip, NOW())";
        
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $id,
            ':ip' => $ip
        ]);
        
        enviarRespuesta(true, 'Evento eliminado exitosamente');
    }
}
?>
