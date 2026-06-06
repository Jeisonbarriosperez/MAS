<?php
// WEB_FORESTACION/php/obtener_reportes.php
header('Content-Type: application/json');

require_once 'conexion.php';

try {
    $sql = "SELECT
              r.*,
              u.nombre   AS ciudadano_nombre,
              u.apellido AS ciudadano_apellido,
              u.correo   AS ciudadano_correo
            FROM reportes_deforestacion r
            JOIN usuarios u ON u.id_usuario = r.id_usuario
            ORDER BY r.fecha_registro DESC";

    $stmt = $pdo->query($sql);
    $reportes = $stmt->fetchAll();

    echo json_encode([
        'ok'   => true,
        'data' => $reportes
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener reportes: ' . $e->getMessage()
    ]);
}
