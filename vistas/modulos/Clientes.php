<?php
$clientes = ControladorClientes::crtMostrarClientes();
$flash = $_SESSION['cliente_flash'] ?? null;
unset($_SESSION['cliente_flash']);
?>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Clientes <small>Propietarios de mascotas</small></h3></div>
        <div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalCliente">
                <i class="fa fa-plus"></i> Agregar cliente
            </button>
        </div>
    </div>
    <div class="clearfix"></div>

    <?php if (is_array($flash)): ?>
        <div class="alert alert-<?php echo Seguridad::escapar($flash['tipo']); ?> alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo Seguridad::escapar($flash['mensaje']); ?>
        </div>
    <?php endif; ?>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title"><h2>Clientes registrados</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead><tr>
                        <th>Nombre completo</th><th>Teléfono</th><th>Fecha de registro</th>
                        <th>Estado</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($clientes as $cliente): ?>
                        <?php $habilitado = (int) $cliente['habilitadoC'] === 1; ?>
                        <tr>
                            <td><?php echo Seguridad::escapar($cliente['nombrec'] . ' ' . $cliente['apellidoc']); ?></td>
                            <td><?php echo Seguridad::escapar($cliente['telefono_c']); ?></td>
                            <td><?php echo Seguridad::escapar($cliente['registro']); ?></td>
                            <td>
                                <span class="label label-<?php echo $habilitado ? 'success' : 'default'; ?>">
                                    <?php echo $habilitado ? 'Habilitado' : 'Deshabilitado'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning btnEditCliente"
                                            data-id="<?php echo (int) $cliente['IDcliente']; ?>"
                                            title="Editar">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button type="button"
                                            class="btn <?php echo $habilitado ? 'btn-danger' : 'btn-success'; ?> btnEstadoCliente"
                                            data-id="<?php echo (int) $cliente['IDcliente']; ?>"
                                            data-estado="<?php echo $habilitado ? 0 : 1; ?>"
                                            title="<?php echo $habilitado ? 'Deshabilitar' : 'Habilitar'; ?>">
                                        <i class="fa <?php echo $habilitado ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="modalCliente" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Clientes">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Agregar cliente</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><input type="text" class="form-control input-lg" name="nuevoCliente" maxlength="50" placeholder="Nombre" required></div>
                <div class="form-group"><input type="text" class="form-control input-lg" name="nuevoApellidoc" maxlength="50" placeholder="Apellido" required></div>
                <div class="form-group"><input type="tel" class="form-control input-lg" name="nuevoTelefono" maxlength="20" placeholder="Teléfono" required></div>
                <div class="form-group">
                    <label>Fecha de registro</label>
                    <input type="date" class="form-control input-lg" name="nuevoRegistro"
                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar cliente</button>
            </div>
        </form>
    </div></div>
</div>

<div id="modCliente" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Clientes">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="IDcliente" id="IDcliente">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Modificar cliente</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><input type="text" class="form-control input-lg" id="editarCliente" name="editarCliente" maxlength="50" placeholder="Nombre" required></div>
                <div class="form-group"><input type="text" class="form-control input-lg" id="editarApellidoc" name="editarApellidoc" maxlength="50" placeholder="Apellido" required></div>
                <div class="form-group"><input type="tel" class="form-control input-lg" id="editarTelefonoCliente" name="editarTelefono" maxlength="20" placeholder="Teléfono" required></div>
                <div class="form-group">
                    <label>Fecha de registro</label>
                    <input type="date" class="form-control input-lg" id="editarRegistro" name="editarRegistro"
                           max="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div></div>
</div>
