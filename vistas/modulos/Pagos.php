<?php
$pagos = ControladorPagos::ctrMostrarPagos();
$citasPendientesPago = ControladorPagos::ctrListarCitasPendientesPago();
$metodosPago = ControladorPagos::ctrMetodosPago();
$usuariosPago = array_values(array_filter(
    ControladorUsuarios::ctrMostrarUsuarios(),
    static fn(array $usuario): bool => (int) $usuario['estado_U'] === 1
));
$flash = $_SESSION['pago_flash'] ?? null;
$puedeCobrar = true;
$puedeCorregirPago = (int) ($_SESSION['IDrol'] ?? 0) === 1;
unset($_SESSION['pago_flash']);
$citaPagoSolicitada = filter_var($_GET['cita'] ?? null, FILTER_VALIDATE_INT);
$citaPagoSolicitada = $citaPagoSolicitada === false ? null : $citaPagoSolicitada;
$citasCobrables = array_values(array_filter(
    $citasPendientesPago,
    static fn(array $cita): bool => (float) $cita['montoCalculado'] > 0
        && (!(int) $cita['incluyeVacunacion'] || (int) $cita['vacunasAplicadas'] > 0)
));

$totalCobrado = 0.0;
$totalHoy = 0.0;
$hoyPago = date('Y-m-d');
foreach ($pagos as $pago) {
    $totalCobrado += (float) $pago['montopago'];
    if (substr($pago['fechapago'], 0, 10) === $hoyPago) {
        $totalHoy += (float) $pago['montopago'];
    }
}

