<?php
$servicios = ControladorServicios::ctrMostrarServicios();
$flash = $_SESSION['servicio_flash'] ?? null;
$administraServicios = (int) ($_SESSION['IDrol'] ?? 0) === 1;
unset($_SESSION['servicio_flash']);
?>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Servicios <small>Catálogo y precios</small></h3></div>
        <?php if ($administraServicios): ?><div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalServicio">
                <i class="fa fa-plus"></i> Agregar servicio
            </button>
        </div><?php endif; ?>
    </div>
    <div class="clearfix"></div>

    <?php if (is_array($flash)): ?>
        <div class="app-flash alert alert-<?php echo Seguridad::escapar($flash['tipo']); ?> alert-dismissible" data-tipo="<?php echo Seguridad::escapar($flash['tipo']); ?>" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo Seguridad::escapar($flash['mensaje']); ?>
        </div>
    <?php endif; ?>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title"><h2>Servicios registrados</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead><tr><th>Servicio</th><th>Descripción</th><th>Precio</th><th>Duración</th><th>Estado</th><th>Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($servicios as $servicio): ?>
                        <?php
                        $habilitado = (int) $servicio['estadoServicio'] === 1;
                        $esVacunacion = $servicio['tipoServicio'] === 'Vacunación';
                        ?>
                        <tr>
                            <td><?php echo Seguridad::escapar($servicio['tipoServicio']); ?></td>
                            <td><?php echo Seguridad::escapar($servicio['descripcion']); ?></td>
                            <td>
                                <?php if ($esVacunacion): ?>
                                    <span class="label label-info">Se calcula por vacunas aplicadas</span>
                                <?php else: ?>
                                    Bs <?php echo number_format((float) $servicio['precio'], 2, '.', ','); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int) $servicio['duracionMinutos']; ?> minutos</td>
                            <td><span class="label label-<?php echo $habilitado ? 'success' : 'default'; ?>"><?php echo $habilitado ? 'Habilitado' : 'Deshabilitado'; ?></span></td>
                            <td><?php if ($administraServicios): ?><div class="btn-group">
                                <button type="button" class="btn btn-warning btnEditServicio" data-id="<?php echo (int) $servicio['IDservicio']; ?>" title="Editar"><i class="fa fa-pencil"></i></button>
                                <button type="button" class="btn <?php echo $habilitado ? 'btn-danger' : 'btn-success'; ?> btnEstadoServicio"
                                        data-id="<?php echo (int) $servicio['IDservicio']; ?>"
                                        data-estado="<?php echo $habilitado ? 0 : 1; ?>"
                                        title="<?php echo $habilitado ? 'Deshabilitar' : 'Habilitar'; ?>">
                                    <i class="fa <?php echo $habilitado ? 'fa-ban' : 'fa-check'; ?>"></i>
                                </button>
                            </div><?php else: ?><span class="text-muted">Solo consulta</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($administraServicios): ?><div id="modalServicio" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="Servicios">
                <?php echo Seguridad::campoCsrf(); ?>
                <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Agregar servicio</h4></div>
                <div class="modal-body"><div class="box-body">
                    <div class="form-group">
                        <label>Nombre del servicio</label><input type="text" class="form-control input-lg" name="nuevoServicio" maxlength="50" required>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label><textarea class="form-control input-lg" name="nuevaDescripcionServicio" maxlength="255" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Precio (Bs)</label><input type="number" class="form-control input-lg" name="nuevoPrecioServicio" min="0" max="99999999.99" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Duración estimada (minutos)</label><input type="number" class="form-control input-lg" name="nuevaDuracionServicio" min="5" max="480" step="5" value="30" required>
                    </div>
                </div></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div id="modServicio" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="Servicios">
                <?php echo Seguridad::campoCsrf(); ?>
                <input type="hidden" name="IDservicio" id="IDservicio">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Modificar servicio</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <label>Nombre del servicio</label>
                            <input type="text" class="form-control input-lg" id="editarServicio" name="editarServicio" maxlength="50" required>
                            <small id="ayudaServicioProtegido" class="help-block" style="display:none">El nombre de Vacunación se conserva porque identifica el flujo de dosis y pagos.</small>
                        </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea class="form-control input-lg" id="editarDescripcionServicio" name="editarDescripcionServicio" maxlength="255" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Precio (Bs)</label>
                        <input type="number" class="form-control input-lg" id="editarPrecioServicio" name="editarPrecioServicio" min="0" max="99999999.99" step="0.01" required>
                        <small id="ayudaPrecioVacunacion" class="help-block" style="display:none">El total se obtiene de las vacunas registradas en el historial.</small>
                    </div>
                    <div class="form-group">
                        <label>Duración estimada (minutos)</label>
                        <input type="number" class="form-control input-lg" id="editarDuracionServicio" name="editarDuracionServicio" min="5" max="480" step="5" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
