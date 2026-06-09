<?php
// WEB_FORESTACION/php/obtener_reportes.php
header('Content-Type: application/json');
require_once 'conexion.php';

try {
    // Combinamos reportes con usuarios (para saber quién reportó) y con categorías (para saber el tipo de actividad en texto)
    $sql = "SELECT
              r.*,
              u.nombre   AS ciudadano_nombre,
              u.apellido AS ciudadano_apellido,
              u.correo   AS ciudadano_correo,
              IFNULL(c.nombre_categoria, 'No especificado') AS tipo_actividad
            FROM reportes_deforestacion r
            JOIN usuarios u ON u.id_usuario = r.id_usuario
            LEFT JOIN categorias_actividad c ON r.id_categoria = c.id_categoria
            ORDER BY r.fecha_registro DESC";
            
    $stmt = $pdo->query($sql);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
?>