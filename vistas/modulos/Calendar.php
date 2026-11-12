<?php
$citas = ControladorCitas::ctrMostrarCitas();
$clientes = ControladorClientes::ctrListarClientesHabilitados();
$usuarios = array_values(array_filter(
    ControladorUsuarios::ctrMostrarUsuarios(),
    static function (array $usuario): bool {
        $rolSesion = (int) ($_SESSION['IDrol'] ?? 0);
        if ((int) $usuario['estado_U'] !== 1) {
            return false;
        }
        if ($rolSesion === 1) {
            return true;
        }
        return (int) $usuario['IDrol'] === 2
            && ($rolSesion !== 2
                || (int) $usuario['IDusuario'] === (int) ($_SESSION['IDusuario'] ?? 0));
    }
));
$servicios = ControladorCitas::ctrListarServiciosActivos();
$mascotasDisponibles = array_values(array_filter(
    ControladorMascotas::crtMostrarMascotas(),
    static fn(array $mascota): bool =>
        (int) $mascota['habilitadoM'] === 1 && (int) $mascota['habilitadoC'] === 1
));
$puedeCrear = $clientes !== [] && $mascotasDisponibles !== [] && $usuarios !== [] && $servicios !== [];
$flashCita = $_SESSION['cita_flash'] ?? null;
unset($_SESSION['cita_flash']);

