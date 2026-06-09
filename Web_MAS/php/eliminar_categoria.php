<?php
// WEB_MAS/php/eliminar_categoria.php
header('Content-Type: application/json');
require_once 'conexion.php';

$id_categoria = intval($_POST['id_categoria'] ?? 0);

if ($id_categoria <= 0) {
    echo json_encode(['ok' => false, 'mensaje' => 'ID de categoría inválido.']); exit;
}

try {
    $sql = "DELETE FROM categorias_actividad WHERE id_categoria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_categoria]);
    
    echo json_encode(['ok' => true, 'mensaje' => 'Categoría eliminada con éxito.']);
} catch (PDOException $e) {
    // Error 23000: Violación de llave foránea (está en uso)
    if ($e->getCode() == '23000') {
        echo json_encode([
            'ok' => false, 
            'mensaje' => 'No se puede eliminar la categoría porque hay autoridades asignadas a ella o reportes activos.'
        ]);
    } else {
        echo json_encode(['ok' => false, 'mensaje' => 'Error al eliminar: ' . $e->getMessage()]);
    }
}