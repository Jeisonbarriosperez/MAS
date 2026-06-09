<?php
// Web_MAS/php/crear_categoria.php
header('Content-Type: application/json');
require_once 'conexion.php';

// Recibimos el nombre de la categoría
$nombre_categoria = trim($_POST['nombre_categoria'] ?? '');

if ($nombre_categoria === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'El nombre de la categoría no puede estar vacío.']);
    exit;
}

try {
    // Insertamos la nueva categoría
    $sql = "INSERT INTO categorias_actividad (nombre_categoria) VALUES (:nombre)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':nombre' => $nombre_categoria]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Categoría agregada exitosamente.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al crear la categoría: ' . $e->getMessage()
    ]);
}
?>
