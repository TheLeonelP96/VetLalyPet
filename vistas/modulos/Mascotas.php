<?php

  $CrearVeterinario = new ControladorMascotas();
  $CrearVeterinario -> ctrCrearMascotas();

  $ModificarMascota = new ControladorMascotas();
  $ModificarMascota -> ctrModificarMascotas();

  //$eliminarMascota = new ControladorMascotas();
  //$eliminarMascota -> crtEliminarMascotas();

  $desabilitarMascota= new ControladorMascotas();
  $desabilitarMascota ->crtDesHabilitarMascotas();

  $item = null;
  $valor = null;
  $clientes = ControladorClientes::crtMostrarClientes($item, $valor);
  
?>
<div class="right_col" role="main">
    <div class="page-title">
              <div class="title_left">
                <h3>Mascotas <small>Ejemplos :v</small></h3>
              </div>

              <div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <h6>Mascotas</h6>
                   <button class="btn btn-primary" data-toggle="modal" data-target="#modalMascota">Agregar Mascota</button>
                  </div>
                </div>
              </div>
            </div>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Listado<small>Mascotas</small></h2>
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
                <p class="text-muted font-13 m-b-30">
                
                </p>
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                <thead>
                    <tr>

                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Mascota</th>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Tipo de mascota</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Genero</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fehca de registro</th>
                      <th class="text-secondary opacity-7"></th>

                    </tr>
                </thead>


                <tbody>
               <?php
                  $item=null;
                  $valor=null;
                
                  $mascotas = ControladorMascotas::crtMostrarMascotas($item, $valor);
                
 
                  foreach( $mascotas as $key=>$ms)
                  {
                 
                   
                      echo'
                      <tr>
                          <td>
                            <div class="d-flex px-2 py-1">
                              <div>';
                                if($ms["fotoM"]!= "")
                                {
                                  echo'<img src="'.Seguridad::escapar($ms["fotoM"]).'" class="avatar avatar-sm me-3">';

                                }
                                else
                                {
                                  echo'<img src="Vistas/js/img/images.jpg" class="avatar avatar-sm me-3">   ';

                                } 
                                echo'
                              </div>
                              <div class="d-flex flex-column justify-content-center">
                                
                                <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($ms["nombrem"]).' </p>
                                <p class="text-xs text-secondary mb-0">
                                 Dueño: '.Seguridad::escapar($ms["nombrec"]).'
                                </p>
                                
                               
                              </div>
                            </div>
                          </td>
                          <td>
                            <p class="text-xs font-weight-bold mb-0">'.Seguridad::escapar($ms["especie"]).'</p>
                            <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($ms["raza"]).' tiene '.Seguridad::escapar($ms["edad"]).'</p>
                          </td>
                          <td class="align-middle text-center text-sm">
                            <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($ms["sexo"]).'</p>
                          </td>
                          <td class="align-middle text-center">
                            <span class="text-secondary text-xs font-weight-bold">'.Seguridad::escapar($ms["fecha_Registro"]).'</span>
                          </td>
                          <td class="align-middle">
                            <div class="btn-group">
                              <button class="btn btn-warning btnEditMascotas" dataid="'.$ms["IDmascota"].'" data-toggle="modal" data-target="#modMascota"><i class="fa fa-pencil"></i></button>';
                                  if($ms["habilitadoM"] != 0)
                                  {
                                    
                                    echo '<button class="btn btn-danger btnDesactivarM " IDmascota="' . $ms["IDmascota"] . '" ><i class="fa fa-trash"></i></button>';
                                  } else {
                                      
                                    echo '<button class="btn btn-success btnActivarM" IDmascota="' . $ms["IDmascota"] . '"><i class="fa fa-refresh"></i></button>';
                                  }
                       
                              echo'
                            </div>
                          </td>
                        </tr>
                      
                     ';
                    

                  }   
                ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="modalMascota" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">

      <!-- CABECERA -->
      <div class="modal-header" style="background:#FFFFFF;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Agregar Mascotas</h4>
      </div>

      <form role="form" method="post" enctype="multipart/form-data">
        <?php echo Seguridad::campoCsrf(); ?>

        <!-- CUERPO -->
        <div class="modal-body">

          <div class="box-body">
           
            <!-- NOMBRE -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoMascota" placeholder="Ingresar nombre" required>
              </div>
            </div>
            <!-- ESPECIE -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-users"></i></span>
                <select class="form-control input-lg" name="nuevoEspecie">
                  <option value="" selected disabled hidden>Seleccionar Especie</option>
                  <option value="Felino">Felino</option>
                  <option value="Canino">Canino</option>
                  <option value="Ave">Ave</option>
                  <option value="Reptil">Reptil</option>
                  <option value="Roedores">Roedores</option>
                  <option value="Lagomorfo">Lagomorfo</option> <!--dato curiosote los lagomorfos no son roedores-->
                  <option value="Mustelidos">Mustelidos</option>
                  <option value="Eulipotiplanos">Eulipotiplanos</option>

                </select>
              </div>
            </div>

            <!-- Raza -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-otter"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoRaza" placeholder="Ingresar raza" required>
              </div>
            </div>

            <!-- Fecha de registro -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-calendar"></i></span>
                <input type="date" class="form-control input-lg" name="nuevaFecha" placeholder="Ingresar Registro" required>
              </div>
            </div>

            <!-- Edad -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-paw"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoEdad" placeholder="Ingresar edad" required>
              </div>
            </div>
             <!--Sexo -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-dog"></i></span>
                <select class="form-control input-lg" name="nuevoSexo">
                    <option value="" selected disabled hidden>Seleccionar Genero</option>
                    <option value="Macho">Macho</option>
                    <option value="Hembra">Hembra</option>
                  </select>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <select class="form-control input-lg" name="nuevoCliente" required>
                  <option value="" selected disabled hidden>Seleccionar Dueño / Cliente</option>
                  <?php
                    foreach ($clientes as $key => $value) {
                      // $value["IDcliente"] guarda el ID físico para la FK
                      // $value["nombrec"] guarda el nombre que ve el recepcionista
                  echo '<option value="'.(int) $value["IDcliente"].'">'.Seguridad::escapar($value["nombrec"]).'</option>';
                    }
                  ?>
                </select>
              </div>
            </div>
             <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-weight-hanging"></i> </span>
                <input type="text" class="form-control input-lg" name="nuevoPeso" placeholder="Ingresar Peso" required>
              </div>
            </div>

            <!-- FOTO -->
            <div class="form-group">
              <div class="panel">SUBIR FOTO</div>
              <input type="file" class="nuevaFoto" name="nuevaFoto">
              <p class="help-block">Peso máximo de la foto 2MB</p>
              <img src="Vistas/dist/img/asada.jpg" class="img-thumbnail previsualizar" width="100px">
            </div>

          </div>

        </div>

        <!-- PIE -->
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar usuario</button>
        </div>

          

      </form>

    </div>
  </div>
   
