<?php
$historiales = ControladorHistorialVacunas::ctrMostrarHistorial();
$citasVacunacion = ControladorHistorialVacunas::ctrListarCitasVacunacion();
$flash = $_SESSION['historial_vac_flash'] ?? null;
$gestionaHistorial = in_array((int) ($_SESSION['IDrol'] ?? 0), [1, 2], true);
unset($_SESSION['historial_vac_flash']);
$idCitaSolicitada = filter_var($_GET['cita'] ?? null, FILTER_VALIDATE_INT);
$citaSolicitada = $idCitaSolicitada === false
    ? null : ControladorCitas::ctrMostrarCitas('IDcita', (int) $idCitaSolicitada);
if (!is_array($citaSolicitada)
    || $citaSolicitada['estadoCita'] !== 'Completada'
    || (int) ($citaSolicitada['esVacunacion'] ?? 0) !== 1) {
    $citaSolicitada = null;
}

$hoy = date('Y-m-d');
$limiteProximas = date('Y-m-d', strtotime('+30 days'));
$vencidas = 0;
$proximas = 0;
foreach ($historiales as $registro) {
    $fechaProxima = $registro['fechaProximaDosis'];
    if ($fechaProxima !== null && $fechaProxima < $hoy) {
        $vencidas++;
    } elseif ($fechaProxima !== null && $fechaProxima <= $limiteProximas) {
        $proximas++;
    }
}

function fechaHistorial(?string $fecha): string
{
    return $fecha ? date('d/m/Y', strtotime($fecha)) : 'Sin programar';
}
?>
<script>
window.citaVacunacionPreseleccionada = <?php echo json_encode(
    $citaSolicitada === null ? null : [
        'IDcita' => (int) $citaSolicitada['IDcita'],
        'fecha' => substr($citaSolicitada['fechaAtencion'], 0, 10),
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?>;
</script>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Historial de vacunación <small>Control de dosis aplicadas</small></h3></div>
        <?php if ($gestionaHistorial): ?><div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalHistorialVac"
                    <?php echo $citasVacunacion === [] ? 'disabled' : ''; ?>>
                <i class="fa fa-plus"></i> Registrar vacunación
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

    <?php if ($citasVacunacion === []): ?>
        <div class="alert alert-warning">No hay citas de vacunación completadas y pendientes de pago.</div>
    <?php endif; ?>

    <div class="row top_tiles">
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel"><div class="x_content text-center">
                <h2><?php echo count($historiales); ?></h2><p>Dosis registradas</p>
            </div></div>
        </div>
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel"><div class="x_content text-center">
                <h2 class="text-warning"><?php echo $proximas; ?></h2><p>Próximas en 30 días</p>
            </div></div>
        </div>
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel"><div class="x_content text-center">
                <h2 class="text-danger"><?php echo $vencidas; ?></h2><p>Dosis vencidas</p>
            </div></div>
        </div>
    </div>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title"><h2>Vacunaciones registradas</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead><tr>
                        <th>Mascota</th><th>Propietario</th><th>Especie</th><th>Vacuna</th>
                        <th>Aplicación</th><th>Próxima dosis</th><th>Cita relacionada</th>
                        <th>Observaciones</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($historiales as $registro): ?>
                        <?php
                        $fechaProxima = $registro['fechaProximaDosis'];
                        $claseProxima = 'default';
                        if ($fechaProxima !== null && $fechaProxima < $hoy) {
                            $claseProxima = 'danger';
                        } elseif ($fechaProxima !== null && $fechaProxima <= $limiteProximas) {
                            $claseProxima = 'warning';
                        } elseif ($fechaProxima !== null) {
                            $claseProxima = 'success';
                        }
                        ?>
                        <tr>
                            <td><?php echo Seguridad::escapar($registro['nombrem']); ?></td>
                            <td><?php echo Seguridad::escapar($registro['propietario']); ?></td>
                            <td><span class="label label-info"><?php echo Seguridad::escapar($registro['nombre_Especie']); ?></span></td>
                            <td><?php echo Seguridad::escapar($registro['nombreVac']); ?></td>
                            <td><?php echo fechaHistorial($registro['fechaAplicacion']); ?></td>
                            <td><span class="label label-<?php echo $claseProxima; ?>"><?php echo fechaHistorial($fechaProxima); ?></span></td>
                            <td>
                                <?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($registro['fechaAtencion']))); ?><br>
                                <small><?php echo Seguridad::escapar($registro['veterinario']); ?></small>
                            </td>
                            <td><?php echo Seguridad::escapar($registro['observaciones'] ?? ''); ?></td>
                            <td>
                                <?php if ($gestionaHistorial): ?><button type="button" class="btn btn-warning btnEditHistorialVac"
                                        data-id="<?php echo (int) $registro['IDhistorialVac']; ?>"
                                        title="<?php echo (int) $registro['citaPagada'] === 1 ? 'Corregir observaciones' : 'Corregir registro'; ?>">
                                    <i class="fa fa-pencil"></i>
                                </button>
                                <?php else: ?><span class="text-muted">Solo consulta</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
