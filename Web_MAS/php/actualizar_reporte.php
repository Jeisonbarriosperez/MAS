<?php
// WEB_FORESTACION/php/actualizar_reporte.php
header('Content-Type: application/json');

require_once 'conexion.php';
$id_reporte = intval($_POST['id_reporte'] ?? 0);
$id_usuario = intval($_POST['id_usuario'] ?? 0);
$id_categoria      = intval($_POST['tipoActividad'] ?? 0);
$municipio         = trim($_POST['municipio'] ?? '');
$vereda            = trim($_POST['vereda'] ?? '');
$coordenadas       = trim($_POST['coordenadas'] ?? '');
$fecha_observacion = $_POST['fecha'] ?? '';
$hora_observacion  = $_POST['hora'] ?? null;
$hectareas         = $_POST['hectareas'] ?? null;
$ecosistema        = $_POST['ecosistema'] ?? 'no_especificado';
$descripcion       = trim($_POST['descripcion'] ?? '');
// Validar básicos
if (
    $id_reporte <= 0 ||
    $id_usuario <= 0 ||
    $id_categoria <= 0 ||
    $municipio === '' ||
    $vereda === '' ||
    $fecha_observacion === '' ||
    $descripcion === ''
) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Faltan datos obligatorios para actualizar el reporte.'
    ]);
    exit;
}

try {
    // 1) Verificar que el reporte exista y pertenezca a ese usuario
    $sqlSel = "SELECT evidencia_foto
               FROM reportes_deforestacion
               WHERE id_reporte = :id AND id_usuario = :usuario";
    $stmtSel = $pdo->prepare($sqlSel);
    $stmtSel->execute([
        ':id'      => $id_reporte,
        ':usuario' => $id_usuario
    ]);
    $row = $stmtSel->fetch();
    if (!$row) {
        echo json_encode([
            'ok' => false,
            'mensaje' => 'No se encontró el reporte o no pertenece a este usuario.'
        ]);
        exit;
    }
    $rutaAnterior = $row['evidencia_foto'];
    $rutaRelativaFoto = $rutaAnterior;
    // 2) ¿Subió una nueva imagen?
    if (!empty($_FILES['evidencia']) && $_FILES['evidencia']['error'] === UPLOAD_ERR_OK) {
        $tmpName  = $_FILES['evidencia']['tmp_name'];
        $nombreOriginal = $_FILES['evidencia']['name'];
        $info = pathinfo($nombreOriginal);
        $extension = strtolower($info['extension'] ?? '');
        $permitidas = ['jpg', 'jpeg', 'png'];
        if (in_array($extension, $permitidas)) {
            $carpeta = __DIR__ . '/../recursos/evidencias/';
            if (!is_dir($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
            $nombreFinal = time() . '_' . random_int(1000, 9999) . '.' . $extension;
            $rutaFisicaNueva  = $carpeta . $nombreFinal;
            if (move_uploaded_file($tmpName, $rutaFisicaNueva)) {
                $rutaRelativaFoto = 'recursos/evidencias/' . $nombreFinal;
                // borrar la foto anterior si existía
                if ($rutaAnterior) {
                    $rutaFisicaVieja = __DIR__ . '/../' . $rutaAnterior;
                    if (is_file($rutaFisicaVieja)) {
                        @unlink($rutaFisicaVieja);
                    }
                }
            }
        }
    }
    // 3) Actualizar registro
    $sqlUpd = "UPDATE reportes_deforestacion
               SET id_categoria        = :id_categoria
                   municipio           = :municipio,
                   vereda_zona         = :vereda_zona,
                   coordenadas         = :coordenadas,
                   fecha_observacion   = :fecha_observacion,
                   hora_observacion    = :hora_observacion,
                   hectareas_afectadas = :hectareas_afectadas,
                   ecosistema          = :ecosistema,
                   descripcion         = :descripcion,
                   evidencia_foto      = :evidencia_foto,
                   fecha_actualizacion = NOW()
               WHERE id_reporte = :id_reporte
                 AND id_usuario = :id_usuario";
    $stmtUpd = $pdo->prepare($sqlUpd);
    $stmtUpd->execute([
        ':id_categoria'       => $id_categoria,
        ':municipio'          => $municipio,
        ':vereda_zona'        => $vereda,
        ':coordenadas'        => $coordenadas !== '' ? $coordenadas : null,
        ':fecha_observacion'  => $fecha_observacion,
        ':hora_observacion'   => $hora_observacion !== '' ? $hora_observacion : null,
        ':hectareas_afectadas'=> $hectareas !== '' ? $hectareas : null,
        ':ecosistema'         => $ecosistema !== '' ? $ecosistema : 'no_especificado',
        ':descripcion'        => $descripcion,
        ':evidencia_foto'     => $rutaRelativaFoto,
        ':id_reporte'         => $id_reporte,
        ':id_usuario'         => $id_usuario
    ]);
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Reporte actualizado correctamente.'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Error al actualizar el reporte: ' . $e->getMessage()
    ]);
}
