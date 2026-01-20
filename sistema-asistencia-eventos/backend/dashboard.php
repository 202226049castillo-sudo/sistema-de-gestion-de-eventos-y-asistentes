<?php
require_once 'conectar.php';

// Validar autenticación
validarOrigen();

$conexion = new ConexionSegura();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? 'estadisticas';
    
    switch ($accion) {
        case 'estadisticas':
            obtenerEstadisticas($conexion);
            break;
            
        case 'actividad':
            obtenerActividadReciente($conexion);
            break;
            
        default:
            enviarRespuesta(false, 'Acción no válida');
    }
}

function obtenerEstadisticas($conexion) {
    $estadisticas = [];
    
    // Total eventos activos
    $sql = "SELECT COUNT(*) as total FROM eventos WHERE activo = 1 AND fecha >= CURDATE()";
    $stmt = $conexion->ejecutarConsulta($sql);
    $estadisticas['totalEventos'] = $stmt->fetch()['total'];
    
    // Total asistentes activos
    $sql = "SELECT COUNT(*) as total FROM asistentes WHERE activo = 1";
    $stmt = $conexion->ejecutarConsulta($sql);
    $estadisticas['totalAsistentes'] = $stmt->fetch()['total'];
    
    // Asistencias hoy
    $sql = "SELECT COUNT(*) as total FROM asistencias WHERE DATE(fecha_hora) = CURDATE()";
    $stmt = $conexion->ejecutarConsulta($sql);
    $estadisticas['asistenciasHoy'] = $stmt->fetch()['total'];
    
    // Asistencias esta semana
    $sql = "SELECT COUNT(*) as total FROM asistencias 
            WHERE YEARWEEK(fecha_hora, 1) = YEARWEEK(CURDATE(), 1)";
    $stmt = $conexion->ejecutarConsulta($sql);
    $estadisticas['asistenciasSemana'] = $stmt->fetch()['total'];
    
    // Eventos próximos
    $sql = "SELECT id, nombre, fecha, ubicacion, capacidad 
            FROM eventos 
            WHERE activo = 1 AND fecha >= CURDATE()
            ORDER BY fecha ASC 
            LIMIT 5";
    $stmt = $conexion->ejecutarConsulta($sql);
    $estadisticas['proximosEventos'] = $stmt->fetchAll();
    
    // Formatear fechas de eventos
    foreach ($estadisticas['proximosEventos'] as &$evento) {
        $fecha = new DateTime($evento['fecha']);
        $evento['fecha_formateada'] = $fecha->format('d M');
        $evento['dia_semana'] = $fecha->format('D');
    }
    
    enviarRespuesta(true, 'Estadísticas obtenidas', $estadisticas);
}

function obtenerActividadReciente($conexion) {
    $sql = "SELECT 
                a.accion,
                a.tabla,
                a.detalles,
                a.fecha,
                TIMESTAMPDIFF(MINUTE, a.fecha, NOW()) as minutos_antes
            FROM auditoria a
            ORDER BY a.fecha DESC 
            LIMIT 10";
    
    $stmt = $conexion->ejecutarConsulta($sql);
    $actividades = $stmt->fetchAll();
    
    // Formatear actividades
    $formateadas = [];
    foreach ($actividades as $actividad) {
        $formateada = [
            'descripcion' => obtenerDescripcionActividad($actividad),
            'tiempo' => obtenerTiempoRelativo($actividad['minutos_antes']),
            'icono' => obtenerIconoActividad($actividad['accion'])
        ];
        $formateadas[] = $formateada;
    }
    
    enviarRespuesta(true, 'Actividad reciente', ['actividadReciente' => $formateadas]);
}

function obtenerDescripcionActividad($actividad) {
    switch ($actividad['accion']) {
        case 'REGISTRO_ASISTENCIA':
            return 'Nueva asistencia registrada';
        case 'CREAR_ASISTENTE':
            return 'Nuevo asistente creado';
        case 'CREAR_EVENTO':
            return 'Nuevo evento creado';
        case 'ACTUALIZAR_EVENTO':
            return 'Evento actualizado';
        default:
            return 'Actividad del sistema';
    }
}

function obtenerTiempoRelativo($minutos) {
    if ($minutos < 1) return 'Hace unos momentos';
    if ($minutos < 60) return "Hace {$minutos} min";
    if ($minutos < 1440) return 'Hace ' . floor($minutos / 60) . ' h';
    return 'Hace ' . floor($minutos / 1440) . ' días';
}

function obtenerIconoActividad($accion) {
    switch ($accion) {
        case 'REGISTRO_ASISTENCIA': return 'fa-user-check';
        case 'CREAR_ASISTENTE': return 'fa-user-plus';
        case 'CREAR_EVENTO': return 'fa-calendar-plus';
        case 'ACTUALIZAR_EVENTO': return 'fa-edit';
        default: return 'fa-history';
    }
}
?>
