<?php
$seguimientoMascotas = ControladorSeguimientoVacunas::ctrObtenerSeguimiento();
$esquemasVacunacion = ControladorSeguimientoVacunas::ctrListarEsquemas();
$especiesEsquema = ControladorSeguimientoVacunas::ctrListarEspecies();
$vacunasEsquema = ControladorSeguimientoVacunas::ctrListarVacunas();
$puedeGestionarEsquemas = in_array((int) ($_SESSION['IDrol'] ?? 0), [1, 2], true);
$flashSeguimiento = $_SESSION['seguimiento_flash'] ?? null;
unset($_SESSION['seguimiento_flash']);
$propietariosSeguimiento = [];
$totalesSeguimiento = ['mascotas' => count($seguimientoMascotas), 'atrasadas' => 0, 'proximas' => 0];
foreach ($seguimientoMascotas as $mascotaSeguimiento) {
    $propietariosSeguimiento[(int) $mascotaSeguimiento['IDcliente']] = $mascotaSeguimiento['propietario'];
    foreach ($mascotaSeguimiento['vacunas'] as $vacunaSeguimiento) {
        if (($vacunaSeguimiento['proxima']['estado'] ?? '') === 'Atrasada') {
            $totalesSeguimiento['atrasadas']++;
        } elseif (($vacunaSeguimiento['proxima']['estado'] ?? '') === 'Próxima') {
            $totalesSeguimiento['proximas']++;
        }
    }
}
asort($propietariosSeguimiento);

function claseEstadoSeguimiento(string $estado): string
{
    return match ($estado) {
        'Aplicada', 'Refuerzo aplicado' => 'success',
        'Atrasada' => 'danger',
        'Próxima' => 'warning',
        default => 'default',
    };
}

function fechaSeguimiento(?string $fecha): string
{
    return $fecha ? date('d/m/Y', strtotime($fecha)) : '—';
}
?>

