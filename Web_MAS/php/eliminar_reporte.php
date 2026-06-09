<?php
// WEB_FORESTACION/php/eliminar_reporte.php
header('Content-Type: application/json');
require_once 'conexion.php';
$id_reporte = intval($_POST['id_reporte'] ?? 0);
$id_usuario = intval($_POST['id_usuario'] ?? 0);
if ($id_reporte <= 0 || $id_usuario <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Datos inválidos para eliminar el reporte.'
    ]);
    exit;
}
try {
    // 1) Obtener la ruta de la foto (si existe)
    $sqlSel = "SELECT evidencia_foto
               FROM reportes_deforestacion
               WHERE id_reporte = :id AND id_usuario = :usuario";
    $stmtSel = $pdo->prepare($sqlSel);
    $stmtSel->execute([
        ':id'      => $id_reporte,
        ':usuario' => $id_usuario
    ]);
    $row = $stmtSel->fetch();
    if (!$row) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el reporte o no pertenece a este usuario.'
        ]);
        exit;
    }
    $rutaRelativa = $row['evidencia_foto'];
    // 2) Eliminar el registro
    $sqlDel = "DELETE FROM reportes_deforestacion
               WHERE id_reporte = :id AND id_usuario = :usuario";
    $stmtDel = $pdo->prepare($sqlDel);
    $stmtDel->execute([
        ':id'      => $id_reporte,
        ':usuario' => $id_usuario
    ]);
    if ($stmtDel->rowCount() > 0 && $rutaRelativa) {
        $rutaFisica = __DIR__ . '/../' . $rutaRelativa;
        if (is_file($rutaFisica)) {
            @unlink($rutaFisica);
        }
    }
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Reporte eliminado correctamente.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al eliminar el reporte: ' . $e->getMessage()
    ]);
}
