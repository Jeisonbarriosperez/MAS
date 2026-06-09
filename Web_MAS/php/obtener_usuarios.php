<?php
// WEB_FORESTACION/php/obtener_usuarios.php
header('Content-Type: application/json');
require_once 'conexion.php';
try {
    $sql = "SELECT 
                u.id_usuario, u.nombre, u.apellido, u.correo, u.telefono, 
                u.municipio, u.vereda_barrio, u.tipo_usuario,
                c.nombre_categoria AS especialidad,
                u.id_especialidad
            FROM usuarios u
            LEFT JOIN categorias_actividad c ON u.id_especialidad = c.id_categoria
            ORDER BY u.id_usuario ASC";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode([
        'ok'   => true,
        'data' => $usuarios
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al obtener usuarios: ' . $e->getMessage()
    ]);
}
