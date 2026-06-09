<?php
// Web_MAS/php/obtener_categorias.php
header('Content-Type: application/json');
require_once 'conexion.php';

try {
    // Consultamos todas las categorías ordenadas alfabéticamente
    $sql = "SELECT id_categoria, nombre_categoria FROM categorias_actividad ORDER BY nombre_categoria ASC";
    $stmt = $pdo->query($sql);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'ok' => true,
        'categorias' => $categorias
    ]);
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al obtener las categorías: ' . $e->getMessage()
    ]);
}
?>