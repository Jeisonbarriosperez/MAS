<?php
// WEB_FORESTACION/php/crear_reporte.php
header('Content-Type: application/json');
require_once 'conexion.php';
$id_usuario        = intval($_POST['id_usuario']   ?? 0);
$id_autoridad      = intval($_POST['id_autoridad'] ?? 0);
$id_categoria      = intval($_POST['tipoActividad'] ?? 0);
$municipio         = trim($_POST['municipio'] ?? '');
$vereda            = trim($_POST['vereda']    ?? '');
$coordenadas       = trim($_POST['coordenadas'] ?? ''); 
$fecha_observacion = $_POST['fecha'] ?? '';
$hora_observacion  = $_POST['hora']  ?? null;
$hectareas         = $_POST['hectareas'] ?? null;
$ecosistema        = $_POST['ecosistema'] ?? 'no_especificado';
$descripcion       = trim($_POST['descripcion'] ?? '');
// Validar que se haya subido una imagen
if (empty($_FILES['evidencia']) || $_FILES['evidencia']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Es obligatorio adjuntar una imagen como evidencia.'
    ]);
    exit;
}
// 1) Validar campos obligatorios
if (
    $id_usuario   <= 0 ||
    $id_autoridad <= 0 ||
    $id_categoria <= 0 ||
    $municipio === '' ||
    $vereda === '' ||
    $fecha_observacion === '' ||
    $descripcion === ''
) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Faltan datos obligatorios para registrar el reporte.'
    ]);
    exit;
}
try {
    // 2) Verificar que el usuario que reporta exista y esté activo
    $sqlUser = "SELECT id_usuario, tipo_usuario, estado
                FROM usuarios
                WHERE id_usuario = :id";
    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->execute([':id' => $id_usuario]);
    $rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$rowUser || $rowUser['estado'] !== 'activo') {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'El usuario que envía el reporte no es válido o está inactivo.'
        ]);
        exit;
    }
    // 3) Verificar que la autoridad exista, sea autoridad y esté activa
    $sqlAut = "SELECT id_usuario, tipo_usuario, estado, id_especialidad
               FROM usuarios
               WHERE id_usuario = :idAut";
    $stmtAut = $pdo->prepare($sqlAut);
    $stmtAut->execute([':idAut' => $id_autoridad]);
    $rowAut = $stmtAut->fetch(PDO::FETCH_ASSOC);
    if (!$rowAut || $rowAut['tipo_usuario'] !== 'autoridad' || $rowAut['estado'] !== 'activo') {
        echo json_encode([
            'ok'      => false,
            'mensaje' => 'La autoridad seleccionada no es válida o no está activa.'
        ]);
        exit;
    }
    // 4) Manejo de la imagen (evidencia)
    $rutaRelativaFoto = null;
    if (!empty($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $tmpName        = $_FILES['evidencia']['tmp_name'];
        $nombreOriginal = $_FILES['evidencia']['name'];
        $info      = pathinfo($nombreOriginal);
        $extension = strtolower($info['extension'] ?? '');
        $permitidas = ['jpg', 'jpeg', 'png'];
        if (in_array($extension, $permitidas, true)) {
            $carpeta = __DIR__ . '/../recursos/evidencias/';
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
            $nombreFinal = time() . '_' . random_int(1000, 9999) . '.' . $extension;
            $rutaFisica  = $carpeta . $nombreFinal;
            if (move_uploaded_file($tmpName, $rutaFisica)) {
                $rutaRelativaFoto = 'recursos/evidencias/' . $nombreFinal;
            }
        }
    }
    // 5) Insertar reporte con autoridad asignada
    $sql = "INSERT INTO reportes_deforestacion
            (id_usuario, id_autoridad, id_categoria, municipio, vereda_zona, coordenadas,
             fecha_observacion, hora_observacion, hectareas_afectadas,
             ecosistema, descripcion, evidencia_foto)
            VALUES
            (:id_usuario, :id_autoridad, :id_categoria, :municipio, :vereda_zona, :coordenadas,
             :fecha_observacion, :hora_observacion, :hectareas_afectadas,
             :ecosistema, :descripcion, :evidencia_foto)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id_usuario'          => $id_usuario,
        ':id_autoridad'        => $id_autoridad,
        ':id_categoria'        => $id_categoria,
        ':municipio'           => $municipio,
        ':vereda_zona'         => $vereda,
        ':coordenadas'         => $coordenadas !== '' ? $coordenadas : null,
        ':fecha_observacion'   => $fecha_observacion,
        ':hora_observacion'    => $hora_observacion !== '' ? $hora_observacion : null,
        ':hectareas_afectadas' => $hectareas !== '' ? $hectareas : null,
        ':ecosistema'          => $ecosistema !== '' ? $ecosistema : 'no_especificado',
        ':descripcion'         => $descripcion,
        ':evidencia_foto'      => $rutaRelativaFoto
    ]);
    $id_generado = $pdo->lastInsertId();
    echo json_encode([
        'ok'         => true,
        'mensaje'    => 'Reporte registrado correctamente.',
        'id_reporte' => $id_generado,
        'ruta_foto'  => $rutaRelativaFoto
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok'      => false,
        'mensaje' => 'Error al registrar el reporte: ' . $e->getMessage()
    ]);
}