$opcionesClientes = static function (array $lista): void {
    foreach ($lista as $cliente) {
        echo '<option value="' . (int) $cliente['IDcliente'] . '">'
            . Seguridad::escapar($cliente['nombrec'] . ' ' . $cliente['apellidoc'])
            . '</option>';
    }
};
$opcionesVeterinarios = static function (array $lista): void {
    foreach ($lista as $usuario) {
        $horariosTurno = ['Mañana' => '07:00–13:00', 'Tarde' => '13:00–19:00',
            'Horario continuo' => '07:00–19:00'];
        echo '<option value="' . (int) $usuario['IDusuario'] . '">'
            . Seguridad::escapar($usuario['nombre_U'] . ' ' . $usuario['apellido_U']
                . ' · ' . $usuario['turno_U'] . ' (' . ($horariosTurno[$usuario['turno_U']] ?? 'sin horario') . ')')
            . '</option>';
    }
};
$opcionesServicios = static function (array $lista): void {
    foreach ($lista as $servicio) {
        echo '<option value="' . (int) $servicio['IDservicio'] . '">'
            . Seguridad::escapar($servicio['tipoServicio'] . ' · ' . $servicio['duracionMinutos'] . ' min')
            . '</option>';
    }
};
?>
<script>
window.citasDelServidor = <?php echo json_encode(
    $citas,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
); ?>;
window.mascotasParaCitas = <?php echo json_encode(array_map(
    static fn(array $mascota): array => [
        'IDmascota' => (int) $mascota['IDmascota'],
        'IDcliente' => (int) $mascota['IDcliente'],
        'nombrem' => $mascota['nombrem'],
    ],
    $mascotasDisponibles
), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
window.configuracionCitas = {
    puedeCrear: <?php echo $puedeCrear ? 'true' : 'false'; ?>,
    idUsuario: <?php echo (int) ($_SESSION['IDusuario'] ?? 0); ?>,
    idRol: <?php echo (int) ($_SESSION['IDrol'] ?? 0); ?>,
    csrf: <?php echo json_encode(Seguridad::tokenCsrf(), JSON_HEX_TAG | JSON_HEX_AMP); ?>
};
</script>

<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left">
            <h3>Calendario de citas <small>Atención de 07:00 a 19:00</small></h3>
        </div>
    </div>
    <div class="clearfix"></div>

    <?php if (is_array($flashCita)): ?>
        <div class="app-flash alert alert-<?php echo Seguridad::escapar($flashCita['tipo']); ?> alert-dismissible" data-tipo="<?php echo Seguridad::escapar($flashCita['tipo']); ?>" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo Seguridad::escapar($flashCita['mensaje']); ?>
        </div>
    <?php endif; ?>


    <?php if (!$puedeCrear): ?>
        <div class="alert alert-warning">
            Para programar citas necesita al menos un cliente con mascota habilitada, un veterinario habilitado y un servicio activo.
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <div class="x_panel">
                <div class="x_title">
                    <h2>Agenda <small>Seleccione un bloque libre para programar una cita</small></h2>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <p>
                        <span class="label label-primary">Pendiente</span>
                        <span class="label label-danger">Cancelada</span>
                        <span class="label label-success">Completada</span>
                    </p>
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalAgregarCita" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Calendar" id="formAgregarCita">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" id="fechaCitaCrear" name="fechaCitaCrear">
            <div class="modal-header" style="background:#3c8dbc;color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Programar nueva cita</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="alert alert-info">
                    Fecha seleccionada: <strong id="fechaCitaCrearTexto"></strong>
                </div>
                <div class="form-group">
                    <label>Motivo</label>
                    <input type="text" class="form-control input-lg" name="nuevoMotivo"
                           maxlength="75" placeholder="Ej.: Control general" required>
                </div>
                <div class="form-group">
                    <label>Servicios</label>
                    <select id="nuevoServicio" name="nuevoServicio[]" class="form-control select-multiple"
                            multiple required>
                        <?php $opcionesServicios($servicios); ?>
                    </select>
                    <small class="help-block">Seleccione uno o varios servicios.</small>
                </div>
                <div class="form-group">
                    <label>Hora</label>
                    <input type="time" class="form-control input-lg" name="seleccionarHora"
                           id="seleccionarHora" min="07:00" max="18:45" step="900" required>
                    <small id="disponibilidadNueva" class="help-block"></small>
                </div>
                <div class="form-group">
                    <label>Propietario</label>
                    <select class="form-control input-lg" id="seleccionarCliente" required>
                        <option value="" selected disabled>Seleccionar cliente</option>
                        <?php $opcionesClientes($clientes); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mascota</label>
                    <select class="form-control input-lg" id="seleccionarMascota"
                            name="seleccionarMascota" required disabled>
                        <option value="">Seleccione primero un cliente</option>
                    </select>
                    <small id="mensajeMascotasCrear" class="help-block"></small>
                </div>
                <div class="form-group">
                    <label><?php echo (int) ($_SESSION['IDrol'] ?? 0) === 1 ? 'Veterinario asignado' : 'Veterinario'; ?></label>
                    <select class="form-control input-lg" id="nuevoUsuario" name="nuevoUsuario" required>
                        <option value="" selected disabled>Seleccionar Veterinario</option>
                        <?php $opcionesVeterinarios($usuarios); ?>
                    </select>
                </div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <a href="#" id="btnRegistrarVacunacionCita" class="btn btn-primary" style="display:none">
                    <i class="fa fa-medkit"></i> Registrar vacunación
                </a>
                <a href="#" id="btnRegistrarPagoCita" class="btn btn-info" style="display:none">
                    <i class="fa fa-money"></i> Registrar pago
                </a>
                <button type="submit" class="btn btn-primary" id="btnGuardarCita">Guardar cita</button>
            </div>
        </form>
    </div></div>
</div>

<div id="modalEditarCita" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Calendar" id="formEditarCita">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" id="idCitaEditar" name="idCitaEditar">
            <div class="modal-header" style="background:#f39c12;color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Detalle de la cita</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <p>Estado: <strong id="estadoCitaTexto"></strong></p>
                <div class="form-group">
                    <label>Motivo</label>
                    <input type="text" class="form-control input-lg" id="editarMotivo"
                           name="editarMotivo" maxlength="75" required>
                </div>
                <div class="form-group">
                    <label>Servicios</label>
                    <select id="editarServicio" name="editarServicio[]" class="form-control select-multiple"
                            multiple required>
                        <?php $opcionesServicios($servicios); ?>
                    </select>
                    <small class="help-block">Seleccione uno o varios servicios.</small>
                </div>
                <div class="row">
                    <div class="form-group col-md-6">
                        <label>Fecha</label>
                        <input type="date" class="form-control input-lg" id="fechaCitaEditar"
                               name="fechaCitaEditar" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label>Hora</label>
                        <input type="time" class="form-control input-lg" id="editarHora"
                               name="editarHora" min="07:00" max="18:45" step="900" required>
                    </div>
                </div>
                <small id="disponibilidadEdicion" class="help-block"></small>
                <div class="form-group">
                    <label>Propietario</label>
                    <select class="form-control input-lg" id="editarCliente" required>
                        <option value="" disabled>Seleccionar cliente</option>
                        <?php $opcionesClientes($clientes); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mascota</label>
                    <select class="form-control input-lg" id="editarMascotaSelect"
                            name="editarMascota" required>
                        <option value="">Seleccionar mascota</option>
                    </select>
                    <small id="mensajeMascotasEditar" class="help-block"></small>
                </div>
                <div class="form-group">
                    <label><?php echo (int) ($_SESSION['IDrol'] ?? 0) === 1 ? 'Veterinario asignado' : 'Veterinario'; ?></label>
                    <select class="form-control input-lg" id="editarUsuario" name="editarUsuario" required>
                        <option value="" disabled>Seleccionar Veterinario</option>
                        <?php $opcionesVeterinarios($usuarios); ?>
                    </select>
                </div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" id="btnAccionAlterna" class="btn"
                        name="btnAccionCita" value="cancelar" formnovalidate></button>
                <?php if (in_array((int) ($_SESSION['IDrol'] ?? 0), [1, 2], true)): ?><button type="submit" id="btnCompletarCita" class="btn btn-success"
                        name="btnAccionCita" value="completar" formnovalidate>
                    <i class="fa fa-check"></i> Completar
                </button>
                <?php endif; ?>
                <button type="submit" class="btn btn-warning" id="btnGuardarCambios"
                        name="btnAccionCita" value="guardar">Guardar cambios</button>
            </div>
        </form>
    </div></div>
</div>
