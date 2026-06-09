<?php
// WEB_FORESTACION/php/obtener_autoridades_por_tipo.php
header('Content-Type: application/json');
require_once 'conexion.php';

// Ahora recibimos un ID numérico
$id_categoria = intval($_POST['tipo_actividad'] ?? ($_GET['tipo_actividad'] ?? 0));

if ($id_categoria <= 0) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'No se recibió una categoría válida.'
    ]);
    exit;
}

try {
    // Hacemos un JOIN para obtener el nombre de la especialidad y enviarlo a JS
    $sql = "SELECT 
                u.id_usuario,
                u.nombre,
                u.apellido,
                u.municipio,
                IFNULL(c.nombre_categoria, 'General') AS especialidad
            FROM usuarios u
            LEFT JOIN categorias_actividad c ON u.id_especialidad = c.id_categoria
            WHERE u.tipo_usuario = 'autoridad'
              AND (u.id_especialidad = :cat OR u.id_especialidad IS NULL)";
              
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':cat' => $id_categoria]);
    $autoridades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'ok'   => true,
        'data' => $autoridades
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al obtener autoridades: ' . $e->getMessage()
    ]);
}
?>