$opcionesMetodos = static function (array $metodos): void {
    echo '<option value="">Seleccione un método</option>';
    foreach ($metodos as $metodo) {
        echo '<option value="' . Seguridad::escapar($metodo) . '">'
            . Seguridad::escapar($metodo) . '</option>';
    }
};
$opcionesUsuariosPago = static function (array $usuarios): void {
    echo '<option value="">Seleccione quién recibió el pago</option>';
    foreach ($usuarios as $usuario) {
        $seleccionado = (int) $usuario['IDusuario'] === (int) ($_SESSION['IDusuario'] ?? 0)
            ? ' selected' : '';
        echo '<option value="' . (int) $usuario['IDusuario'] . '"' . $seleccionado . '>'
            . Seguridad::escapar($usuario['nombre_U'] . ' ' . $usuario['apellido_U']
                . ' · ' . $usuario['nombre_Rol']) . '</option>';
    }
};
?>
<script>
window.citaPagoPreseleccionada = <?php echo json_encode($citaPagoSolicitada); ?>;
window.usuarioPagoActual = <?php echo (int) ($_SESSION['IDusuario'] ?? 0); ?>;
</script>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Pagos <small>Cobro de servicios realizados</small></h3></div>
        <?php if ($puedeCobrar): ?><div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalPago"
                    <?php echo $citasCobrables === [] ? 'disabled' : ''; ?>>
                <i class="fa fa-plus"></i> Registrar pago
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

    <?php if ($citasPendientesPago === []): ?>
        <div class="alert alert-info">No hay servicios completados pendientes de pago.</div>
    <?php endif; ?>

    <?php if ($citasPendientesPago !== []): ?>
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="x_panel">
                <div class="x_title"><h2>Servicios pendientes de cobro</h2><div class="clearfix"></div></div>
                <div class="x_content">
                    <table class="table table-striped table-bordered">
                        <thead><tr><th>Fecha de cita</th><th>Propietario</th><th>Mascota</th><th>Servicio</th><th>Estado</th><th>Acción</th></tr></thead>
                        <tbody>
                        <?php foreach ($citasPendientesPago as $cita): ?>
                            <?php $faltaVacuna = (int) $cita['incluyeVacunacion'] === 1 && (int) $cita['vacunasAplicadas'] === 0; ?>
                            <tr>
                                <td><?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($cita['fechaAtencion']))); ?></td>
                                <td><?php echo Seguridad::escapar($cita['propietario']); ?></td>
                                <td><?php echo Seguridad::escapar($cita['nombrem']); ?></td>
                                <td><?php echo Seguridad::escapar($cita['tipoServicio']); ?></td>
                                <td>
                                    <?php if ($puedeCobrar): ?>
                                    <?php if ($faltaVacuna): ?>
                                        <span class="label label-warning">Falta registrar vacuna</span>
                                    <?php else: ?>
                                        <span class="label label-success">Listo para cobrar · Bs <?php echo number_format((float) $cita['montoCalculado'], 2, '.', ','); ?></span>
                                    <?php endif; ?>
                                    <?php else: ?><span class="text-muted">Pendiente de recepción</span><?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($faltaVacuna): ?>
                                        <a class="btn btn-primary btn-sm" href="HistorialVac?cita=<?php echo (int) $cita['IDcita']; ?>">
                                            <i class="fa fa-medkit"></i> Registrar vacunación
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-success btn-sm btnCobrarCita" data-cita="<?php echo (int) $cita['IDcita']; ?>">
                                            <i class="fa fa-money"></i> Cobrar
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row top_tiles">
        <div class="col-md-4 col-sm-4 col-xs-12"><div class="x_panel"><div class="x_content text-center">
            <h2><?php echo count($pagos); ?></h2><p>Pagos registrados</p>
        </div></div></div>
        <div class="col-md-4 col-sm-4 col-xs-12"><div class="x_panel"><div class="x_content text-center">
            <h2 class="text-success">Bs <?php echo number_format($totalHoy, 2, '.', ','); ?></h2><p>Cobrado hoy</p>
        </div></div></div>
        <div class="col-md-4 col-sm-4 col-xs-12"><div class="x_panel"><div class="x_content text-center">
            <h2 class="text-primary">Bs <?php echo number_format($totalCobrado, 2, '.', ','); ?></h2><p>Total registrado</p>
        </div></div></div>
    </div>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title"><h2>Historial de pagos</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead><tr>
                        <th>Fecha de pago</th><th>Propietario</th><th>Mascota</th><th>Servicio</th>
                        <th>Fecha de cita</th><th>Monto</th><th>Método</th><th>Recibido por</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr>
                            <td><?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($pago['fechapago']))); ?></td>
                            <td><?php echo Seguridad::escapar($pago['propietario']); ?></td>
                            <td><?php echo Seguridad::escapar($pago['nombrem']); ?></td>
                            <td><?php echo Seguridad::escapar($pago['tipoServicio']); ?></td>
                            <td><?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($pago['fechaAtencion']))); ?></td>
                            <td><strong>Bs <?php echo number_format((float) $pago['montopago'], 2, '.', ','); ?></strong></td>
                            <td><span class="label label-info"><?php echo Seguridad::escapar($pago['metodopago']); ?></span></td>
                            <td><?php echo Seguridad::escapar($pago['recibidoPor']); ?></td>
                            <td>
                                <a class="btn btn-primary" href="ComprobantePago?id=<?php echo (int) $pago['IDpago']; ?>"
                                   title="Ver comprobante"><i class="fa fa-print"></i></a>
                                <?php if ($puedeCorregirPago): ?><button type="button" class="btn btn-warning btnEditPago"
                                        data-id="<?php echo (int) $pago['IDpago']; ?>" title="Corregir pago">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($puedeCobrar): ?><div id="modalPago" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Pagos">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Registrar pago</h4></div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><label>Cita completada</label><select class="form-control input-lg" id="nuevaCitaPago" name="nuevaCitaPago" required>
                    <option value="">Seleccione una cita</option>
                    <?php foreach ($citasCobrables as $cita): ?>
                        <option value="<?php echo (int) $cita['IDcita']; ?>"
                                <?php echo (int) $citaPagoSolicitada === (int) $cita['IDcita'] ? 'selected' : ''; ?>>
                            <?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($cita['fechaAtencion']))
                                . ' · ' . $cita['nombrem'] . ' · ' . $cita['propietario'] . ' · ' . $cita['tipoServicio']); ?>
                        </option>
                    <?php endforeach; ?>
                </select></div>
                <div id="nuevoResumenCobro" class="alert alert-info">Seleccione una cita para calcular el cobro.</div>
                <div id="nuevosConceptosPago"></div>
                <div class="form-group"><label>Total calculado (Bs)</label><input type="text" class="form-control input-lg" id="nuevoMontoPagoVista" readonly></div>
                <div class="form-group"><label>Método</label><select class="form-control input-lg metodoPago" name="nuevoMetodoPago" required><?php $opcionesMetodos($metodosPago); ?></select></div>
                <div class="form-group"><label>Pago recibido por</label><select class="form-control input-lg" id="nuevoUsuarioPago" name="nuevoUsuarioPago" required><?php $opcionesUsuariosPago($usuariosPago); ?></select><small class="help-block">Seleccione al usuario que recibió y registró el cobro.</small></div>
                <div class="form-group"><label>Fecha y hora del pago</label><input type="datetime-local" class="form-control input-lg" name="nuevaFechaPago" value="<?php echo date('Y-m-d\TH:i'); ?>" max="<?php echo date('Y-m-d\TH:i'); ?>" required></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary" id="btnRegistrarPago">Registrar cobro</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<?php if ($puedeCorregirPago): ?><div id="modPago" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Pagos">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="IDpago" id="IDpago">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Corregir pago</h4></div>
            <div class="modal-body"><div class="box-body">
                <div class="alert alert-info" id="editarDetallePago"></div>
                <div class="form-group"><label>Monto cobrado (Bs)</label><input type="text" class="form-control input-lg" id="editarMontoPago" readonly></div>
                <div class="form-group"><label>Método</label><select class="form-control input-lg metodoPago" id="editarMetodoPago" name="editarMetodoPago" required><?php $opcionesMetodos($metodosPago); ?></select></div>
                <div class="form-group"><label>Pago recibido por</label><select class="form-control input-lg" id="editarUsuarioPago" name="editarUsuarioPago" required><?php $opcionesUsuariosPago($usuariosPago); ?></select></div>
                <div class="form-group"><label>Fecha y hora del pago</label><input type="datetime-local" class="form-control input-lg" id="editarFechaPago" name="editarFechaPago" max="<?php echo date('Y-m-d\TH:i'); ?>" required></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary">Guardar corrección</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>