<style>
.solo-impresion-seguimiento { display: none; }
@media print {
    body.imprimiendo-seguimiento * { visibility: hidden !important; }
    body.imprimiendo-seguimiento .mascota-seguimiento.imprimir-seguimiento,
    body.imprimiendo-seguimiento .mascota-seguimiento.imprimir-seguimiento * { visibility: visible !important; }
    body.imprimiendo-seguimiento .mascota-seguimiento.imprimir-seguimiento {
        position: absolute; left: 0; top: 0; width: 100%; margin: 0; border: 0; box-shadow: none;
    }
    body.imprimiendo-seguimiento .no-imprimir-seguimiento { display: none !important; }
    body.imprimiendo-seguimiento .solo-impresion-seguimiento { display: block !important; }
    body.imprimiendo-seguimiento table { font-size: 11px; }
    body.imprimiendo-seguimiento .label { border: 1px solid #777; color: #222 !important; }
}
</style>

<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left">
            <h3>Seguimiento de vacunación <small>Esquemas por mascota</small></h3>
        </div>
        <?php if ($puedeGestionarEsquemas): ?>
            <div class="title_right">
                <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalEsquemaVacuna">
                    <i class="fa fa-plus"></i> Agregar dosis al esquema
                </button>
            </div>
        <?php endif; ?>
    </div>
    <div class="clearfix"></div>

    <?php if (is_array($flashSeguimiento)): ?>
        <div class="app-flash alert alert-<?php echo Seguridad::escapar($flashSeguimiento['tipo']); ?> alert-dismissible"
             data-tipo="<?php echo Seguridad::escapar($flashSeguimiento['tipo']); ?>" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo Seguridad::escapar($flashSeguimiento['mensaje']); ?>
        </div>
    <?php endif; ?>

    <div class="x_panel no-imprimir-seguimiento">
        <div class="x_title">
            <h2>Configuración de esquemas <small>Dosis recomendadas por especie y vacuna</small></h2>
            <div class="clearfix"></div>
        </div>
        <div class="x_content">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead><tr><th>Especie</th><th>Vacuna</th><th>Dosis</th><th>Edad recomendada</th><th>Siguiente aplicación</th><?php if ($puedeGestionarEsquemas): ?><th>Acciones</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php foreach ($esquemasVacunacion as $esquema): ?>
                        <tr>
                            <td><?php echo Seguridad::escapar($esquema['nombre_Especie']); ?></td>
                            <td><?php echo Seguridad::escapar($esquema['nombreVac']); ?><?php if ((int) $esquema['estadoVac'] !== 1): ?> <span class="label label-default">Inhabilitada</span><?php endif; ?></td>
                            <td>Dosis <?php echo (int) $esquema['numeroDosis']; ?></td>
                            <td><?php echo (int) $esquema['edadAplicacion'] . ' ' . Seguridad::escapar($esquema['unidadEdad']); ?></td>
                            <td><?php echo $esquema['intervaloSiguienteDias'] === null ? 'Sin intervalo definido' : (int) $esquema['intervaloSiguienteDias'] . ' días después'; ?></td>
                            <?php if ($puedeGestionarEsquemas): ?>
                                <td><div class="btn-group">
                                    <button type="button" class="btn btn-warning btnEditarEsquema" data-id="<?php echo (int) $esquema['IDesquemaVac']; ?>" title="Editar"><i class="fa fa-pencil"></i></button>
                                    <button type="button" class="btn btn-danger btnEliminarEsquema" data-id="<?php echo (int) $esquema['IDesquemaVac']; ?>" data-vacuna="<?php echo Seguridad::escapar($esquema['nombreVac'] . ' - dosis ' . $esquema['numeroDosis']); ?>" <?php echo (int) $esquema['utilizado'] === 1 ? 'data-utilizado="1"' : ''; ?> title="Eliminar"><i class="fa fa-trash"></i></button>
                                </div></td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($esquemasVacunacion === []): ?>
                        <tr><td colspan="<?php echo $puedeGestionarEsquemas ? 6 : 5; ?>" class="text-center">Aún no hay dosis configuradas.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row top_tiles">
        <div class="col-md-4 col-sm-4 col-xs-12"><div class="x_panel"><div class="x_content text-center">
            <h2><?php echo $totalesSeguimiento['mascotas']; ?></h2><p>Mascotas en seguimiento</p>
        </div></div></div>
        <div class="col-md-4 col-sm-4 col-xs-12"><div class="x_panel"><div class="x_content text-center">
            <h2 class="text-warning"><?php echo $totalesSeguimiento['proximas']; ?></h2><p>Aplicaciones próximas</p>
        </div></div></div>
        <div class="col-md-4 col-sm-4 col-xs-12"><div class="x_panel"><div class="x_content text-center">
            <h2 class="text-danger"><?php echo $totalesSeguimiento['atrasadas']; ?></h2><p>Aplicaciones atrasadas</p>
        </div></div></div>
    </div>

    <div class="row">
        <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group"><label>Propietario</label>
                <select id="filtroPropietarioSeguimiento" class="form-control">
                    <option value="">Todos los propietarios</option>
                    <?php foreach ($propietariosSeguimiento as $idCliente => $propietario): ?>
                        <option value="<?php echo $idCliente; ?>"><?php echo Seguridad::escapar($propietario); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-md-6 col-sm-6 col-xs-12">
            <div class="form-group"><label>Mascota</label>
                <select id="filtroMascotaSeguimiento" class="form-control">
                    <option value="">Todas las mascotas</option>
                    <?php foreach ($seguimientoMascotas as $mascota): ?>
                        <option value="<?php echo (int) $mascota['IDmascota']; ?>"
                                data-cliente="<?php echo (int) $mascota['IDcliente']; ?>">
                            <?php echo Seguridad::escapar($mascota['nombrem'] . ' · ' . $mascota['propietario']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div id="listaSeguimientoVacunas">
        <?php foreach ($seguimientoMascotas as $mascota): ?>
            <div class="x_panel mascota-seguimiento"
                 data-mascota="<?php echo (int) $mascota['IDmascota']; ?>"
                 data-cliente="<?php echo (int) $mascota['IDcliente']; ?>">
                <div class="solo-impresion-seguimiento">
                    <h2>LalyPet's — Esquema individual de vacunación</h2>
                    <p>Generado: <?php echo date('d/m/Y H:i'); ?></p>
                </div>
                <div class="x_title">
                    <h2><i class="fa fa-paw"></i> <?php echo Seguridad::escapar($mascota['nombrem']); ?>
                        <small><?php echo Seguridad::escapar(
                            $mascota['nombre_Especie'] . ' · ' . $mascota['edadTexto']
                            . ' · Propietario: ' . $mascota['propietario']
                        ); ?></small>
                    </h2>
                    <button type="button" class="btn btn-default btn-sm pull-right btnImprimirSeguimiento no-imprimir-seguimiento"
                            title="Imprimir solamente el esquema de esta mascota">
                        <i class="fa fa-print"></i> Imprimir esquema
                    </button>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <?php if ($mascota['vacunas'] === []): ?>
                        <div class="alert alert-info">No existen vacunas activas con esquema para esta especie.</div>
                    <?php else: ?>
                        <div class="table-responsive"><table class="table table-striped table-bordered">
                            <thead><tr>
                                <th>Vacuna</th><th>Progreso</th><th>Detalle de dosis</th>
                                <th>Próxima recomendación</th><th>Estado</th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($mascota['vacunas'] as $vacuna): ?>
                                <?php $proxima = $vacuna['proxima']; ?>
                                <tr>
                                    <td><strong><?php echo Seguridad::escapar($vacuna['nombreVac']); ?></strong></td>
                                    <td><?php echo (int) $vacuna['aplicadas']; ?> de <?php echo (int) $vacuna['totalBase']; ?> dosis base</td>
                                    <td>
                                        <?php foreach ($vacuna['dosisSeguimiento'] as $dosis): ?>
                                            <span class="label label-<?php echo claseEstadoSeguimiento($dosis['estado']); ?>"
                                                  style="display:inline-block;margin:2px 2px 2px 0">
                                                <?php echo $dosis['numero'] === 'R' ? 'Refuerzo' : 'Dosis ' . (int) $dosis['numero']; ?>:
                                                <?php echo Seguridad::escapar($dosis['estado']); ?>
                                                <?php if ($dosis['fechaAplicacion']): ?>
                                                    <?php echo fechaSeguimiento($dosis['fechaAplicacion']); ?>
                                                <?php endif; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <?php if ($proxima): ?>
                                            <?php echo $proxima['numero'] === 'R' ? 'Refuerzo' : 'Dosis ' . (int) $proxima['numero']; ?>
                                            · <?php echo fechaSeguimiento($proxima['fechaRecomendada']); ?>
                                        <?php else: ?>
                                            Sin fecha programada
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($proxima): ?>
                                            <span class="label label-<?php echo claseEstadoSeguimiento($proxima['estado']); ?>">
                                                <?php echo Seguridad::escapar($proxima['estado']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="label label-success">Esquema completo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($seguimientoMascotas === []): ?>
            <div class="alert alert-info">No hay mascotas habilitadas para mostrar.</div>
        <?php endif; ?>
    </div>
    <div id="sinResultadosSeguimiento" class="alert alert-warning" style="display:none">
        No hay mascotas que coincidan con los filtros seleccionados.
    </div>
</div>

<?php if ($puedeGestionarEsquemas): ?>
<?php
$camposEsquema = static function (string $prefijo) use ($especiesEsquema, $vacunasEsquema): void {
    $esEditar = $prefijo === 'editar';
?>
    <div class="form-group"><label>Especie</label>
        <select class="form-control input-lg especieEsquema" id="<?php echo $prefijo; ?>EspecieEsquema" required>
            <option value="">Seleccione una especie</option>
            <?php foreach ($especiesEsquema as $especie): ?>
                <option value="<?php echo (int) $especie['IDespecie']; ?>"><?php echo Seguridad::escapar($especie['nombre_Especie']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group"><label>Vacuna</label>
        <select class="form-control input-lg vacunaEsquema" id="<?php echo $prefijo; ?>VacunaEsquema" name="IDvacunaEsquema" required disabled>
            <option value="">Seleccione primero una especie</option>
            <?php foreach ($vacunasEsquema as $vacuna): ?>
                <option value="<?php echo (int) $vacuna['IDvacuna']; ?>" data-especie="<?php echo (int) $vacuna['IDespecie']; ?>">
                    <?php echo Seguridad::escapar($vacuna['nombreVac'] . ((int) $vacuna['estadoVac'] === 1 ? '' : ' (inhabilitada)')); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <label>Número de dosis</label>
                <input type="number" class="form-control input-lg" id="
                <?php echo $prefijo; ?>NumeroDosis" name="numeroDosis" min="1" max="99" required>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>Edad recomendada</label>
                <input type="number" class="form-control input-lg" id="
                <?php echo $prefijo; ?>EdadAplicacion" name="edadAplicacion" min="1" max="3650" required>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label>Unidad</label>
                <select class="form-control input-lg" id="<?php echo $prefijo; ?>UnidadEdad" name="unidadEdad" required>
                    <option>Días</option>  
                    <option>Semanas</option>
                    <option>Meses</option>
                    <option>Años</option>
                </select>
            </div>
        </div>
    </div>
    <div class="form-group">
        <label>Intervalo para la siguiente aplicación (días)</label>
        <input type="number" class="form-control input-lg" id="<?php echo $prefijo; ?>Intervalo" name="intervaloSiguienteDias" min="1" max="3650" placeholder="Opcional">
        <small class="help-block">Déjelo vacío si esta dosis no requiere una aplicación posterior programada.</small>
    </div>
    <?php if ($esEditar): ?>
        <div id="avisoEsquemaUtilizado" class="alert alert-info" style="display:none">Esta dosis ya fue aplicada. Se puede ajustar la edad o el intervalo, pero no cambiar de vacuna ni de número de dosis.
        </div><?php endif; ?>
    <?php }; ?>

<div id="modalEsquemaVacuna" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="SeguimientoVac">
                <?php echo Seguridad::campoCsrf(); ?><input type="hidden" name="accionEsquema" value="crear">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Agregar dosis al esquema</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body"><?php $camposEsquema('nuevo'); ?></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar dosis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="modalEditarEsquema" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="SeguimientoVac">
                <?php echo Seguridad::campoCsrf(); ?>
                <input type="hidden" name="accionEsquema" value="editar"><input type="hidden" name="IDesquemaVac" id="editarIDesquemaVac">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Modificar dosis del esquema</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body"><?php $camposEsquema('editar'); ?></div>
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
