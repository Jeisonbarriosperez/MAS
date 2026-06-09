<?php
// WEB_FORESTACION/php/actualizar_estado_reporte.php
header('Content-Type: application/json');

require_once 'conexion.php';

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
    $userRow = $stmtUser->fetch();

    if (!$userRow || $userRow['estado'] !== 'active' && $userRow['estado'] !== 'activo') {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Usuario no encontrado o inactivo.'
        ]);
        exit;
    }

    $tipoUsuario = $userRow['tipo_usuario'];

    // 2) Validar permisos (Solo el admin o la autoridad asignada a este reporte)
    if ($tipoUsuario === 'autoridad') {
        $sqlCheck = "SELECT id_autoridad FROM reportes_deforestacion WHERE id_reporte = :id_reporte";
        $stmtCheck = $pdo->prepare($sqlCheck);
        $stmtCheck->execute([':id_reporte' => $id_reporte]);
        $reporteCheck = $stmtCheck->fetch();

        if (!$reporteCheck || intval($reporteCheck['id_autoridad']) !== $id_usuario) {
            echo json_encode([
                'ok'      => false,
                'mensaje' => 'No tienes permisos sobre este reporte (no te ha sido asignado).'
            ]);
            exit;
        }
    } elseif ($tipoUsuario !== 'admin') {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Solo autoridades asignadas o el administrador pueden gestionar reportes.'
        ]);
        exit;
    }

    // 3) ACCIÓN CONDICIONAL: ¿Se está cerrando el reporte?
    if ($estado === 'cerrado') {
        
        // A. Obtener la ruta de la foto antes de borrar el registro para no perder la referencia
        $sqlSel = "SELECT evidencia_foto FROM reportes_deforestacion WHERE id_reporte = :id_reporte";
        $stmtSel = $pdo->prepare($sqlSel);
        $stmtSel->execute([':id_reporte' => $id_reporte]);
        $rowReporte = $stmtSel->fetch();

        if ($rowReporte) {
            $rutaRelativa = $rowReporte['evidencia_foto'];

            // B. Eliminar físicamente el registro de la base de datos
            $sqlDel = "DELETE FROM reportes_deforestacion WHERE id_reporte = :id_reporte";
            $stmtDel = $pdo->prepare($sqlDel);
            $stmtDel->execute([':id_reporte' => $id_reporte]);

            // C. Eliminar la foto del servidor si existe
            if ($rutaRelativa) {
                $rutaFisica = __DIR__ . '/../' . $rutaRelativa;
                if (file_exists($rutaFisica)) {
                    @unlink($rutaFisica);
                }
            }

            echo json_encode([
                'ok'      => true,
                'mensaje' => 'El reporte ha sido cerrado y eliminado del sistema correctamente junto con su evidencia.'
            ]);
            exit;
        } else {
            echo json_encode([
                'ok'      => false,
                'mensaje' => 'El reporte ya no existe en el sistema.'
            ]);
            exit;
        }
    }

    // 4) Si el estado NO es 'cerrado' (ej: en_proceso, en_revision), se actualiza normalmente
    $sqlUpdate = "UPDATE reportes_deforestacion
                  SET estado_reporte     = :estado,
                      fecha_cierre       = NULL,
                      observacion_cierre = NULL
                  WHERE id_reporte       = :id_reporte";
    $stmtUpd = $pdo->prepare($sqlUpdate);
    $stmtUpd->execute([
        ':estado'     => $estado,
        ':id_reporte' => $id_reporte
    ]);

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Estado del reporte actualizado a: ' . $estado
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error crítico en el servidor: ' . $e->getMessage()
    ]);
}