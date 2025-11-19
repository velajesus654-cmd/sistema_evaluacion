<?php
// Inicio temprano del buffer
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Función para enviar respuesta JSON
function sendJsonResponse($success, $message, $additionalData = []) {
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    $response = array_merge(['success' => $success, 'message' => $message], $additionalData);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Verificaciones básicas
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(false, 'Método no permitido. Use POST.');
    }

    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        sendJsonResponse(false, 'Error al subir el archivo.');
    }

    // Verificar y cargar dependencias
    if (!file_exists('vendor/autoload.php')) {
        sendJsonResponse(false, 'Error: Composer no está instalado. Ejecuta "composer install" en la terminal.');
    }
    require_once 'vendor/autoload.php';

    // Verificar y cargar configuración de base de datos - CORREGIDO
    $configPath = __DIR__ . '/config/database.php';
    if (!file_exists($configPath)) {
        sendJsonResponse(false, 'Error: Archivo config/database.php no encontrado en: ' . $configPath);
    }
    
    require_once $configPath;

    // Verificar que la clase Database existe
    if (!class_exists('Database')) {
        sendJsonResponse(false, 'Error: La clase Database no está definida en config/database.php');
    }

    // Procesar archivo Excel
    $uploadedFile = $_FILES['excel_file']['tmp_name'];
    
    if (!file_exists($uploadedFile)) {
        sendJsonResponse(false, 'Error: Archivo temporal no encontrado.');
    }

    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($uploadedFile);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    // Verificar datos
    if (count($rows) <= 1) {
        sendJsonResponse(false, 'El archivo está vacío o solo contiene encabezados.');
    }

    // Omitir encabezados
    $headerRow = array_shift($rows);
    
    // Conectar a BD
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        sendJsonResponse(false, 'Error: No se pudo conectar a la base de datos. Verifica las credenciales en config/database.php');
    }

    $successCount = 0;
    $errorCount = 0;
    $errors = [];

    // Preparar consultas
    $insertSql = "INSERT INTO usuarios 
                 (id_usuario, clave, nombres, apellidos, email, id_tipo_usuario) 
                 VALUES (?, ?, ?, ?, ?, ?)";
    
    $checkTypeSql = "SELECT id_tipo_usuario FROM tipo_usuario WHERE id_tipo_usuario = ?";
    $checkUserSql = "SELECT id_usuario FROM usuarios_encuestas WHERE id_usuario = ?";

    $insertStmt = $db->prepare($insertSql);
    $checkTypeStmt = $db->prepare($checkTypeSql);
    $checkUserStmt = $db->prepare($checkUserSql);

    foreach ($rows as $rowIndex => $row) {
        // Saltar filas completamente vacías
        $nonEmptyCells = array_filter($row, function($cell) {
            return $cell !== null && $cell !== '' && trim($cell) !== '';
        });
        
        if (empty($nonEmptyCells)) {
            continue;
        }

        // Asegurar que tenemos 6 columnas
        while (count($row) < 6) {
            $row[] = '';
        }

        // Obtener y limpiar datos
        $id_usuario = cleanValue($row[0]);
        $clave = cleanValue($row[1]);
        $nombres = cleanValue($row[2]);
        $apellidos = cleanValue($row[3]);
        $email = cleanValue($row[4]);
        $id_tipo_usuario = cleanValue($row[5]);

        // Validaciones
        if (empty($id_usuario)) {
            $errorCount++;
            $errors[] = "Fila " . ($rowIndex + 2) . ": ID de usuario vacío";
            continue;
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorCount++;
            $errors[] = "Fila " . ($rowIndex + 2) . ": Email inválido - '$email'";
            continue;
        }

        // Validar id_tipo_usuario
        if (empty($id_tipo_usuario)) {
            $errorCount++;
            $errors[] = "Fila " . ($rowIndex + 2) . ": ID tipo usuario está VACÍO. Valor recibido: '" . $row[5] . "'";
            continue;
        }

        try {
            // Verificar si el tipo de usuario existe
            $checkTypeStmt->execute([$id_tipo_usuario]);
            $tipoUsuarioExists = $checkTypeStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tipoUsuarioExists) {
                $availableTypes = getAvailableUserTypes($db);
                $errorCount++;
                $errors[] = "Fila " . ($rowIndex + 2) . ": ID tipo usuario '$id_tipo_usuario' NO EXISTE. Valores disponibles: $availableTypes";
                continue;
            }

            // Verificar si el usuario ya existe
            $checkUserStmt->execute([$id_usuario]);
            if ($checkUserStmt->fetch(PDO::FETCH_ASSOC)) {
                $errorCount++;
                $errors[] = "Fila " . ($rowIndex + 2) . ": Usuario '$id_usuario' ya existe";
                continue;
            }

            // Insertar en la base de datos
            if ($insertStmt->execute([$id_usuario, $clave, $nombres, $apellidos, $email, $id_tipo_usuario])) {
                $successCount++;
            } else {
                $errorCount++;
                $errors[] = "Fila " . ($rowIndex + 2) . ": Error al insertar en BD";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errors[] = "Fila " . ($rowIndex + 2) . ": Error BD - " . $e->getMessage();
        }
    }

    // Respuesta final
    $response = [
        'success' => true,
        'message' => "Importación completada: $successCount registros insertados, $errorCount errores",
        'stats' => [
            'successful' => $successCount,
            'errors' => $errorCount,
            'total' => $successCount + $errorCount
        ]
        
    ];

    if ($errorCount > 0) {
        $response['error_details'] = array_slice($errors, 0, 15);
    }

    ob_end_clean();
    echo json_encode($response);

} catch (Exception $e) {
    sendJsonResponse(false, 'Error general: ' . $e->getMessage());
}

// Función para limpiar valores
function cleanValue($value) {
    if (is_null($value)) return '';
    if (is_float($value) && $value == (int)$value) return (int)$value;
    if (is_float($value)) return (string)$value;
    if (is_bool($value)) return $value ? '1' : '0';
    
    $value = trim((string)$value);
    
    // Convertir números en texto a números
    if (is_numeric($value)) {
        return $value + 0;
    }
    
    return $value;
}

// Función para obtener tipos de usuario disponibles
function getAvailableUserTypes($db) {
    try {
        $stmt = $db->query("SELECT id_tipo_usuario FROM tipo_usuario");
        $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return implode(', ', $types);
    } catch (Exception $e) {
        return 'No se pudieron obtener los tipos';
    }
}
?>


  	<!-- Required meta tags -->
  	<meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  	<!-- Bootstrap CSS -->
  	<link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../plugins/font-awesome/css/font-awesome.min.css">

    <link rel="shortcut icon" href="../imagenes/Logo-fis.png">



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
</head>
<body>
<style>
    .btnBack{
        background: #6ff41cff;
        color: #ffff;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        width: 150px;
        height: 75px;
    }
    .btnBack:hover {
  background-color: #45a049; /* Un verde un poco más oscuro */
  box-shadow: 1px 2px 5px rgba(0,0,0,0.2); /* Añade una sombra suave */
}
    </style>
        <div class="d-grid gap-2">
                        <button type="action" class="btnBack" id="backButton">
                           <a href="index.php"> <i class="fas fa-upload"></i> REGRESAR 
                        </button></a>
        </div>
</body>
</html>