<?php
// WEB_FORESTACION/php/crear_autoridad.php
header('Content-Type: application/json');
require_once 'conexion.php';

$nombre     = trim($_POST['nombre']    ?? '');
$apellido   = trim($_POST['apellido']  ?? '');
$correo     = trim($_POST['correo']    ?? '');
$telefono   = trim($_POST['telefono']  ?? '');
$municipio  = trim($_POST['municipio'] ?? '');
$vereda     = trim($_POST['vereda']    ?? '');
$clave      = trim($_POST['clave']     ?? '');
$creado_por = intval($_POST['creado_por'] ?? 0); // id del admin

// Recibimos la especialidad enviada por el select (será un ID numérico)
$id_especialidad = intval($_POST['especialidad'] ?? 0);

// Si el ID es 0 o menor, significa que es "General" (atiende todas las categorías), por ende se guarda como NULL
$id_especialidad = ($id_especialidad > 0) ? $id_especialidad : null;

if (
    $nombre === '' || $apellido === '' || $correo === '' ||
    $municipio === '' || $clave === '' || $creado_por <= 0
) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan campos obligatorios para registrar la autoridad.'
    ]);
    exit;
}

$telefono = ($telefono !== '') ? $telefono : null;

// 1) Verificar que no exista ya un usuario con ese correo o teléfono
try {
    $sqlCheck = "SELECT COUNT(*) AS total FROM usuarios WHERE correo = :correo OR (telefono IS NOT NULL AND telefono = :telefono)";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo'   => $correo,
        ':telefono' => $telefono
    ]);
    if ($stmtCheck->fetch()['total'] > 0) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Ya existe un usuario registrado con ese correo o teléfono.'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al verificar duplicados: ' . $e->getMessage()
    ]);
    exit;
}

// 2) Encriptar contraseña
$hash = password_hash($clave, PASSWORD_DEFAULT);

// 3) Insertar como autoridad vinculando la nueva columna id_especialidad
try {
    $sql = "INSERT INTO usuarios
            (nombre, apellido, correo, telefono, municipio, vereda_barrio,
             tipo_usuario, clave_hash, creado_por, id_especialidad)
            VALUES
            (:nombre, :apellido, :correo, :telefono, :municipio, :vereda,
             'autoridad', :clave_hash, :creado_por, :id_especialidad)";
             
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre'          => $nombre,
        ':apellido'        => $apellido,
        ':correo'          => $correo,
        ':telefono'        => $telefono,   
        ':municipio'       => $municipio,
        ':vereda'          => $vereda,
        ':clave_hash'      => $hash,
        ':creado_por'      => $creado_por,
        ':id_especialidad' => $id_especialidad
    ]);
    
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Autoridad creada correctamente.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error en la base de datos al crear autoridad: ' . $e->getMessage()
    ]);
}
?>