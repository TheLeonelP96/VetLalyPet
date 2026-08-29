<?php
  $CrearCliente = new ControladorClientes();
  $CrearCliente -> crtCrearCliente();

  $modificarCliente = new ControladorClientes();
  $modificarCliente -> ctrModificarClientes();

  $eliminarClientes = new ControladorClientes();
  $eliminarClientes ->crtEliminarClientes ();
?>
<div class="right_col" role="main">
    <div class="page-title">
              <div class="title_left">
                <h3>Clientes <small>Ejemplos</small></h3>
              </div>

              <div class="title_right">
                <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                  <div class="input-group">
                    <h6>Cliente</h6>
                   <button class="btn btn-primary" data-toggle="modal" data-target="#modalCliente">Agregar Cliente</button>
                  </div>
                </div>
              </div>
            </div>
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title">
                <h2>Listado <small>Clientes</small></h2>
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
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">ID</th>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Cliente</th>
                      <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Telefono cliente</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Registro</th>
                      <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Modificables</th>
                    </tr>
                </thead>


                <tbody>
               <?php
                  $item=null;
                  $valor=null;
                
                  $clientes = ControladorClientes::crtMostrarClientes($item, $valor);
                
 
                  foreach( $clientes as $key=>$cl)
                  {
                 
                   
                      echo'
                      <tr>
                            <td>
                                <div>
                                    <h6 class="mb-0 text-sm">'.$cl["IDcliente"].'</h6>
                                </div>
                            </td>
                          <td>
                            
                            <div class="d-flex px-2 py-1">
                              
                              <div class="d-flex flex-column justify-content-center">
                                
                              <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($cl["nombrec"]).' </p>
                              <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($cl["apellidoc"]).' </p>
                              </div>
                            </div>
                          </td>
                          <td>
                            <p class="text-xs font-weight-bold mb-0">'.Seguridad::escapar($cl["telefono"]).'</p>
                         
                          </td>
                          <td>
                            
                            <p class="text-xs text-secondary mb-0">'.Seguridad::escapar($cl["registro"]).'</p>
                          </td>
                          <td class="align-middle">
                            <div class="btn-group">
                              <button class="btn btn-warning btnEditClientes" datid="'.$cl["IDcliente"].'" data-toggle="modal" data-target="#modCliente"><i class="fa fa-pencil"></i></button>
                              <button class="btn btn-danger btnEliminarCliente" IDcliente="'.$cl["IDcliente"].'"><i class="fa fa-times"></i></button>
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
<div id="modalCliente" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
          <form role="form" method="post">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header" style="background:#FFFFFF;">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <h4 class="modal-title">Agregar Cliente</h4>
        </div>

        <div class="modal-body">
          <div class="box-body">
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" id="nuevoCliente" name="nuevoCliente" placeholder="Ingresar nombre" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-font"></i></span>
                <input type="text" class="form-control input-lg" id="nuevoApellidoc" name="nuevoApellidoc" placeholder="Ingresar apellido" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                <input type="text" class="form-control input-lg" name="nuevoTelefono" placeholder="Ingresar teléfono" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                <input type="date" class="form-control input-lg" name="nuevoRegistro" required>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar Cliente</button>
        </div>

      </form>
    </div>
  </div>
</div>
<div id="modCliente" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <div class="modal-content">

      <!-- CABECERA -->
      <div class="modal-header" style="background:#FFFFFF;">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Modificar Clientes</h4>
      </div>

      <form role="form" method="post" enctype="multipart/form-data">
        <?php echo Seguridad::campoCsrf(); ?>

        <!-- CUERPO -->
        <div class="modal-body">

          <div class="box-body">
           <input type="hidden" name="IDcliente" id="IDcliente">
            <!-- NOMBRE -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control input-lg" id="editarCliente" name="editarCliente" placeholder="Ingresar nombre" required>
              </div>
            </div>
           

            <!-- Raza -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-otter"></i></span>
                <input type="text" class="form-control input-lg" id="editarApellidoc" name="editarApellidoc" placeholder="Ingresar Apellido" required>
              </div>
            </div>

            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-otter"></i></span>
                <input type="text" class="form-control input-lg" id="editarTelefono" name="editarTelefono" placeholder="Ingresar num.telefonico" required>
              </div>
            </div>

            <!-- Fecha de registro -->
            <div class="form-group">
              <div class="input-group">
                <span class="input-group-addon"><i class="fa-solid fa-calendar"></i></span>
                <input type="date" class="form-control input-lg" id="editarRegistro" name="editarRegistro" placeholder="Ingresar Registro" required>
              </div>
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

