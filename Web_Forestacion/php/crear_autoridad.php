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

// 🔹 NUEVO: especialidad de la autoridad
$especialidad = $_POST['especialidad'] ?? 'general';

// Validar que venga algo coherente
$especialidadesPermitidas = ['tala','quema','cambio_uso','extraccion','otra','general'];
if (!in_array($especialidad, $especialidadesPermitidas, true)) {
    $especialidad = 'general';
}

if (
    $nombre === '' || $apellido === '' || $correo === '' ||
    $municipio === '' || $clave === '' || $creado_por <= 0
) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan datos obligatorios para crear la autoridad.'
    ]);
    exit;
}

// Normalizar teléfono
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
            'mensaje' => 'Ya existe un usuario (ciudadano o autoridad) con ese correo o teléfono.'
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

// 3) Insertar como autoridad (ahora con especialidad)
try {
    $sql = "INSERT INTO usuarios
            (nombre, apellido, correo, telefono, municipio, vereda_barrio,
             tipo_usuario, clave_hash, creado_por, especialidad)
            VALUES
            (:nombre, :apellido, :correo, :telefono, :municipio, :vereda,
             'autoridad', :clave_hash, :creado_por, :especialidad)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nombre'       => $nombre,
        ':apellido'     => $apellido,
        ':correo'       => $correo,
        ':telefono'     => $telefono,   // NULL si no se envió
        ':municipio'    => $municipio,
        ':vereda'       => $vereda,
        ':clave_hash'   => $hash,
        ':creado_por'   => $creado_por,
        ':especialidad' => $especialidad
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Autoridad creada correctamente.'
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Correo o teléfono ya están registrados en el sistema.'
        ]);
    } else {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'Error al crear autoridad: ' . $e->getMessage()
        ]);
    }
}


