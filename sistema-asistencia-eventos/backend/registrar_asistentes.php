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
        registrarAsistente($conexion);
        break;
        
    case 'GET':
        listarAsistentes($conexion);
        break;
        
    case 'PUT':
        actualizarAsistente($conexion);
        break;
        
    case 'DELETE':
        eliminarAsistente($conexion);
        break;
        
    default:
        enviarRespuesta(false, 'Método no permitido', [], 405);
}

function registrarAsistente($conexion) {
    // Sanitizar y validar entrada
    $input = $conexion->sanitizar($_POST);
    
    $reglas = [
        'nombre' => ['required', 'min:3', 'max:100'],
        'documento' => ['required', 'min:5', 'max:20'],
        'email' => ['email', 'max:100'],
        'telefono' => ['telefono', 'max:20'],
        'categoria' => ['in:general,vip,ponente,organizador,prensa']
    ];
    
    $errores = validarEntrada($input, $reglas);
    
    if (!empty($errores)) {
        enviarRespuesta(false, 'Errores de validación', ['errores' => $errores], 400);
    }
    
    // Verificar si el asistente ya existe
    $sql = "SELECT id FROM asistentes WHERE documento = :documento";
    $stmt = $conexion->ejecutarConsulta($sql, [':documento' => $input['documento']]);
    
    if ($stmt->fetch()) {
        enviarRespuesta(false, 'El asistente con este documento ya está registrado');
    }
    
    // Generar código único seguro
    $codigo = 'ASIS-' . strtoupper(bin2hex(random_bytes(4)));
    $qr_code = bin2hex(random_bytes(16));
    
    // Insertar nuevo asistente
    $sql = "INSERT INTO asistentes 
            (nombre, documento, email, telefono, categoria, 
             codigo, qr_code, activo, fecha_registro) 
            VALUES 
            (:nombre, :documento, :email, :telefono, :categoria,
             :codigo, :qr_code, 1, NOW())";
    
    $params = [
        ':nombre' => $input['nombre'],
        ':documento' => $input['documento'],
        ':email' => $input['email'] ?? null,
        ':telefono' => $input['telefono'] ?? null,
        ':categoria' => $input['categoria'] ?? 'general',
        ':codigo' => $codigo,
        ':qr_code' => $qr_code
    ];
    
    try {
        $stmt = $conexion->ejecutarConsulta($sql, $params);
        $asistente_id = $conexion->getConexion()->lastInsertId();
        
        // Registrar en auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'CREAR_ASISTENTE', 'asistentes', :id, :detalles, :ip, NOW())";
        
        $detalles = json_encode([
            'nombre' => $input['nombre'],
            'documento' => $input['documento'],
            'categoria' => $params[':categoria']
        ]);
        
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $asistente_id,
            ':detalles' => $detalles,
            ':ip' => $ip
        ]);
        
        $respuesta = [
            'id' => $asistente_id,
            'codigo' => $codigo,
            'qr_code' => $qr_code,
            'fecha_registro' => date('Y-m-d H:i:s')
        ];
        
        enviarRespuesta(true, 'Asistente registrado exitosamente', $respuesta);
        
    } catch (Exception $e) {
        enviarRespuesta(false, 'Error al registrar el asistente', [], 500);
    }
}

function listarAsistentes($conexion) {
    // Verificar autenticación
    if (!verificarAutenticacion()) {
        enviarRespuesta(false, 'No autorizado', [], 401);
    }
    
    // Parámetros de filtrado
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $por_pagina = min(100, max(10, (int)($_GET['por_pagina'] ?? 20)));
    $offset = ($pagina - 1) * $por_pagina;
    
    $filtros = [];
    $params = [];
    
    // Construir filtros
    if (isset($_GET['busqueda']) && !empty($_GET['busqueda'])) {
        $busqueda = $conexion->sanitizar($_GET['busqueda']);
        $filtros[] = "(nombre LIKE :busqueda OR documento LIKE :busqueda OR codigo LIKE :busqueda)";
        $params[':busqueda'] = "%{$busqueda}%";
    }
    
    if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
        $categoria = $conexion->sanitizar($_GET['categoria']);
        $filtros[] = "categoria = :categoria";
        $params[':categoria'] = $categoria;
    }
    
    if (isset($_GET['activo']) && is_numeric($_GET['activo'])) {
        $filtros[] = "activo = :activo";
        $params[':activo'] = (int)$_GET['activo'];
    }
    
    $where = $filtros ? 'WHERE ' . implode(' AND ', $filtros) : '';
    
    // Obtener total de registros
    $sql_total = "SELECT COUNT(*) as total FROM asistentes {$where}";
    $stmt_total = $conexion->ejecutarConsulta($sql_total, $params);
    $total = $stmt_total->fetch()['total'];
    $paginas = ceil($total / $por_pagina);
    
    // Obtener asistentes
    $sql = "SELECT id, nombre, documento, email, telefono, 
                   categoria, codigo, fecha_registro, activo,
                   (SELECT COUNT(*) FROM asistencias WHERE asistente_id = asistentes.id) as total_asistencias
            FROM asistentes 
            {$where}
            ORDER BY fecha_registro DESC 
            LIMIT :offset, :limit";
    
    $params[':offset'] = $offset;
    $params[':limit'] = $por_pagina;
    
    $stmt = $conexion->ejecutarConsulta($sql, $params);
    $asistentes = $stmt->fetchAll();
    
    // Sanitizar datos de salida
    foreach ($asistentes as &$asistente) {
        $asistente = array_map('htmlspecialchars', $asistente);
    }
    
    $respuesta = [
        'asistentes' => $asistentes,
        'paginacion' => [
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total' => $total,
            'paginas' => $paginas
        ]
    ];
    
    enviarRespuesta(true, 'Asistentes obtenidos', $respuesta);
}

