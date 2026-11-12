<?php
$dashboardPerfil = ControladorPerfil::ctrDashboardActual();
if (!is_array($dashboardPerfil)) {
    echo '<div class="right_col" role="main"><div class="alert alert-danger">No fue posible cargar el perfil.</div></div>';
    return;
}
$usuarioPerfil = $dashboardPerfil['usuario'];
$resumenPerfil = $dashboardPerfil['resumen'];
$cajaPerfil = $dashboardPerfil['caja'];
$fotoPerfil = $usuarioPerfil['foto'] !== ''
    ? $usuarioPerfil['foto'] : 'vistas/production/images/img.jpg';
$flashPerfil = $_SESSION['perfil_flash'] ?? null;
unset($_SESSION['perfil_flash']);

function claseEstadoPerfil(string $estado): string
{
    return match ($estado) {
        'Completada' => 'success',
        'Cancelada' => 'danger',
        default => 'warning',
    };
}
?>

<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Mi perfil <small>Resumen personal de actividad</small></h3></div>
        <div class="title_right">
            <a href="Calendar" class="btn btn-primary pull-right"><i class="fa fa-calendar"></i> Ver calendario</a>
        </div>
    </div>
    <div class="clearfix"></div>
    <?php if (is_array($flashPerfil)): ?>
        <div class="app-flash alert alert-<?php echo Seguridad::escapar($flashPerfil['tipo']); ?> alert-dismissible"
             data-tipo="<?php echo Seguridad::escapar($flashPerfil['tipo']); ?>" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo Seguridad::escapar($flashPerfil['mensaje']); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 col-sm-4 col-xs-12">
            <div class="x_panel">
                <div class="x_content text-center">
                    <img src="<?php echo Seguridad::escapar($fotoPerfil); ?>"
                         class="img-circle" alt="Foto del usuario"
                         style="width:130px;height:130px;object-fit:cover;margin-bottom:15px">
                    <h2><?php echo Seguridad::escapar(
                        $usuarioPerfil['nombre_U'] . ' ' . $usuarioPerfil['apellido_U']); ?></h2>
                    <span class="label label-primary"><?php echo Seguridad::escapar($usuarioPerfil['nombre_Rol']); ?></span>
                    <hr>
                    <p><i class="fa fa-envelope"></i> <?php echo Seguridad::escapar($usuarioPerfil['email_U']); ?></p>
                    <p><i class="fa fa-phone"></i> <?php echo Seguridad::escapar($usuarioPerfil['telefono_U']); ?></p>
                    <p><i class="fa fa-clock-o"></i> Turno: <?php echo Seguridad::escapar($usuarioPerfil['turno_U']); ?></p>
                    <p><span class="label label-success">Cuenta habilitada</span></p>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalEditarPerfil">
                        <i class="fa fa-pencil"></i> Editar mis datos
                    </button>
                </div>
            </div>
        </div>

        <div class="col-md-8 col-sm-8 col-xs-12">
            <div class="row top_tiles">
                <div class="col-md-6 col-sm-6 col-xs-12"><div class="x_panel"><div class="x_content text-center">
                    <h2 class="text-primary"><?php echo $resumenPerfil['citasHoy']; ?></h2><p>Citas asignadas hoy</p>
                </div></div></div>
                <div class="col-md-6 col-sm-6 col-xs-12"><div class="x_panel"><div class="x_content text-center">
                    <h2 class="text-warning"><?php echo $resumenPerfil['proximas']; ?></h2><p>Próximas citas pendientes</p>
                </div></div></div>
                <div class="col-md-6 col-sm-6 col-xs-12"><div class="x_panel"><div class="x_content text-center">
                    <h2 class="text-success"><?php echo $resumenPerfil['completadas']; ?></h2>
                    <p>Citas completadas <small>(<?php echo $resumenPerfil['canceladas']; ?> canceladas)</small></p>
                </div></div></div>
                <div class="col-md-6 col-sm-6 col-xs-12"><div class="x_panel"><div class="x_content text-center">
                    <h2 class="text-success">Bs <?php echo number_format($cajaPerfil['cobradoMes'], 2, '.', ','); ?></h2>
                    <p>Recibido este mes</p>
                </div></div></div>
            </div>
            <div class="x_panel">
                <div class="x_title"><h2>Accesos rápidos</h2><div class="clearfix"></div></div>
                <div class="x_content text-center">
                    <a href="Calendar" class="btn btn-app"><i class="fa fa-calendar"></i> Citas</a>
                    <a href="HistorialVac" class="btn btn-app"><i class="fa fa-medkit"></i> Vacunación</a>
                    <a href="SeguimientoVac" class="btn btn-app"><i class="fa fa-list-alt"></i> Esquemas</a>
                    <a href="Pagos" class="btn btn-app"><i class="fa fa-money"></i> Pagos</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-7 col-sm-7 col-xs-12">
            <div class="x_panel">
                <div class="x_title"><h2>Mis citas recientes y próximas</h2><div class="clearfix"></div></div>
                <div class="x_content">
                    <?php if ($dashboardPerfil['citas'] === []): ?>
                        <div class="alert alert-info">Todavía no tiene citas asignadas.</div>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table table-striped">
                            <thead><tr><th>Fecha</th><th>Mascota</th><th>Propietario</th><th>Servicio</th><th>Estado</th></tr></thead>
                            <tbody><?php foreach ($dashboardPerfil['citas'] as $citaPerfil): ?><tr>
                                <td><?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($citaPerfil['fechaAtencion']))); ?></td>
                                <td><?php echo Seguridad::escapar($citaPerfil['nombrem']); ?></td>
                                <td><?php echo Seguridad::escapar($citaPerfil['propietario']); ?></td>
                                <td><?php echo Seguridad::escapar($citaPerfil['tipoServicio']); ?></td>
                                <td><span class="label label-<?php echo claseEstadoPerfil($citaPerfil['estadoCita']); ?>">
                                    <?php echo Seguridad::escapar($citaPerfil['estadoCita']); ?></span></td>
                            </tr><?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-5 col-sm-5 col-xs-12">
            <div class="x_panel">
                <div class="x_title"><h2>Pagos recibidos por mí</h2><div class="clearfix"></div></div>
                <div class="x_content">
                    <p><strong>Hoy:</strong> Bs <?php echo number_format($cajaPerfil['cobradoHoy'], 2, '.', ','); ?>
                        · <strong>Total de operaciones:</strong> <?php echo $cajaPerfil['cantidadPagos']; ?></p>
                    <?php if ($dashboardPerfil['pagos'] === []): ?>
                        <div class="alert alert-info">Todavía no registró pagos.</div>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table table-striped">
                            <thead><tr><th>Fecha</th><th>Mascota</th><th>Método</th><th>Monto</th></tr></thead>
                            <tbody><?php foreach ($dashboardPerfil['pagos'] as $pagoPerfil): ?><tr>
                                <td><?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($pagoPerfil['fechapago']))); ?></td>
                                <td><?php echo Seguridad::escapar($pagoPerfil['nombrem']); ?></td>
                                <td><?php echo Seguridad::escapar($pagoPerfil['metodopago']); ?></td>
                                <td>Bs <?php echo number_format((float) $pagoPerfil['montopago'], 2, '.', ','); ?></td>
                            </tr><?php endforeach; ?></tbody>
                        </table></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalEditarPerfil" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Perfil">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="editarPerfil" value="1">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Editar mis datos</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Nombre</label><input type="text" class="form-control input-lg" name="perfilNombre" minlength="2" maxlength="50" value="<?php echo Seguridad::escapar($usuarioPerfil['nombre_U']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Apellido</label><input type="text" class="form-control input-lg" name="perfilApellido" minlength="2" maxlength="50" value="<?php echo Seguridad::escapar($usuarioPerfil['apellido_U']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Teléfono</label><input type="tel" class="form-control input-lg" name="perfilTelefono" minlength="7" maxlength="20" value="<?php echo Seguridad::escapar($usuarioPerfil['telefono_U']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Correo electrónico</label><input type="email" class="form-control input-lg" name="perfilEmail" maxlength="254" value="<?php echo Seguridad::escapar($usuarioPerfil['email_U']); ?>" required><small class="help-block">El nuevo correo será el utilizado para iniciar sesión.</small>
                </div>
                <div class="form-group">
                    <label>Nueva contraseña (opcional)</label><input type="password" class="form-control input-lg" name="perfilPasswordNueva" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Déjela vacía para conservar la actual">
                </div>
                <hr>
                <div class="form-group">
                    <label>Contraseña actual</label><input type="password" class="form-control input-lg" name="perfilPasswordActual" maxlength="72" autocomplete="current-password" required>
                    <small class="help-block">Es obligatoria para autorizar cualquier modificación.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div></div>
</div>