</div>
<div id="modMascota" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">

      <!-- CABECERA -->
      <div class="modal-header" style="background:#FFFFFF;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Editar Mascotas</h4>
      </div>

      <form role="form" method="post" enctype="multipart/form-data">
        <?php echo Seguridad::campoCsrf(); ?>

        <!-- CUERPO -->
        <div class="modal-body">

          <div class="box-body">
           <input type="hidden" name="IDmascota" id="IDmascota">
            <!-- NOMBRE -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" id="editarMascota" name="editarMascota" placeholder="Ingresar nombre" required>
              </div>
            </div>
            <!-- ESPECIE -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-users"></i></span>
                <select class="form-control input-lg" id="editarEspecie" name="editarEspecie">
                  <option value="" selected disabled hidden>Seleccionar Especie</option>
                  <option value="Felino">Felino</option>
                  <option value="Canino">Canino</option>
                  <option value="Ave">Ave</option>
                  <option value="Reptil">Reptil</option>
                  <option value="Roedores">Roedores</option>
                  <option value="Lagomorfo">Lagomorfo</option> <!--dato curiosote los lagomorfos no son roedores-->
                  <option value="Mustelidos">Mustelidos</option>
                  <option value="Eulipotiplanos">Eulipotiplanos</option>

                </select>
              </div>
            </div>

            <!-- Raza -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-otter"></i></span>
                <input type="text" class="form-control input-lg" id="editarRaza" name="editarRaza" placeholder="Ingresar raza" required>
              </div>
            </div>

            <!-- Fecha de registro -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-calendar"></i></span>
                <input type="date" class="form-control input-lg" id="editarFecha" name="editarFecha" placeholder="Ingresar Registro" required>
              </div>
            </div>

            <!-- Edad -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-paw"></i></span>
                <input type="text" class="form-control input-lg" id="editarEdad" name="editarEdad"placeholder="Ingresar edad" required>
              </div>
            </div>
             <!--Sexo -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-dog"></i></span>
                <select class="form-control input-lg" id="editarSexo" name="editarSexo">
                    <option value="" selected disabled hidden>Seleccionar Genero</option>
                    <option value="Macho">Macho</option>
                    <option value="Hembra">Hembra</option>
                  </select>
              </div>
            </div>
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <select class="form-control input-lg" id="editarCliente" name="editarCliente" required>
                  <option value="" selected disabled hidden>Seleccionar Dueño / Cliente</option>
                  <?php
                    foreach ($clientes as $key => $value) {
                      // $value["IDcliente"] guarda el ID físico para la FK
                      // $value["nombrec"] guarda el nombre que ve el recepcionista
                  echo '<option value="'.(int) $value["IDcliente"].'">'.Seguridad::escapar($value["nombrec"]).'</option>';
                    }
                  ?>
                </select>
              </div>
            </div>
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-weight-hanging"></i> </span>
                <input type="text" class="form-control input-lg" id="editarPeso" name="editarPeso" placeholder="Ingresar Peso" required>
              </div>
            </div>

            <!-- FOTO -->
            <div class="form-group">
              <div class="panel">SUBIR FOTO</div>
               <input type="file" class="nuevaFoto" id="editarfoto" name="editarFoto">
              <input type="hidden" name="fotoActual" id="fotoActual">
              <p class="help-block">Peso máximo de la foto 2MB</p>
              <img src="Vistas/dist/img/asada.jpg" class="img-thumbnail previsualizar" width="100px">
            </div>

          </div>

        </div>

        <!-- PIE -->
        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>


      </form>


    </div>
  </div>
   
</div>

