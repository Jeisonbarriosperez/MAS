<?php
// WEB_FORESTACION/php/obtener_autoridades_por_tipo.php
header('Content-Type: application/json');
require_once 'conexion.php';
// Permitimos tanto POST (desde JS) como GET (para probar en el navegador)
$tipo = $_POST['tipo_actividad'] ?? ($_GET['tipo_actividad'] ?? '');
if ($tipo === '') {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'No se recibió el tipo de actividad.'
    ]);
    exit;
}
try {
    $sql = "SELECT 
                id_usuario,
                nombre,
                apellido,
                municipio,
                especialidad
            FROM usuarios
            WHERE tipo_usuario = 'autoridad'
              AND (
                    especialidad = :tipo
                 OR especialidad = 'general'
                 OR especialidad = 'otra'
              )";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':tipo' => $tipo]);
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
