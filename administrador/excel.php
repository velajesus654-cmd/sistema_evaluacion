<!--/*
// config.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sistema_encuestas');

function conectarDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }
    $conn->set_charset("utf8");
    return $conn;
}

// Función para verificar si existe el tipo de usuario
function validarTipoUsuario($conn, $id_tipo_usuario) {
    $stmt = $conn->prepare("SELECT id_tipo_usuario FROM tipo_usuario WHERE id_tipo_usuario = ?");
    $stmt->bind_param("i", $id_tipo_usuario);
    $stmt->execute();
    $stmt->store_result();
    $existe = $stmt->num_rows > 0;
    $stmt->close();
    return $existe;
}

// Función mejorada para insertar usuario
function insertarUsuario($conn, $id_usuario, $clave, $nombres, $apellidos, $email, $id_tipo_usuario) {
    // Validar que el tipo de usuario existe
    if (!validarTipoUsuario($conn, $id_tipo_usuario)) {
        return false;
    }
    
    $sql = "INSERT INTO usuarios (id_usuario, clave, nombres, apellidos, email, id_tipo_usuario) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    
    $stmt->bind_param("issssi", $id_usuario, $clave, $nombres, $apellidos, $email, $id_tipo_usuario);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

// Función principal corregida
function exportarExcelAMysql($archivo_temporal) {
    $conn = conectarDB();
    
    // Primero, obtener los tipos de usuario válidos
    $tipos_validos = [];
    $result = $conn->query("SELECT id_tipo_usuario FROM tipo_usuario");
    while ($row = $result->fetch_assoc()) {
        $tipos_validos[] = $row['id_tipo_usuario'];
    }
    
    // Leer el archivo como texto plano (mejor para debugging)
    $contenido = file_get_contents($archivo_temporal);
    $lineas = explode(PHP_EOL, $contenido);
    
    $registros_insertados = 0;
    $errores = [];
    
    foreach ($lineas as $indice => $linea) {
        // Saltar primera línea (encabezados) y líneas vacías
        if ($indice === 0 || trim($linea) === '') continue;
        
        // Separar por comas
        $datos = str_getcsv($linea);
        
        // Validar que tenga exactamente 6 columnas
        if (count($datos) === 6) {
            // Limpiar y validar datos
            $id_usuario = intval(trim($datos[0]));
            $clave = trim($datos[1]);
            $nombres = trim($datos[2]);
            $apellidos = trim($datos[3]);
            $email = trim($datos[4]);
            $id_tipo_usuario = intval(trim($datos[5]));
            
            // Validaciones
            if ($id_usuario <= 0) {
                $errores[] = "Fila " . ($indice + 1) . ": ID usuario inválido";
                continue;
            }
            
            if (empty($clave)) {
                $errores[] = "Fila " . ($indice + 1) . ": Clave vacía";
                continue;
            }
            
            // Verificar si el tipo de usuario es válido
            if (!in_array($id_tipo_usuario, $tipos_validos)) {
                $errores[] = "Fila " . ($indice + 1) . ": Tipo de usuario $id_tipo_usuario no existe. Válidos: " . implode(', ', $tipos_validos);
                continue;
            }
            
            // Intentar insertar
            if (insertarUsuario($conn, $id_usuario, $clave, $nombres, $apellidos, $email, $id_tipo_usuario)) {
                $registros_insertados++;
            } else {
                $errores[] = "Fila " . ($indice + 1) . ": Error al insertar - " . $conn->error;
            }
        } else {
            $errores[] = "Fila " . ($indice + 1) . ": Número de columnas incorrecto (" . count($datos) . ")";
        }
    }
    
    $conn->close();
    
    return [
        'registros' => $registros_insertados,
        'errores' => $errores,
        'tipos_validos' => $tipos_validos
    ];
}

// HTML y procesamiento del formulario
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar Usuarios</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Importar Usuarios desde CSV</h1>
        
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="archivo" accept=".csv" required>
            <button type="submit" name="importar">Importar</button>
        </form>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
            if ($_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
                $resultado = exportarExcelAMysql($_FILES['archivo']['tmp_name']);
                
                echo "<div class='success'>Registros insertados: " . $resultado['registros'] . "</div>";
                
                if (!empty($resultado['tipos_validos'])) {
                    echo "<div class='info'>Tipos de usuario válidos: " . implode(', ', $resultado['tipos_validos']) . "</div>";
                }
                
                if (!empty($resultado['errores'])) {
                    echo "<div class='error'><strong>Errores:</strong><br>" . implode("<br>", $resultado['errores']) . "</div>";
                }
            }
        }
        ?>
        
        <div class="info">
            <h3>Formato requerido del CSV:</h3>
            <pre>
id_usuario,clave,nombres,apellidos,email,id_tipo_usuario
1,USR001,Juan,Pérez,juan@email.com,1
2,USR002,María,Gómez,maria@email.com,2
            </pre>
            <p><strong>Nota:</strong> Los tipos de usuario deben existir previamente en la tabla tipo_usuario</p>
        </div>
    </div>
</body>
</html>-->