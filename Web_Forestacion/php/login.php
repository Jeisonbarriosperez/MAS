<?php
// WEB_FORESTACION/php/login.php
header('Content-Type: application/json');

require_once 'conexion.php';

$correo = trim($_POST['loginCorreo'] ?? '');
$clave  = trim($_POST['loginClave']  ?? '');

if ($correo === '' || $clave === '') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Correo y contraseña son obligatorios.'
    ]);
    exit;
}

$sql = "SELECT id_usuario, nombre, apellido, tipo_usuario, clave_hash, estado
        FROM usuarios
        WHERE correo = :correo
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':correo' => $correo]);
$usuario = $stmt->fetch();

if (!$usuario) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Usuario no encontrado.'
    ]);
    exit;
}

if ($usuario['estado'] !== 'activo') {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'El usuario se encuentra inactivo.'
    ]);
    exit;
}

if (!password_verify($clave, $usuario['clave_hash'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Contraseña incorrecta.'
    ]);
    exit;
}

// Aquí podrías iniciar sesión con $_SESSION, pero para este proyecto
// bastará con devolver los datos básicos al frontend.
echo json_encode([
    'ok'         => true,
    'mensaje'    => 'Inicio de sesión exitoso.',
    'id_usuario' => $usuario['id_usuario'],
    'nombre'     => $usuario['nombre'],
    'apellido'   => $usuario['apellido'],
    'rol'        => $usuario['tipo_usuario']
]);
