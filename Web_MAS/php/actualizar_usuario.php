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
$especialidad = trim($_POST['especialidad'] ?? '');
$nueva_clave  = trim($_POST['nueva_clave']  ?? '');
if ($id_usuario <= 0 || $nombre === '' || $apellido === '' || $correo === '' || $tipo_usuario === '') {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Faltan datos obligatorios para actualizar el usuario.'
    ]);
    exit;
}
$telefono = ($telefono !== '') ? $telefono : null;
// Validar tipo_usuario simple 
$tiposValidos = ['ciudadano', 'autoridad', 'admin'];
if (!in_array($tipo_usuario, $tiposValidos, true)) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Tipo de usuario inválido.'
    ]);
    exit;
}
// Validar especialidad simple
$especialidadesValidas = [
    'tala','quema','cambio_uso','extraccion','otra','general',
    'contaminacion_agua','contaminacion_aire','residuos_solidos',
    'trafico_fauna','mineria_ilegal',''
];
if (!in_array($especialidad, $especialidadesValidas, true)) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Especialidad inválida.'
    ]);
    exit;
}
// --- NUEVA VALIDACIÓN: evitar quitar especialidad/rol a autoridad con reportes sin sustituto ---
// Solo aplica si el usuario ES actualmente una autoridad
$sqlActual = "SELECT tipo_usuario, especialidad FROM usuarios WHERE id_usuario = :id";
$stmtActual = $pdo->prepare($sqlActual);
$stmtActual->execute([':id' => $id_usuario]);
$datosActuales = $stmtActual->fetch();

if ($datosActuales && $datosActuales['tipo_usuario'] === 'autoridad') {
    $especialidadActual = $datosActuales['especialidad'];
    $nuevoTipo = $tipo_usuario;
    $nuevaEspecialidad = ($tipo_usuario === 'autoridad') ? $especialidad : 'general';
    // ¿Está cambiando el rol o la especialidad?
    $cambiaRol = ($nuevoTipo !== 'autoridad');
    $cambiaEspecialidad = ($nuevaEspecialidad !== $especialidadActual);
    if ($cambiaRol || $cambiaEspecialidad) {
        // Contar reportes NO cerrados asignados a esta autoridad
        $sqlReportes = "SELECT COUNT(*) AS total
                        FROM reportes_deforestacion
                        WHERE id_autoridad = :idAutoridad
                          AND estado_reporte != 'cerrado'";
        $stmtRep = $pdo->prepare($sqlReportes);
        $stmtRep->execute([':idAutoridad' => $id_usuario]);
        $totalReportes = $stmtRep->fetchColumn();
        if ($totalReportes > 0) {
            $sqlReasignar = "UPDATE reportes_deforestacion 
                 SET id_autoridad = :idSustituto 
                 WHERE id_autoridad = :idAntiguo AND estado_reporte != 'cerrado'";
            // Buscar otra autoridad activa con la misma especialidad actual (excluyendo al mismo usuario)
            $sqlSustituto = "SELECT COUNT(*) AS total
                             FROM usuarios
                             WHERE tipo_usuario = 'autoridad'
                               AND estado = 'activo'
                               AND especialidad = :espActual
                               AND id_usuario != :idAutoridad";
            $stmtSust = $pdo->prepare($sqlSustituto);
            $stmtSust->execute([
                ':espActual'   => $especialidadActual,
                ':idAutoridad' => $id_usuario
            ]);
            $totalSustitutos = $stmtSust->fetchColumn();
            if ($totalSustitutos == 0) {
                echo json_encode([
                    'ok'      => false,
                    'mensaje' => "No se puede cambiar el rol o la especialidad porque esta autoridad tiene $totalReportes reporte(s) activos y no existe otra autoridad con la especialidad '$especialidadActual' que pueda asumirlos. Primero asigne esos reportes a otra autoridad o ciérrelos."
                ]);
                exit;
            }
        }
    }
}
// --- FIN DE LA NUEVA VALIDACIÓN ---
try {
    // 1) Verificar que correo / teléfono no estén usados por otro usuario
    $sqlCheck = "SELECT COUNT(*) AS total
                 FROM usuarios
                 WHERE (correo = :correo
                        OR (telefono IS NOT NULL AND telefono = :telefono))
                   AND id_usuario <> :id";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([
        ':correo'   => $correo,
        ':telefono' => $telefono,
        ':id'       => $id_usuario
    ]);
    $rowCheck = $stmtCheck->fetch();
    if ($rowCheck && $rowCheck['total'] > 0) {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'Ya existe otro usuario con ese correo o teléfono.'
        ]);
        exit;
    }
    // 2) Armamos el UPDATE dinámico
    $campos = [
        'nombre'       => $nombre,
        'apellido'     => $apellido,
        'correo'       => $correo,
        'telefono'     => $telefono,
        'municipio'    => $municipio,
        'vereda_barrio'=> $vereda,
        'tipo_usuario' => $tipo_usuario,
        'especialidad' => ($tipo_usuario === 'autoridad' ? $especialidad : 'general'),
    ];
    $setPartes = [];
    $params = [];
    foreach ($campos as $col => $val) {
        $setPartes[]          = "$col = :$col";
        $params[":$col"]      = $val;
    }
    // Si el admin envió nueva_clave, actualizamos clave_hash
    if ($nueva_clave !== '') {
        $setPartes[]        = "clave_hash = :clave_hash";
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
        'mensaje' => 'Error al actualizar usuario: ' . $e->getMessage()
    ]);
}
