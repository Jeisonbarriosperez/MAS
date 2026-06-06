<?php
// WEB_FORESTACION/php/eliminar_usuario.php
header('Content-Type: application/json');

require_once 'conexion.php';

$id_usuario = intval($_POST['id_usuario'] ?? 0);

if ($id_usuario <= 0) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'ID de usuario inválido.'
    ]);
    exit;
}

// (Opcional) Evitar borrar al último admin, o evitar que el admin se borre a sí mismo
// Eso lo puedes agregar luego si quieres.

try {
    $sql = "DELETE FROM usuarios WHERE id_usuario = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id_usuario]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'No se encontró el usuario a eliminar.'
        ]);
        exit;
    }

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Usuario eliminado correctamente.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al eliminar usuario: ' . $e->getMessage()
    ]);
}
