<?php
// Web_MAS/php/modificar_categoria.php
header('Content-Type: application/json');
require_once 'conexion.php';

// 1) Recibimos los datos enviados por JavaScript
$id_categoria = intval($_POST['id_categoria'] ?? 0);
$nuevo_nombre = trim($_POST['nuevo_nombre'] ?? '');

// 2) Validaciones básicas
if ($id_categoria <= 0) {
    echo json_encode([
        'ok' => false, 
        'mensaje' => 'ID de categoría inválido.'
    ]);
    exit;
}

if ($nuevo_nombre === '') {
    echo json_encode([
        'ok' => false, 
        'mensaje' => 'El nombre de la categoría no puede estar vacío.'
    ]);
    exit;
}

try {
    // 3) Verificar si el NUEVO nombre ya existe en OTRA categoría distinta
    // Usamos id_categoria != :id para que no arroje error si el usuario 
    // guarda exactamente el mismo nombre que ya tenía su categoría.
    $sqlCheck = "SELECT COUNT(*) FROM categorias_actividad 
                 WHERE LOWER(nombre_categoria) = LOWER(:nombre) 
                 AND id_categoria != :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':nombre' => $nuevo_nombre,
        ':id'     => $id_categoria
    ]);
    $existe = $stmtCheck->fetchColumn();

    if ($existe > 0) {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'No se puede modificar: Ya existe otra categoría con ese nombre.'
        ]);
        exit;
    }

    // 4) Actualizar la categoría en la base de datos
    $sql = "UPDATE categorias_actividad 
            SET nombre_categoria = :nombre 
            WHERE id_categoria = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre' => $nuevo_nombre,
        ':id'     => $id_categoria
    ]);

    // Verificamos si realmente se modificó alguna fila
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'ok'      => true,
            'mensaje' => 'Categoría actualizada correctamente.'
        ]);
    } else {
        // Si el usuario presiona "Guardar" sin cambiar ni una letra del nombre actual
        echo json_encode([
            'ok'      => true,
            'mensaje' => 'No se detectaron cambios en el nombre de la categoría.'
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al modificar la categoría: ' . $e->getMessage()
    ]);
}
?>