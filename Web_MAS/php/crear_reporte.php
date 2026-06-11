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
   // =========================================================================
    // 🚀 NUEVO MÓDULO: VERIFICACIÓN DE IMAGEN CON GOOGLE GEMINI AI
    // =========================================================================
    // Tu API Key exacta
    $apiKey = getenv('GCP_API_KEY'); 
    // Obtenemos la imagen y su tipo
    $tmpName  = $_FILES['evidencia']['tmp_name'];
    $mimeType = mime_content_type($tmpName);
    $imagenBase64 = base64_encode(file_get_contents($tmpName));
    // El enlace AHORA VA LIMPIO (sin el ?key=)
    $urlApi = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent";
    // Instrucciones estrictas para la IA
    $promptText = "Eres un moderador de un sistema ambiental en Colombia. Analiza esta imagen. " .
                  "Responde ÚNICAMENTE con un objeto JSON válido con la estructura: {\"valida\": true/false, \"motivo\": \"breve justificación en español\"}. " .
                  "Regla de oro: 'valida' debe ser true SOLO si la imagen muestra daños reales o potenciales al medio ambiente " .
                  "(deforestación, tala, quema, contaminación, basura, minería). " .
                  "Si la imagen es una selfie, un meme, pornografía, objetos irrelevantes o paisajes sin daño, 'valida' debe ser false. " .
                  "No incluyas formato markdown en tu respuesta, solo el texto JSON.";
    // Armamos el paquete que exige Google
    $dataEnvio = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $promptText],
                    [
                        "inline_data" => [
                            "mime_type" => $mimeType,
                            "data"      => $imagenBase64
                        ]
                    ]
                ]
            ]
        ]
    ];
    // Conexión cURL hacia Google (TRADUCIDA EXACTAMENTE DE TU COMANDO CURL)
    $ch = curl_init($urlApi);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataEnvio));
    // AQUÍ ESTÁ LA MAGIA: Pasamos tu llave en el encabezado X-goog-api-key
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-goog-api-key: ' . $apiKey
    ]);
    // Evitamos bloqueos de XAMPP/Localhost
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $respuestaGemini = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);
    // MODO DEPURACIÓN: Si falla, te mostrará el error en pantalla
    if ($httpCode !== 200) {
        echo json_encode([
            'ok'      => false,
            'mensaje' => "Error de API Google ($httpCode). Detalles: " . print_r($respuestaGemini, true) . " - " . $errorCurl
        ]);
        exit; 
    }
    // Evaluamos la respuesta de la IA
    if ($respuestaGemini) {
        $resultadoDecodificado = json_decode($respuestaGemini, true);
        $textoIA = $resultadoDecodificado['candidates'][0]['content']['parts'][0]['text'] ?? '';
        // Limpiamos la respuesta de la IA
        $textoIA = str_replace(['```json', '```'], '', $textoIA);
        $decisionIA = json_decode(trim($textoIA), true);
        // Si la IA decide que NO es válida, detenemos el proceso
        if (isset($decisionIA['valida']) && $decisionIA['valida'] === false) {
            echo json_encode([
                'ok'      => false,
                'mensaje' => '⚠️ Foto rechazada por Seguridad IA: ' . $decisionIA['motivo']
            ]);
            exit;
        }
    }
    // =========================================================================
    // 2) Verificar que el usuario que reporta exista y esté activo
    $sqlUser = "SELECT id_usuario, tipo_usuario, estado FROM usuarios WHERE id_usuario = :id";
    $stmtUser = $pdo->prepare($sqlUser);
    $stmtUser->execute([':id' => $id_usuario]);
    $rowUser = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$rowUser || $rowUser['estado'] !== 'activo') {
        echo json_encode(['ok' => false, 'mensaje' => 'El usuario que envía el reporte no es válido o está inactivo.']);
        exit;
    }
    // 3) Verificar que la autoridad exista, sea autoridad y esté activa
    $sqlAut = "SELECT id_usuario, tipo_usuario, estado, id_especialidad FROM usuarios WHERE id_usuario = :idAut";
    $stmtAut = $pdo->prepare($sqlAut);
    $stmtAut->execute([':idAut' => $id_autoridad]);
    $rowAut = $stmtAut->fetch(PDO::FETCH_ASSOC);
    if (!$rowAut || $rowAut['tipo_usuario'] !== 'autoridad' || $rowAut['estado'] !== 'activo') {
        echo json_encode(['ok' => false, 'mensaje' => 'La autoridad seleccionada no es válida o no está activa.']);
        exit;
    }
    // 4) Manejo de la imagen (evidencia) - AHORA SÍ LA GUARDAMOS FÍSICAMENTE
    $rutaRelativaFoto = null;
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
?>
