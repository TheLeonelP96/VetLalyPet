<?php
$historiales = ControladorHistorialVacunas::ctrMostrarHistorial();
$mascotas = ControladorHistorialVacunas::ctrListarMascotasActivas();
$flash = $_SESSION['historial_vac_flash'] ?? null;
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
        'IDmascota' => (int) $citaSolicitada['IDmascota'],
        'fecha' => substr($citaSolicitada['fechaAtencion'], 0, 10),
    ],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?>;
</script>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Historial de vacunación <small>Control de dosis aplicadas</small></h3></div>
        <div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalHistorialVac"
                    <?php echo $mascotas === [] ? 'disabled' : ''; ?>>
                <i class="fa fa-plus"></i> Registrar vacunación
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

    <?php if ($mascotas === []): ?>
        <div class="alert alert-warning">No hay mascotas y propietarios habilitados para registrar una vacunación.</div>
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
                                <?php if ($registro['IDcita'] !== null): ?>
                                    <?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($registro['fechaAtencion']))); ?><br>
                                    <small><?php echo Seguridad::escapar($registro['veterinario']); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Registro directo</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo Seguridad::escapar($registro['observaciones'] ?? ''); ?></td>
                            <td>
                                <button type="button" class="btn btn-warning btnEditHistorialVac"
                                        data-id="<?php echo (int) $registro['IDhistorialVac']; ?>" title="Corregir registro">
                                    <i class="fa fa-pencil"></i>
                                </button>
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
function opcionesMascotasHistorial(array $mascotas): void
{
    echo '<option value="">Seleccione una mascota</option>';
    foreach ($mascotas as $mascota) {
        echo '<option value="' . (int) $mascota['IDmascota'] . '">'
            . Seguridad::escapar($mascota['nombrem'] . ' · ' . $mascota['nombre_Especie'] . ' · ' . $mascota['propietario'])
            . '</option>';
    }
}
?>

<div id="modalHistorialVac" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="HistorialVac">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Registrar vacunación</h4></div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><label>Mascota</label><select class="form-control input-lg mascotaHistorial" id="nuevaMascotaHistorial" name="nuevaMascotaHistorial" required><?php opcionesMascotasHistorial($mascotas); ?></select></div>
                <div class="form-group"><label>Vacuna compatible</label><select class="form-control input-lg vacunaHistorial" id="nuevaVacunaHistorial" name="nuevaVacunaHistorial" disabled required><option value="">Seleccione primero una mascota</option></select></div>
                <div class="form-group"><label>Fecha de aplicación</label><input type="date" class="form-control input-lg" id="nuevaFechaAplicacion" name="nuevaFechaAplicacion" value="<?php echo $hoy; ?>" max="<?php echo $hoy; ?>" required></div>
                <div class="form-group"><label>Próxima dosis (opcional)</label><input type="date" class="form-control input-lg" name="nuevaFechaProximaDosis"></div>
                <div class="form-group"><label>Cita relacionada (opcional)</label><select class="form-control input-lg citaHistorial" id="nuevaCitaHistorial" name="nuevaCitaHistorial" disabled><option value="">Sin cita relacionada</option></select><small class="help-block">La cita debe pertenecer a la mascota y coincidir con la fecha de aplicación.</small></div>
                <div class="form-group"><label>Observaciones</label><textarea class="form-control input-lg" name="nuevaObservacionVacuna" maxlength="255" rows="3"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary">Registrar dosis</button></div>
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
                <div class="form-group"><label>Mascota</label><select class="form-control input-lg mascotaHistorial" id="editarMascotaHistorial" name="editarMascotaHistorial" required><?php opcionesMascotasHistorial($mascotas); ?></select></div>
                <div class="form-group"><label>Vacuna compatible</label><select class="form-control input-lg vacunaHistorial" id="editarVacunaHistorial" name="editarVacunaHistorial" required></select></div>
                <div class="form-group"><label>Fecha de aplicación</label><input type="date" class="form-control input-lg" id="editarFechaAplicacion" name="editarFechaAplicacion" max="<?php echo $hoy; ?>" required></div>
                <div class="form-group"><label>Próxima dosis (opcional)</label><input type="date" class="form-control input-lg" id="editarFechaProximaDosis" name="editarFechaProximaDosis"></div>
                <div class="form-group"><label>Cita relacionada (opcional)</label><select class="form-control input-lg citaHistorial" id="editarCitaHistorial" name="editarCitaHistorial"><option value="">Sin cita relacionada</option></select><small class="help-block">La cita debe pertenecer a la mascota y coincidir con la fecha de aplicación.</small></div>
                <div class="form-group"><label>Observaciones</label><textarea class="form-control input-lg" id="editarObservacionVacuna" name="editarObservacionVacuna" maxlength="255" rows="3"></textarea></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button><button type="submit" class="btn btn-primary">Guardar corrección</button></div>
        </form>
    </div></div>
</div>
