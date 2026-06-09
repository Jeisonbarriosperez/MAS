<?php
// WEB_FORESTACION/php/obtener_mis_reportes.php
header('Content-Type: application/json');
require_once 'conexion.php';
$id_usuario = intval($_POST['id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'ID de usuario inválido.'
    ]);
    exit;
}
try {
    $sql = "SELECT *
            FROM reportes_deforestacion
            WHERE id_usuario = :id
            ORDER BY fecha_registro DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_usuario]);
    $rows = $stmt->fetchAll();
    echo json_encode([
        'ok'   => true,
        'data' => $rows
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener tus reportes: ' . $e->getMessage()
    ]);
}
