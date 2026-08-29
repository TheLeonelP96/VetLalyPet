<?php
 $idRolActual = $_SESSION["IDrol"];

// Le pasamos el rol al controlador para que decida qué traer
  $citas = ControladorCitas::ctrMostrarCitas();

  $mascotas = ControladorMascotas::crtMostrarMascotas(null, null);
  $clientes = ControladorClientes::crtMostrarClientes(null, null);
  $usuarios = ControladorUsuarios::ctrMostrarUsuarios(null, null);

  

?>
<script>
  // 2. Pasamos el arreglo de PHP a un objeto JSON real de JavaScript
  window.citasDelServidor = <?php echo json_encode($citas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<div class="right_col" role="main">
  <?php if (!empty($_SESSION['cita_flash'])): ?>
    <?php $flashCita = $_SESSION['cita_flash']; unset($_SESSION['cita_flash']); ?>
    <div class="alert alert-<?php echo Seguridad::escapar($flashCita['tipo']); ?> alert-dismissible fade in" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      <?php echo Seguridad::escapar($flashCita['mensaje']); ?>
    </div>
  <?php endif; ?>
  <div class="">
    <div class="page-title">
      <div class="title_left">
        <h3>Calendario <small></small></h3>
      </div>

      <div class="title_right">
        <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
          <div class="input-group">
            <input type="text" class="form-control" placeholder="Buscar cita...">
            <span class="input-group-btn">
              <button class="btn btn-default" type="button">Go!</button>
            </span>
          </div>
        </div>
      </div>
    </div>

    <div class="clearfix"></div>

    <div class="row">
      <div class="col-md-12">
        <div class="x_panel">
          <div class="x_title">
            <h2>Listado<small>Citas</small></h2>
            <ul class="nav navbar-right panel_toolbox">
              <li><a class="collapse-link"><i class="fa fa-chevron-up"></i></a>
              </li>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false"><i class="fa fa-wrench"></i></a>
                <ul class="dropdown-menu" role="menu">
                  <li><a href="#">Settings 1</a>
                  </li>
                  <li><a href="#">Settings 2</a>
                  </li>
                </ul>
              </li>
              <li><a class="close-link"><i class="fa fa-close"></i></a>
              </li>
            </ul>
            <div class="clearfix"></div>
          </div>
          <div class="x_content">

            <div id="calendar"></div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<div id="modalAgregarCita" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">

              <form role="form" method="post" id="formAgregarCita">
              <?php echo Seguridad::campoCsrf(); ?>

        <div class="modal-header" style="background:#3c8dbc; color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Programar Nueva Cita</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">

            <input type="hidden" id="fechaCitaCrear" name="fechaCitaCrear"> 
            <div class="form-group">
              <label>Motivo de la Cita</label>
              <input type="text" class="form-control input-lg" name="nuevoMotivo" placeholder="Ej: Consulta General" required>
            </div>

          <div class="form-group">
            <label>Hora de la Cita</label>
            <input type="time" class="form-control input-lg" name="seleccionarHora" id="seleccionarHora" 
                  min="07:00" max="18:30" step="1800" required>
            <small id="disponibilidadNueva" class="help-block"></small>
          </div>

            <!-- Seleccionar Cliente -->
            <div class="form-group">
              <label>Seleccionar Propietario / Cliente</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <select class="form-control input-lg" id="seleccionarCliente" name="seleccionarCliente" required>
                  <option value="" selected disabled hidden>Seleccione el cliente</option>
                  <?php
                    foreach ($clientes as $key => $value) {
                  echo '<option value="'.(int) $value["IDcliente"].'">'.Seguridad::escapar($value["nombrec"]).'</option>';
                    }
                  ?>
                </select>
              </div>
            </div>

            <!-- Seleccionar Mascota (Inicia deshabilitado o vacío) -->
            <div class="form-group">
              <label>Seleccionar Mascota</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-paw"></i></span>
                <select class="form-control input-lg" id="seleccionarMascota" name="seleccionarMascota" required>
                  <option value="" selected disabled hidden>Seleccione primero un cliente</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label>Atendido por (Usuario)</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-users"></i></span>
                <select class="form-control input-lg" id="nuevoUsuario" name="nuevoUsuario" required>
                  <option value="">Seleccione el usuario</option>
                  <?php
                  foreach ($usuarios as $key => $value) {
                    if ($value["IDrol"] == 2) 
                    {
            
                    echo '<option value="'.(int) $value["IDusuario"].'">'.Seguridad::escapar($value["nombre"]).'</option>';
                      
                    }
                  }
                  ?>
                </select>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          <button type="submit" class="btn btn-primary" id="btnGuardarCita">Guardar Cita</button>
        </div>

      </form>

    </div>
  </div>
</div>
<div id="modalEditarCita" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">

            <form role="form" method="post" id="formEditarCita">
              <?php echo Seguridad::campoCsrf(); ?>

        <div class="modal-header" style="background:#f39c12; color:white">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Editar o Reprogramar Cita</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">

            <input type="hidden" id="idCitaEditar" name="idCitaEditar">
            
            <div class="form-group">
              <label>Motivo de la Cita</label>
              <input type="text" class="form-control input-lg" id="editarMotivo" name="editarMotivo" required>
            </div>

            <div class="form-group col-md-6 col-sm-6 col-xs-12">
              <label>Nueva Hora</label>
              <input type="time" class="form-control input-lg" id="editarHora" name="editarHora" 
               min="07:00" max="18:30" step="1800" required>
              <small id="disponibilidadEdicion" class="help-block"></small>
            </div>
            <div class="form-group col-md-6 col-sm-6 col-xs-12">
              <label>Modificar Fecha (Día)</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                <input type="date" class="form-control input-lg" id="fechaCitaEditar" name="fechaCitaEditar" required>
              </div>
            </div>
            <div class="form-group">
              <label>Propietario / Cliente</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <select class="form-control input-lg" id="editarCliente" name="editarCliente" required>
                  <option value="" selected disabled hidden>Seleccione el cliente</option>
                  <?php
                  foreach ($clientes as $key => $value) {
                  echo '<option value="'.(int) $value["IDcliente"].'">'.Seguridad::escapar($value["nombrec"]).'</option>';
                  }
                  ?>
                </select>
              </div>
            </div>


            <div class="form-group">
              <label>Mascota</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-paw"></i></span>
                <select class="form-control input-lg" id="editarMascotaSelect" name="editarMascota" required>
                  <option value="" selected disabled hidden>Seleccione la mascota</option>
                 
                </select>
              </div>
            </div>
            <!-- SELECT DE MASCOTA EN EL MODAL DE EDITAR -->


           
            <div class="form-group">
              <label>Veterinario Asignado</label>
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user-md"></i></span>
                <select class="form-control input-lg" id="editarUsuario" name="editarUsuario" required>
                  <option value="" selected disabled hidden>Seleccione el veterinario</option>
                  <?php
                  foreach ($usuarios as $key => $value) {
                    if ( $value["IDrol"] == 2) {
                      echo '<option value="'.(int) $value["IDusuario"].'">'.Seguridad::escapar($value["nombre"]).'</option>';
                    }
                  }
                  ?>
                </select>
              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
          
          <button type="submit" id="btnAccionAlterna" class="btn" name="btnAccionCita" value="cancelar" formnovalidate></button>
          
          <button type="submit" id="btnCompletarCita" class="btn btn-success" name="btnAccionCita" value="completar"><i class="fa fa-check"></i> Completar Cita</button>
          <button type="submit" class="btn btn-warning" id="btnGuardarCambios" name="btnAccionCita" value="guardar">Guardar Cambios</button>
        </div>

      </form>

    </div>
  </div>
</div>