function actualizarAsistente($conexion) {
    // Para PUT, necesitamos leer el input
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
    
    // Verificar que el asistente existe
    $sql = "SELECT id FROM asistentes WHERE id = :id";
    $stmt = $conexion->ejecutarConsulta($sql, [':id' => $id]);
    
    if (!$stmt->fetch()) {
        enviarRespuesta(false, 'Asistente no encontrado');
    }
    
    // Construir consulta de actualización dinámica
    $campos = [];
    $params = [':id' => $id];
    
    $campos_permitidos = ['nombre', 'email', 'telefono', 'categoria', 'activo'];
    
    foreach ($campos_permitidos as $campo) {
        if (isset($input[$campo])) {
            $campos[] = "{$campo} = :{$campo}";
            $params[":{$campo}"] = $input[$campo];
        }
    }
    
    if (empty($campos)) {
        enviarRespuesta(false, 'No hay campos para actualizar');
    }
    
    $sql = "UPDATE asistentes SET " . implode(', ', $campos) . " WHERE id = :id";
    
    try {
        $conexion->ejecutarConsulta($sql, $params);
        
        // Auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'ACTUALIZAR_ASISTENTE', 'asistentes', :id, :detalles, :ip, NOW())";
        
        $detalles = json_encode($input);
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $id,
            ':detalles' => $detalles,
            ':ip' => $ip
        ]);
        
        enviarRespuesta(true, 'Asistente actualizado exitosamente');
        
    } catch (Exception $e) {
        enviarRespuesta(false, 'Error al actualizar el asistente', [], 500);
    }
}

function eliminarAsistente($conexion) {
    // Para DELETE, necesitamos leer el input
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
    
    // Verificar que el asistente existe y no tiene asistencias
    $sql = "SELECT a.id, 
                   (SELECT COUNT(*) FROM asistencias WHERE asistente_id = a.id) as total_asistencias
            FROM asistentes a 
            WHERE a.id = :id";
    
    $stmt = $conexion->ejecutarConsulta($sql, [':id' => $id]);
    $asistente = $stmt->fetch();
    
    if (!$asistente) {
        enviarRespuesta(false, 'Asistente no encontrado');
    }
    
    if ($asistente['total_asistencias'] > 0) {
        // En lugar de eliminar, desactivar
        $sql = "UPDATE asistentes SET activo = 0 WHERE id = :id";
        $conexion->ejecutarConsulta($sql, [':id' => $id]);
        
        // Auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'DESACTIVAR_ASISTENTE', 'asistentes', :id, 'Desactivado por tener asistencias', :ip, NOW())";
        
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $id,
            ':ip' => $ip
        ]);
        
        enviarRespuesta(true, 'Asistente desactivado (tiene asistencias registradas)');
    } else {
        // Eliminar completamente
        $sql = "DELETE FROM asistentes WHERE id = :id";
        $conexion->ejecutarConsulta($sql, [':id' => $id]);
        
        // Auditoría
        $ip = $_SERVER['REMOTE_ADDR'];
        $sql_audit = "INSERT INTO auditoria 
                      (usuario_id, accion, tabla, registro_id, detalles, ip_address, fecha)
                      VALUES (0, 'ELIMINAR_ASISTENTE', 'asistentes', :id, 'Eliminado completamente', :ip, NOW())";
        
        $conexion->ejecutarConsulta($sql_audit, [
            ':id' => $id,
            ':ip' => $ip
        ]);
        
        enviarRespuesta(true, 'Asistente eliminado exitosamente');
    }
}

function verificarAutenticacion() {
    // Implementación básica de autenticación
    $api_key = $_SERVER['HTTP_X_API_KEY'] ?? '';
    
    // En producción, usaría una tabla de API keys o JWT
    $valid_keys = ['clave_secreta_api_12345']; // Mover a variables de entorno
    
    return in_array($api_key, $valid_keys);
}
?>