function opcionesCitasHistorial(array $citas): void
{
    echo '<option value="">Seleccione una cita</option>';
    foreach ($citas as $cita) {
        echo '<option value="' . (int) $cita['IDcita'] . '">'
            . Seguridad::escapar(date('d/m/Y H:i', strtotime($cita['fechaAtencion']))
                . ' · ' . $cita['nombrem'] . ' · ' . $cita['propietario'])
            . '</option>';
    }
}
?>

<?php if ($gestionaHistorial): ?>
    <div id="modalHistorialVac" class="modal fade" role="dialog">
        <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="HistorialVac">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Registrar vacunación</h4></div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><label>Cita de vacunación</label><select class="form-control input-lg citaHistorial" id="nuevaCitaHistorial" name="nuevaCitaHistorial" required><?php opcionesCitasHistorial($citasVacunacion); ?></select></div>
                <div id="nuevaMascotaDerivada" class="alert alert-info">Seleccione una cita para obtener la mascota.</div>
                <div class="form-group">
                    <label>Vacunas compatibles</label>
                    <select id="nuevasVacunasHistorial" name="nuevaVacunaHistorial[]"
                            class="form-control select-multiple" multiple required disabled>
                        <option disabled>Seleccione una cita.</option>
                    </select>
                    <div id="resumenAdvertenciasEsquemaVac" class="text-warning" style="display:none;margin-top:6px">
                        <i class="fa fa-bell"></i>
                        <button type="button" class="btn btn-link btn-xs" id="btnVerAdvertenciasEsquemaVac"></button>
                    </div>
                </div>
                <div class="form-group"><label>Fecha de aplicación</label><input type="date" class="form-control input-lg" id="nuevaFechaAplicacion" name="nuevaFechaAplicacion" value="<?php echo $hoy; ?>" max="<?php echo $hoy; ?>" required></div>
                <div class="form-group"><label>Próxima dosis (opcional)</label><input type="date" class="form-control input-lg" name="nuevaFechaProximaDosis"></div>
                <div class="form-group"><label>Observaciones</label><textarea class="form-control input-lg" name="nuevaObservacionVacuna" maxlength="255" rows="3"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary" id="btnRegistrarVacunacion">Registrar vacunas seleccionadas</button></div>
        </form>
    </div></div>
</div>
<div id="modHistorialVac" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="HistorialVac">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="IDhistorialVac" id="IDhistorialVac">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Corregir historial de vacunación</h4></div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><label>Cita de vacunación</label><select class="form-control input-lg citaHistorial" id="editarCitaHistorial" name="editarCitaHistorial" required><?php opcionesCitasHistorial($citasVacunacion); ?></select></div>
                <div id="editarMascotaDerivada" class="alert alert-info">La mascota se obtiene de la cita.</div>
                <div class="form-group"><label>Vacuna compatible</label><select class="form-control input-lg vacunaHistorial" id="editarVacunaHistorial" name="editarVacunaHistorial" required></select></div>
                <div class="form-group"><label>Fecha de aplicación</label><input type="date" class="form-control input-lg" id="editarFechaAplicacion" name="editarFechaAplicacion" max="<?php echo $hoy; ?>" required></div>
                <div class="form-group"><label>Próxima dosis (opcional)</label><input type="date" class="form-control input-lg" id="editarFechaProximaDosis" name="editarFechaProximaDosis"></div>
                <div class="form-group"><label>Observaciones</label><textarea class="form-control input-lg" id="editarObservacionVacuna" name="editarObservacionVacuna" maxlength="255" rows="3"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary">Guardar corrección</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>
