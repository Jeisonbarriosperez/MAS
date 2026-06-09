<?php
// WEB_FORESTACION/php/actualizar_usuario.php
header('Content-Type: application/json');
require_once 'conexion.php';

$id_usuario   = intval($_POST['id_usuario'] ?? 0);
$nombre       = trim($_POST['nombre']       ?? '');
$apellido     = trim($_POST['apellido']     ?? '');
$correo       = trim($_POST['correo']       ?? '');
$telefono     = trim($_POST['telefono']     ?? '');
$municipio    = trim($_POST['municipio']    ?? '');
$vereda       = trim($_POST['vereda']       ?? '');
$tipo_usuario = trim($_POST['tipo_usuario'] ?? '');
$nueva_clave  = trim($_POST['nueva_clave']  ?? '');

// Recibimos la especialidad como ID numérico
$id_especialidad = intval($_POST['especialidad'] ?? 0);
$id_especialidad = ($id_especialidad > 0 && $tipo_usuario === 'autoridad') ? $id_especialidad : null;

if ($id_usuario <= 0 || $nombre === '' || $apellido === '' || $correo === '' || $tipo_usuario === '') {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Faltan datos obligatorios para actualizar el usuario.'
    ]);
    exit;
}

$telefono = ($telefono !== '') ? $telefono : null;

$tiposValidos = ['ciudadano', 'autoridad', 'admin'];
if (!in_array($tipo_usuario, $tiposValidos, true)) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Tipo de usuario inválido.'
    ]);
    exit;
}

try {
    // 1) Verificar que el correo o teléfono no estén duplicados en otro usuario
    $sqlCheck = "SELECT COUNT(*) AS total FROM usuarios WHERE (correo = :correo OR (telefono IS NOT NULL AND telefono = :telefono)) AND id_usuario != :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo'   => $correo,
        ':telefono' => $telefono,
        ':id'       => $id_usuario
    ]);
    if ($stmtCheck->fetch()['total'] > 0) {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Ya existe otro usuario con ese correo o teléfono.'
        ]);
        exit;
    }

    // 2) Armamos el UPDATE dinámico usando id_especialidad
    $campos = [
        'nombre'        => $nombre,
        'apellido'      => $apellido,
        'correo'        => $correo,
        'telefono'      => $telefono,
        'municipio'     => $municipio,
        'vereda_barrio' => $vereda,
        'tipo_usuario'  => $tipo_usuario,
        'id_especialidad' => $id_especialidad, // Mapeado correctamente a la nueva columna
    ];

    $setPartes = [];
    $params = [];
    foreach ($campos as $col => $val) {
        $setPartes[]     = "$col = :$col";
        $params[":$col"] = $val;
    }

    // Si el administrador cambió la contraseña, la añadimos al update
    if ($nueva_clave !== '') {
        $setPartes[]           = "clave_hash = :clave_hash";
        $params[':clave_hash'] = password_hash($nueva_clave, PASSWORD_DEFAULT);
    }

    $params[':id_usuario'] = $id_usuario;

    $sql = "UPDATE usuarios
            SET " . implode(", ", $setPartes) . "
            WHERE id_usuario = :id_usuario";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'ok'      => true,
        'mensaje' => 'Usuario actualizado correctamente.'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error en el servidor al actualizar: ' . $e->getMessage()
    ]);
}
?>
