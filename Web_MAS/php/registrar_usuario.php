<?php
// WEB_FORESTACION/php/registrar_usuario.php
header('Content-Type: application/json');
require_once 'conexion.php';
// Leer datos del formulario (por POST)
$nombre    = trim($_POST['regNombre']    ?? '');
$apellido  = trim($_POST['regApellido']  ?? '');
$correo    = trim($_POST['regCorreo']    ?? '');
$telefono  = trim($_POST['regTelefono']  ?? '');
$municipio = trim($_POST['regMunicipio'] ?? '');
$vereda    = trim($_POST['regVereda']    ?? '');
$clave     = trim($_POST['regClave']     ?? '');
// Validación básica
if ($nombre === '' || $apellido === '' || $correo === '' || $municipio === '' || $clave === '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan campos obligatorios.'
    ]);
    exit;
}
// Normalizar teléfono: si viene vacío, lo dejamos en NULL
$telefono = ($telefono !== '') ? $telefono : null;
// 1) Verificar que no exista ya un usuario con ese correo o teléfono
try {
    $sqlCheck = "SELECT COUNT(*) AS total
                 FROM usuarios
                 WHERE correo = :correo
                    OR (telefono IS NOT NULL AND telefono = :telefono)";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo'   => $correo,
        ':telefono' => $telefono
    ]);
    $rowCheck = $stmtCheck->fetch();
    if ($rowCheck && $rowCheck['total'] > 0) {
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
// 3) Insertar como ciudadano
try {
    $sql = "INSERT INTO usuarios
            (nombre, apellido, correo, telefono, municipio, vereda_barrio, tipo_usuario, clave_hash)
            VALUES
            (:nombre, :apellido, :correo, :telefono, :municipio, :vereda, 'ciudadano', :clave_hash)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre'     => $nombre,
        ':apellido'   => $apellido,
        ':correo'     => $correo,
        ':telefono'   => $telefono,   // NULL si no se envió
        ':municipio'  => $municipio,
        ':vereda'     => $vereda,
        ':clave_hash' => $hash,
    ]);
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Usuario registrado correctamente como ciudadano.'
    ]);
} catch (PDOException $e) {
    // Por si acaso hay índice UNIQUE en la BD
    if ($e->getCode() === '23000') { // violación de UNIQUE
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Correo o teléfono ya están registrados en el sistema.'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error al registrar el usuario: ' . $e->getMessage()
        ]);
    }
}
