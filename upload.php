<?php
require 'vendor/autoload.php';
require '../conexion.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['archivo']['name'])) {

    $nombreArchivo = $_FILES['archivo']['name'];
    $rutaTemporal = $_FILES['archivo']['tmp_name'];

    try {
        $documento = IOFactory::load($rutaTemporal);
        $hoja = $documento->getActiveSheet();
        $filas = $hoja->toArray();

        $contador = 0;
        foreach ($filas as $index => $fila) {
            // Saltar la cabecera
            if ($index == 0) continue;

            $clave = $fila[0];
            $nombres = $fila[1];
            $apellidos = $fila[2];
            $email = $fila[3];
            $id_tipo_usuario = $fila[4];
            

            if (!empty($nombres) && !empty($email)) {
                $sql = "INSERT INTO usuarios (clave, nombres, apellidos, email, id_tipo_usuario)
                        VALUES ('$clave', '$nombres', '$apellidos', '$email', '$id_tipo_usuario')";
                $conn->query($sql);
                $contador++;
            }
        }

        echo "<h3>✅ Se cargaron $contador registros correctamente.</h3>";
        echo "<a href='index.php'>Volver</a>";

    } catch (Exception $e) {
        echo "Error al procesar el archivo: " . $e->getMessage();
    }
} else {
    echo "No se seleccionó ningún archivo.";
}
?>
