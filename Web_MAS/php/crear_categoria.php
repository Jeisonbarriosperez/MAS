<?php
// Web_MAS/php/crear_categoria.php
header('Content-Type: application/json');
require_once 'conexion.php';

// Recibimos el nombre de la categoría y le quitamos espacios a los lados
$nombre_categoria = trim($_POST['nombre_categoria'] ?? '');

if ($nombre_categoria === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'El nombre de la categoría no puede estar vacío.']);
    exit;
}

try {
    // 1) Verificar si la categoría ya existe (LOWER nos ayuda a que "Tala" y "tala" cuenten como lo mismo)
    $sqlCheck = "SELECT COUNT(*) FROM categorias_actividad WHERE LOWER(nombre_categoria) = LOWER(:nombre)";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([':nombre' => $nombre_categoria]);
    $existe = $stmtCheck->fetchColumn();

    if ($existe > 0) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se puede agregar: Ya existe una categoría con ese nombre.'
        ]);
        exit;
    }

    // 2) Si no existe, insertamos la nueva categoría
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