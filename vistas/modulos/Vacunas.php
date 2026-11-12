<?php
$vacunas = ControladorVacunas::ctrMostrarVacunas();
$especies = ControladorVacunas::ctrListarEspecies();
$flash = $_SESSION['vacuna_flash'] ?? null;
$administraVacunas = (int) ($_SESSION['IDrol'] ?? 0) === 1;
unset($_SESSION['vacuna_flash']);
?>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Vacunas <small>Compatibilidad por especie</small></h3></div>
        <?php if ($administraVacunas): ?><div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalVacuna"
                    <?php echo $especies === [] ? 'disabled' : ''; ?>>
                <i class="fa fa-plus"></i> Agregar vacuna
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

    <?php if ($especies === []): ?>
        <div class="alert alert-warning">Primero debe registrar al menos una especie.</div>
    <?php endif; ?>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title"><h2>Vacunas registradas</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead><tr>
                        <th>Vacuna</th>
                        <th>Descripción</th>
                        <th>Especie compatible</th>
                        <th>Precio</th>
                        <th>Stock</th><th>Stock mínimo</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($vacunas as $vacuna): ?>
                        <?php
                        $habilitada = (int) $vacuna['estadoVac'] === 1;
                        $stock = (int) $vacuna['stock'];
                        ?>
                        <tr>
                            <td><?php echo Seguridad::escapar($vacuna['nombreVac']); ?></td>
                            <td><?php echo Seguridad::escapar($vacuna['Descripcion']); ?></td>
                            <td><span class="label label-info"><?php echo Seguridad::escapar($vacuna['nombre_Especie']); ?></span></td>
                            <td>Bs <?php echo number_format((float) $vacuna['preciovac'], 2, '.', ','); ?></td>
                            <td>
                                <span class="label label-<?php echo $stock === 0 ? 'danger' : ($stock <= (int) $vacuna['stockMinimo'] ? 'warning' : 'success'); ?>">
                                    <?php echo $stock; ?>
                                </span>
                            </td>
                            <td><?php echo (int) $vacuna['stockMinimo']; ?></td>
                            <td><?php if ($administraVacunas): ?>
                                <span class="label label-<?php echo $habilitada ? 'success' : 'default'; ?>">
                                    <?php echo $habilitada ? 'Habilitada' : 'Deshabilitada'; ?>
                                </span>
                            <?php else: ?><span class="text-muted">Solo consulta</span><?php endif; ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning btnEditVacuna"
                                            data-id="<?php echo (int) $vacuna['IDvacuna']; ?>" title="Editar">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <button type="button"
                                            class="btn <?php echo $habilitada ? 'btn-danger' : 'btn-success'; ?> btnEstadoVacuna"
                                            data-id="<?php echo (int) $vacuna['IDvacuna']; ?>"
                                            data-estado="<?php echo $habilitada ? 0 : 1; ?>"
                                            title="<?php echo $habilitada ? 'Deshabilitar' : 'Habilitar'; ?>">
                                        <i class="fa <?php echo $habilitada ? 'fa-ban' : 'fa-check'; ?>"></i>
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

<?php if ($administraVacunas): ?><div id="modalVacuna" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Vacunas">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Agregar vacuna</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><label>Nombre</label><input type="text" class="form-control input-lg" name="nuevaVacuna" maxlength="50" required></div>
                <div class="form-group"><label>Descripción</label><textarea class="form-control input-lg" name="nuevaDescripcionVacuna" maxlength="255" rows="3" required></textarea></div>
                <div class="form-group"><label>Especie compatible</label><select class="form-control input-lg" name="nuevaEspecieVacuna" required>
                    <option value="">Seleccione una especie</option>
                    <?php foreach ($especies as $especie): ?>
                        <option value="<?php echo (int) $especie['IDespecie']; ?>"><?php echo Seguridad::escapar($especie['nombre_Especie']); ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="form-group"><label>Precio (Bs)</label><input type="number" class="form-control input-lg" name="nuevoPrecioVacuna" min="0" max="99999999.99" step="0.01" required></div>
                <div class="form-group"><label>Stock</label><input type="number" class="form-control input-lg" name="nuevoStockVacuna" min="0" step="1" required></div>
                <div class="form-group"><label>Stock mínimo para alerta</label><input type="number" class="form-control input-lg" name="nuevoStockMinimo" min="0" step="1" value="5" required><small class="help-block">Se mostrará una alerta cuando las existencias sean iguales o menores a este valor.</small></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar vacuna</button>
            </div>
        </form>
    </div></div>
</div>

<div id="modVacuna" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Vacunas">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="IDvacuna" id="IDvacuna">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Modificar vacuna</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><label>Nombre</label><input type="text" class="form-control input-lg" id="editarVacuna" name="editarVacuna" maxlength="50" required></div>
                <div class="form-group"><label>Descripción</label><textarea class="form-control input-lg" id="editarDescripcionVacuna" name="editarDescripcionVacuna" maxlength="255" rows="3" required></textarea></div>
                <div class="form-group"><label>Especie compatible</label><select class="form-control input-lg" id="editarEspecieVacuna" name="editarEspecieVacuna" required>
                    <option value="">Seleccione una especie</option>
                    <?php foreach ($especies as $especie): ?>
                        <option value="<?php echo (int) $especie['IDespecie']; ?>"><?php echo Seguridad::escapar($especie['nombre_Especie']); ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="form-group"><label>Precio (Bs)</label><input type="number" class="form-control input-lg" id="editarPrecioVacuna" name="editarPrecioVacuna" min="0" max="99999999.99" step="0.01" required></div>
                <div class="form-group"><label>Stock</label><input type="number" class="form-control input-lg" id="editarStockVacuna" name="editarStockVacuna" min="0" step="1" required></div>
                <div class="form-group"><label>Stock mínimo para alerta</label><input type="number" class="form-control input-lg" id="editarStockMinimo" name="editarStockMinimo" min="0" step="1" required></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div></div>
</div>
<?php endif; ?>
