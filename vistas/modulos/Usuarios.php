<?php
  $CrearVeterinario = new ControladorUsuarios();
  $CrearVeterinario -> ctrCrearUsuarios();
  
  $editarUsuario = new ControladorUsuarios();
  $editarUsuario -> ctrEditarUsuarios();
     
  /*$eliminarUsuario = new ControladorUsuarios();
  $eliminarUsuario -> crtEliminarUsuarios();*/

  $desabilitarUsuario = new ControladorUsuarios() ;
  $desabilitarUsuario -> crtDesHabilitarUsuarios();
      
?>
<div class="right_col" role="main">
    <div class="page-title">
              <div class="title_left">
                <h3>Usuarios <small>Ejemplos para no llenar de basura la base de datos</small></h3>
              </div>

              <div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <h6>Veterinarios</h6>
                   <button class="btn btn-primary" data-toggle="modal" data-target="#miModal">Agregar usuario</button>
                  </div>
                </div>
              </div>
            </div>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Usuarios <small></small></h2>
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
                        <!--th>Id</th-->
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Rol Usuario</th>
                        <th>Correo</th>
                        <th>Turno</th>
                        <th>Estado</th>
                        <th>Habilitados</th>
                        <th>Aciones</th>
                       
                    </tr>
                </thead>


                <tbody>
                <?php
                  $item=null;
                  $valor=null;
                  $Usuarios= ControladorUsuarios::ctrMostrarUsuarios($item, $valor);

                  foreach($Usuarios as $key=>$value)
                  {
                    echo'
                    <tr>
                      <!--td>
                        
                        <h6 class="mb-0 text-sm">'.$value["IDusuario"].'</h6>
                        
                      </td-->
                      

                      ';
                        if($value["foto"]!= "")
                        {
                            echo'<td><img src="'.Seguridad::escapar($value["foto"]).'" class="avatar avatar-sm me-3"></td>';

                        }
                        else
                        {
                          echo'<td><img src="Vistas/js/img/img.jpg" class="avatar avatar-sm me-3"></td>   ';

                        } 
                        echo '
                              
                        
                      <td>
                        
                          <h6 class="mb-0 text-sm">'.Seguridad::escapar($value["Usunombre"]).'</h6>
                          <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($value["nombre"]).' '.Seguridad::escapar($value["apellido"]).'</p>
                          <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($value["telefono"]).' </p>

                      </td> 
                      ';
                        echo '
                        <td>
                          <p class="text-xs text-secondary mb-0">';

                          switch($value["IDrol"])
                          {
                            case 1:
                              echo "Administrador";
                            break;
                            case 2:
                              echo "Veterinario";
                              break;
                            case 3:
                              echo "Recepcionista";
                              break;
                          }

                          echo '</p>
                        </td>

                      <td>
                          <p class="text-xs font-weight-bold mb-0">'.Seguridad::escapar($value["correo"]).'</p>
                      </td>
                      <td>
                        <p class="text-xs font-weight-bold mb-0">'.Seguridad::escapar($value["turno"]).'</p>
                      </td> 
                  
                      
                    ';
                      echo '
                      
                      <td>';
                        if($value["estado"]==1)
                        {
                          echo'<p class="align-middle text-center text-sm">En linea</p>';
                        }
                        else
                        {
                          echo'<p class="align-middle text-center text-sm">Desconectado</p>';
                        }
                       

                      echo '
                       
                      
                      <td>';
                        if($value["habilitado"]!=0)
                        {
                          echo'<p class="align-middle text-center text-sm">Habilitado</p>';
                        }
                        else
                        {
                          echo'<p class="align-middle text-center text-sm">No Habilitado</p>';
                        }
                       

                      echo '
                      </td> 
                      <td>
                        <div class="btn-group">
                          <button class="btn btn-warning btnEditUsuario" data-id="'.$value["IDusuario"].'" data-toggle="modal" data-target="#modUsuario"><i class="fa fa-pencil"></i></button>
                          <button class="btn btn-danger btnEliminarUsuario" IDusuario="'.$value["IDusuario"].'"><i class="fa fa-times"></i></button>';
                         
                        if($value["habilitado"] != 0){
                            
                            echo '<button class="btn btn-danger btnDesactivar " IDusuario="' . $value["IDusuario"] . '" ><i class="fa fa-trash"></i></button>';
                        } else {
                            
                            echo '<button class="btn btn-success btnActivar" IDusuario="' . $value["IDusuario"] . '"><i class="fa fa-refresh"></i></button>';
                        }
                        echo'
                        </div>
                      </td>   
                    </tr>';
                  }
                ?>
                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div id="miModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">

      <!-- CABECERA -->
      <div class="modal-header" style="background:#FFFFFF;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Agregar usuario</h4>
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
                <input type="text" class="form-control input-lg" name="nuevoNombre" placeholder="Ingresar nombre" required>
              </div>
            </div>
            <!--Apellido-->
            <div class="form-group"> 
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoApellido" placeholder="Ingresar Apellido" required>
              </div>
            </div>
            <!--Nombre usuario-->
            <div class="form-group"> 
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoUsuario" placeholder="Ingresar Nombre usuario" required>
              </div>
            </div>

            <!-- TELEFONO -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoTelefono" placeholder="Ingresar Telefono" required>
              </div>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                <input type="email" class="form-control input-lg" name="nuevoEmail" placeholder="Ingresar Correo" required>
              </div>
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                <input type="password" class="form-control input-lg" name="nuevoPassword" minlength="8" maxlength="72" placeholder="Ingresar contraseña (mínimo 8 caracteres)" required>
              </div>
            </div>

            <!-- HORARIO -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-users"></i></span>
                <select class="form-control input-lg" name="nuevoTurno">
                  <option value="" selected disabled hidden>Seleccionar Horario</option>
                  <option value="Mañana">Mañana</option>
                  <option value="Tarde">Tarde</option>
                  <option value="Horario continuo">Horario continuo</option>
                </select>
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
<div id="modUsuario" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">

      <!-- CABECERA -->
      <div class="modal-header" style="background:#FFFFFF;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Modificar usuario</h4>
      </div>

      <form role="form" method="post" enctype="multipart/form-data">
        <?php echo Seguridad::campoCsrf(); ?>

        <!-- CUERPO -->
        <div class="modal-body">

          <div class="box-body">
            <!--Idusuario para que funcione pues el inge tiene el modificar en read oln-->
            <input type="hidden" name="IDusuario" id="IDusuario">
            <!-- NOMBRE -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" id="editarNombre" name="editarNombre"  required>
              </div>
            </div>
            <!--Apellido-->
            <div class="form-group"> 
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" id="editarApellido" name="editarApellido"  required>
              </div>
            </div>
            <!--Nombre usuario-->
            <div class="form-group"> 
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" id="editarUsuario"name="editarUsuario" required>
              </div>
            </div>

            <!-- TELEFONO -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="text" class="form-control input-lg" id="editarTelefono" name="editarTelefono"  required>
              </div>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-envelope"></i></span>
                <input type="email" class="form-control input-lg"  id="editarEmail" name="editarEmail"  required>
              </div>
            </div>

            <!-- PASSWORD -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-lock"></i></span>
                <input type="password" class="form-control input-lg" id="editarPassword" name="editarPassword" minlength="8" maxlength="72" placeholder="Dejar vacío para conservar la contraseña">
              </div>
            </div>

            <!-- HORARIO -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-users"></i></span>
                <select class="form-control input-lg"  id="editarTurno" name="editarTurno">
                  <option value="" selected disabled hidden>Seleccionar Horario</option>
                  <option value="Mañana">Mañana</option>
                  <option value="Tarde">Tarde</option>
                  <option value="Horario continuo">Horario continuo</option>
                </select>
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
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>
       
       

      </form>

    </div>
  </div>
   
</div>

