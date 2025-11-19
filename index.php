<?php 

  date_default_timezone_set("America/Mexico_City");
  $date = new DateTime();

  $fecha_inicio = $date->format('Y-m-d H:i:s');
  
?>


<!DOCTYPE html>
<html lang="es">
<head>




  	<!-- Required meta tags -->
  	<meta charset="utf-8">
  	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  	<!-- Bootstrap CSS -->
  	<link rel="stylesheet" href="../css/bootstrap.min.css">
    <!-- Favicon - FIS -->

    <link rel="stylesheet" href="../css/main.css">

    <link rel="stylesheet" href="../plugins/font-awesome/css/font-awesome.min.css">

    <link rel="shortcut icon" href="../imagenes/Logo-fis.png">

  	<title>ADMININISTRADOR</title>

    <script type="text/javascript" language="javascript">   
      history.pushState(null, null, location.href);
      window.onpopstate = function () {
        history.go(1);
      };
    </script>

</head>
<body>

<?php
    require '../navbar.php';
?>

	<!-- Content Section -->
	<div class="container" style="margin-top: 30px;">
	    <div class="row">
	        <div class="col-md-12 row">
	        	<div class="col-md-10 col-xs-12">
	        		<h3>SISTEMA DE ENCUESTAS</h3>
	        	</div>
            <br>
            <br>
	        	<div class="col-12">
	        		 <button class="btn btn-success col-12" id="boton_agregar">
	                    <b>Agregar Encuesta</b>
	                </button>
	        	</div>
	        </div>
	    </div>
	    <hr/>
	    <div class="row">
	        <div class="col-md-12">
	            <h4>Encuestas:</h4>
	            <div class="table-responsive">
	            	<div id="tabla_encuestas"></div>
	            </div>
	        </div>
	    </div>
	</div>
  


	<!-- /Content Section -->


  <!-- Optional JavaScript -->
  <!-- jQuery first, then Popper.js, then Bootstrap JS -->
  <script src="../js/jquery-3.3.1.min.js"></script>
  <script src="../js/popper.min.js"></script>
  <script src="../js/bootstrap.min.js"></script>
  <script src="js/encuestas.js"></script>

</body>
</html>

<!-- Modal Agregar Nueva Encuesta -->
<div class="modal fade" id="modal_agregar" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
            	<h4 class="modal-title">Agregar Nueva Encuesta</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

            	<div class="form-group row">
      					<label for="titulo" class="col-sm-3 col-form-label">Título</label>
      					<div class="col-sm-9">
      						<input type="text" class="form-control" id="titulo" placeholder="Título" autocomplete="off" autofocus>
      					</div>
      				</div>
              <div class="form-group row">
                <label for="descripcion" class="col-sm-3 col-form-label">Descripción</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="descripcion" placeholder="Descripción"></textarea>
                </div>
              </div>
              <div class="form-group row">
                <label for="fecha_final" class="col-sm-3 col-form-label">Fecha Final</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="fecha_final" value="<?php echo $fecha_inicio ?>"  autocomplete="off">
                  <p>Fomato: año-mes-día horas:minutos:segundos</p>
                </div>
              </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="agregarEncuesta()">Agregar Encuesta</button>
                <input type="hidden" id="hidden_id_usuario" value="<?php echo $_SESSION['id_usuario'] ?>">
            </div>

        </div>
    </div>
</div>

<!-- Modal Modificar Producto -->
<div class="modal fade " id="modal_modificar" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
            	<h4 class="modal-title">Modificar Producto</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

            	<div class="form-group row">
      					<label for="modificar_titulo" class="col-sm-3 col-form-label">Título</label>
      					<div class="col-sm-9">
      						<input type="text" class="form-control" id="modificar_titulo" placeholder="Título">
      					</div>
      				</div>

              <div class="form-group row">
                <label for="descripcion" class="col-sm-3 col-form-label">Descripción</label>
                <div class="col-sm-9">
                  <textarea class="form-control" id="modificar_descripcion" placeholder="Descripción"></textarea>
                </div>
              </div>

              <div class="form-group row">
                <label for="fecha_final" class="col-sm-3 col-form-label">Fecha Final</label>
                <div class="col-sm-9">
                  <input type="text" class="form-control" id="modificar_fecha_final" placeholder="Fecha de Cierre" autocomplete="off"
                  value="<?php echo $fecha_inicio ?>">
                  <p>Fomato: año-mes-día horas:minutos:segundos</p>
                </div>
              </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="modificarDetallesEncuesta()">Modificar Encuesta</button>
                <input type="hidden" id="hidden_id_encuesta">
            </div>

        </div>
    </div>
</div>

<center>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Importar Datos desde Excel</h4>
            </div>
            <div class="card-body">
                <form action="importar_excel.php" method="post" enctype="multipart/form-data" id="importForm">
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Seleccionar archivo Excel:</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                        <div class="form-text">
                            Formatos aceptados: .xlsx, .xls
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estructura esperada del Excel:</label>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Columna A</th>
                                        <th>Columna B</th>
                                        <th>Columna C</th>
                                        <th>Columna D</th>
                                        <th>Columna E</th>
                                        <th>Columna F</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>id_usuario</td>
                                        <td>clave</td>
                                        <td>nombres</td>
                                        <td>apellidos</td>
                                        <td>email</td>
                                        <td>id_tipo_usuario</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="importBtn">
                            <i class="fas fa-upload"></i> Importar Datos
                        </button>
                    </div>
                </form>

                <!-- Área para mostrar resultados -->
                <div id="result" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-fontawesome-kit.js"></script>
    
    <script>
        document.getElementById('importForm').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('excel_file');
            const importBtn = document.getElementById('importBtn');
            const resultDiv = document.getElementById('result');
            
            if (fileInput.files.length === 0) {
                e.preventDefault();
                resultDiv.innerHTML = '<div class="alert alert-warning">Por favor selecciona un archivo Excel.</div>';
                return;
            }

            // Cambiar texto del botón durante la importación
            importBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importando...';
            importBtn.disabled = true;
        });
    </script>

</center>
</html>
