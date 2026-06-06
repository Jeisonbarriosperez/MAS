<?php
// WEB_FORESTACION/php/obtener_usuarios.php
header('Content-Type: application/json');

require_once 'conexion.php';

try {
    $sql = "SELECT 
                id_usuario,
                nombre,
                apellido,
                correo,
                telefono,
                municipio,
                vereda_barrio,
                tipo_usuario,
                especialidad
            FROM usuarios
            ORDER BY id_usuario ASC";

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
