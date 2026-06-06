<?php
// WEB_FORESTACION/php/actualizar_estado_reporte.php
header('Content-Type: application/json');

require_once 'conexion.php';

// OJO: aquí "id_autoridad" realmente es "id_usuario_que_actualiza"
$id_reporte   = intval($_POST['id_reporte']   ?? 0);
$id_usuario   = intval($_POST['id_autoridad'] ?? 0); // viene desde JS como id_autoridad
$estado       = $_POST['estado']      ?? '';
$observacion  = trim($_POST['observacion'] ?? '');

if ($id_reporte <= 0 || $id_usuario <= 0 || $estado === '') {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Datos incompletos para actualizar el reporte.'
    ]);
    exit;
}

// Solo permitiremos los estados del ENUM
$estadosValidos = ['registrado', 'en_revision', 'en_proceso', 'cerrado'];
if (!in_array($estado, $estadosValidos, true)) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Estado de reporte inválido.'
    ]);
    exit;
}

try {
    // 1) Verificar usuario que está intentando actualizar
    $sqlUser = "SELECT id_usuario, tipo_usuario, estado
                FROM usuarios
                WHERE id_usuario = :id";
    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->execute([':id' => $id_usuario]);
    $rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$rowUser || $rowUser['estado'] !== 'activo') {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'El usuario que intenta actualizar no es válido o está inactivo.'
        ]);
        exit;
    }

    $tipoUsuario = $rowUser['tipo_usuario'];

    // 2) Obtener el reporte y ver a quién está asignado
    $sqlRep = "SELECT id_reporte, id_autoridad
               FROM reportes_deforestacion
               WHERE id_reporte = :id_reporte";
    $stmtRep = $pdo->prepare($sqlRep);
    $stmtRep->execute([':id_reporte' => $id_reporte]);
    $rowRep = $stmtRep->fetch(PDO::FETCH_ASSOC);

    if (!$rowRep) {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Reporte no encontrado.'
        ]);
        exit;
    }

    $idAutoridadAsignada = intval($rowRep['id_autoridad']);

    // 3) Reglas de permiso:
    //    - Si es "autoridad": solo puede actualizar si el reporte está asignado a él
    //    - Si es "admin": puede actualizar cualquier reporte
    if ($tipoUsuario === 'autoridad') {
        if ($idAutoridadAsignada !== $id_usuario) {
            echo json_encode([
                'ok'      => false,
                'mensaje' => 'No tienes permiso para actualizar este reporte (no está asignado a ti).'
            ]);
            exit;
        }
    } elseif ($tipoUsuario !== 'admin') {
        // Ni autoridad ni admin => no puede
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Solo autoridades asignadas o el administrador pueden actualizar reportes.'
        ]);
        exit;
    }

    // 4) Preparar valores de cierre
    $fecha_cierre = null;
    $obs_cierre   = null;

    if ($estado === 'cerrado') {
        $fecha_cierre = date('Y-m-d H:i:s');         // fecha/hora actual
        $obs_cierre   = ($observacion !== '') ? $observacion : null;
    }

    // 5) Actualizar SOLO estado, fecha_cierre y observacion_cierre
    $sqlUpdate = "UPDATE reportes_deforestacion
                  SET estado_reporte     = :estado,
                      fecha_cierre       = :fecha_cierre,
                      observacion_cierre = :observacion
                  WHERE id_reporte       = :id_reporte";

    $stmtUpd = $pdo->prepare($sqlUpdate);
    $stmtUpd->execute([
        ':estado'       => $estado,
        ':fecha_cierre' => $fecha_cierre,  // null si no está cerrado
        ':observacion'  => $obs_cierre,    // null si no está cerrado
        ':id_reporte'   => $id_reporte
    ]);

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Estado del reporte actualizado correctamente.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al actualizar el reporte: ' . $e->getMessage()
    ]);
}

