<?php
$mascotas = ControladorMascotas::crtMostrarMascotas();
$clientes = ControladorClientes::ctrListarClientesHabilitados();
$especies = ControladorMascotas::ctrListarEspecies();
$razas = ControladorMascotas::ctrListarRazas();
$flash = $_SESSION['mascota_flash'] ?? null;
unset($_SESSION['mascota_flash']);
?>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left"><h3>Mascotas <small>Pacientes veterinarios</small></h3></div>
        <div class="title_right">
            <?php if ((int) ($_SESSION['IDrol'] ?? 0) === 1): ?>
                <button class="btn btn-info pull-right" data-toggle="modal" data-target="#modalEspecieRaza">
                    <i class="fa fa-paw"></i> Agregar especie y raza
                </button>
            <?php endif; ?>
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#modalMascota"
                    <?php echo $clientes === [] ? 'disabled title="Primero registre un cliente habilitado"' : ''; ?>>
                <i class="fa fa-plus"></i> Agregar mascota
            </button>
            <button class="btn btn-success pull-right" data-toggle="modal" data-target="#modalClienteMascota">
                <i class="fa fa-user-plus"></i> Agregar cliente y mascota
            </button>
        </div>
    </div>
    <div class="clearfix"></div>

    <?php if ($clientes === []): ?>
        <div class="alert alert-warning">Debe existir al menos un cliente habilitado antes de registrar mascotas.</div>
    <?php endif; ?>
    <?php if (is_array($flash)): ?>
        <div class="app-flash alert alert-<?php echo Seguridad::escapar($flash['tipo']); ?> alert-dismissible" data-tipo="<?php echo Seguridad::escapar($flash['tipo']); ?>" role="alert">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?php echo Seguridad::escapar($flash['mensaje']); ?>
        </div>
    <?php endif; ?>

    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="x_panel">
            <div class="x_title"><h2>Mascotas registradas</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead><tr>
                        <th>Nombre</th><th>Dueño</th><th>Especie / raza</th><th>Nacimiento</th>
                        <th>Sexo</th><th>Peso</th><th>Detalles</th><th>Estado</th><th>Acciones</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($mascotas as $mascota): ?>
                        <?php $habilitada = (int) $mascota['habilitadoM'] === 1; ?>
                        <tr>
                            <td>
                                <strong><?php echo Seguridad::escapar($mascota['nombrem']); ?></strong><br>
                                <small>Registro: <?php echo Seguridad::escapar($mascota['fecha_Registro']); ?></small>
                            </td>
                            <td><?php echo Seguridad::escapar($mascota['nombrec'] . ' ' . $mascota['apellidoc']); ?></td>
                            <td>
                                <?php echo Seguridad::escapar($mascota['nombre_Especie']); ?><br>
                                <small><?php echo Seguridad::escapar($mascota['nombre_Raza']); ?></small>
                            </td>
                            <td>
                                <?php echo Seguridad::escapar($mascota['fechaNac_m']); ?><br>
                                <small><?php
                                $edadAnios = (int) $mascota['edad'];
                                $edadMeses = (int) $mascota['edadMeses'];
                                $edadDias = max(0, (int) $mascota['edadDias']);
                                if ($edadAnios > 0) {
                                    $mesesRestantes = $edadMeses % 12;
                                    echo $edadAnios . ($edadAnios === 1 ? ' año' : ' años');
                                    if ($mesesRestantes > 0) {
                                        echo ' y ' . $mesesRestantes . ($mesesRestantes === 1 ? ' mes' : ' meses');
                                    }
                                } elseif ($edadMeses > 0) {
                                    echo $edadMeses . ($edadMeses === 1 ? ' mes' : ' meses');
                                } else {
                                    echo $edadDias . ($edadDias === 1 ? ' día' : ' días');
                                }
                                ?></small>
                            </td>
                            <td><?php echo Seguridad::escapar($mascota['sexo']); ?></td>
                            <td><?php echo Seguridad::escapar($mascota['peso']); ?> kg</td>
                            <td>
                                <?php if (!empty($mascota['color'])): ?>
                                    <small>Color: <?php echo Seguridad::escapar($mascota['color']); ?></small><br>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="label label-<?php echo $habilitada ? 'success' : 'default'; ?>">
                                    <?php echo $habilitada ? 'Habilitada' : 'Deshabilitada'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning btnEditMascota"
                                            data-id="<?php echo (int) $mascota['IDmascota']; ?>"
                                            title="Editar">
                                        <i class="fa fa-pencil"></i>
                                    </button>
                                    <?php if ((int) ($_SESSION['IDrol'] ?? 0) === 1): ?><button type="button"
                                            class="btn <?php echo $habilitada ? 'btn-danger' : 'btn-success'; ?> btnEstadoMascota"
                                            data-id="<?php echo (int) $mascota['IDmascota']; ?>"
                                            data-estado="<?php echo $habilitada ? 0 : 1; ?>"
                                            title="<?php echo $habilitada ? 'Deshabilitar' : 'Habilitar'; ?>">
                                        <i class="fa <?php echo $habilitada ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </button>
                                    <?php endif; ?>
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

<div id="modalClienteMascota" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="post" action="Mascotas">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="crearClienteMascota" value="1">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Agregar cliente y mascota</h4>
            </div>
            <div class="modal-body"><div class="row">
                <div class="col-md-6">
                    <h4>Datos del cliente</h4>
                    <div class="form-group"><input type="text" class="form-control input-lg" name="conjuntoNombreCliente" maxlength="50" placeholder="Nombre" required></div>
                    <div class="form-group"><input type="text" class="form-control input-lg" name="conjuntoApellidoCliente" maxlength="50" placeholder="Apellido" required></div>
                    <div class="form-group"><input type="tel" class="form-control input-lg" name="conjuntoTelefonoCliente" maxlength="20" placeholder="Teléfono" required></div>
                    <div class="form-group">
                        <label>Fecha de registro del cliente</label>
                        <input type="date" class="form-control input-lg" name="conjuntoRegistroCliente"
                               value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4>Datos de la mascota</h4>
                    <div class="form-group"><input type="text" class="form-control input-lg" name="conjuntoNombreMascota" maxlength="50" placeholder="Nombre de la mascota" required></div>
                    <div class="form-group">
                        <label>Especie</label>
                        <select class="form-control input-lg" id="conjuntoEspecieMascota" required>
                            <option value="" selected disabled>Seleccionar especie</option>
                            <?php opcionesEspeciesMascota($especies); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Raza</label>
                        <select class="form-control input-lg" id="conjuntoRazaMascota" name="conjuntoRazaMascota" required>
                            <option value="" selected disabled>Seleccionar raza</option>
                            <?php opcionesRazasMascota($razas); ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Fecha de nacimiento</label>
                        <input type="date" class="form-control input-lg" name="conjuntoNacimientoMascota" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de registro de la mascota</label>
                        <input type="date" class="form-control input-lg" name="conjuntoRegistroMascota"
                               value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group"><input type="number" class="form-control input-lg" name="conjuntoPesoMascota" min="0.01" max="9999.99" step="0.01" placeholder="Peso en kg" required></div>
                    <div class="form-group">
                        <select class="form-control input-lg" name="conjuntoSexoMascota" required>
                            <option value="" selected disabled>Seleccionar sexo</option>
                            <option value="Macho">Macho</option><option value="Hembra">Hembra</option>
                        </select>
                    </div>
                    <div class="form-group"><input type="text" class="form-control input-lg" name="conjuntoColorMascota" maxlength="50" placeholder="Color (opcional)"></div>
                </div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-success">Guardar cliente y mascota</button>
            </div>
        </form>
    </div></div>
</div>

<?php if ((int) ($_SESSION['IDrol'] ?? 0) === 1): ?>
<div id="modalEspecieRaza" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Mascotas">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="crearEspecieRaza" value="1">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Agregar especie y raza</h4>
            </div>
            <div class="modal-body">
                <p class="text-muted">La nueva raza quedará vinculada automáticamente con esta especie.</p>
                <div class="form-group">
                    <label>Nombre de la especie</label>
                    <input type="text" class="form-control input-lg" name="nombreEspecie" maxlength="50" placeholder="Ej.: Ave" required>
                </div>
                <div class="form-group">
                    <label>Nombre de la raza</label>
                    <input type="text" class="form-control input-lg" name="nombreRaza" maxlength="50" placeholder="Ej.: Canario doméstico" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-info">Guardar especie y raza</button>
            </div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<?php
function opcionesClientesMascota(array $clientes): void
{
    foreach ($clientes as $cliente) {
        echo '<option value="' . (int) $cliente['IDcliente'] . '">'
            . Seguridad::escapar($cliente['nombrec'] . ' ' . $cliente['apellidoc'])
            . '</option>';
    }
}

function opcionesEspeciesMascota(array $especies): void
{
    foreach ($especies as $especie) {
        echo '<option value="' . (int) $especie['IDespecie'] . '">'
            . Seguridad::escapar($especie['nombre_Especie']) . '</option>';
    }
}

function opcionesRazasMascota(array $razas): void
{
    foreach ($razas as $raza) {
        echo '<option value="' . (int) $raza['IDraza'] . '" data-especie="'
            . (int) $raza['IDespecie'] . '">' . Seguridad::escapar($raza['nombre_Raza']) . '</option>';
    }
}
?>

<div id="modalMascota" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Mascotas">
            <?php echo Seguridad::campoCsrf(); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Agregar mascota</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><input type="text" class="form-control input-lg" name="nuevoMascota" maxlength="50" placeholder="Nombre de la mascota" required></div>
                <div class="form-group">
                    <label>Dueño</label>
                    <select class="form-control input-lg" name="nuevoCliente" required>
                        <option value="" selected disabled>Seleccionar cliente</option>
                        <?php opcionesClientesMascota($clientes); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Especie</label>
                    <select class="form-control input-lg" id="nuevaEspecie" required>
                        <option value="" selected disabled>Seleccionar especie</option>
                        <?php opcionesEspeciesMascota($especies); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Raza</label>
                    <select class="form-control input-lg" id="nuevaRaza" name="nuevaRaza" required>
                        <option value="" selected disabled>Seleccionar raza</option>
                        <?php opcionesRazasMascota($razas); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" class="form-control input-lg" name="nuevaFechaNacimiento"
                           max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Fecha de registro</label>
                    <input type="date" class="form-control input-lg" name="nuevaFechaRegistro"
                           value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group"><input type="number" class="form-control input-lg" name="nuevoPeso" min="0.01" max="9999.99" step="0.01" placeholder="Peso en kg" required></div>
                <div class="form-group">
                    <select class="form-control input-lg" name="nuevoSexo" required>
                        <option value="" selected disabled>Seleccionar sexo</option>
                        <option value="Macho">Macho</option><option value="Hembra">Hembra</option>
                    </select>
                </div>
                <div class="form-group"><input type="text" class="form-control input-lg" name="nuevoColor" maxlength="50" placeholder="Color (opcional)"></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar mascota</button>
            </div>
        </form>
    </div></div>
</div>

<div id="modMascota" class="modal fade" role="dialog">
    <div class="modal-dialog"><div class="modal-content">
        <form method="post" action="Mascotas">
            <?php echo Seguridad::campoCsrf(); ?>
            <input type="hidden" name="IDmascota" id="IDmascota">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Modificar mascota</h4>
            </div>
            <div class="modal-body"><div class="box-body">
                <div class="form-group"><input type="text" class="form-control input-lg" id="editarMascota" name="editarMascota" maxlength="50" placeholder="Nombre de la mascota" required></div>
                <div class="form-group">
                    <label>Dueño</label>
                    <select class="form-control input-lg" id="editarCliente" name="editarCliente" required>
                        <?php opcionesClientesMascota($clientes); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Especie</label>
                    <select class="form-control input-lg" id="editarEspecie" required>
                        <?php opcionesEspeciesMascota($especies); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Raza</label>
                    <select class="form-control input-lg" id="editarRaza" name="editarRaza" required>
                        <option value="">Seleccionar raza</option>
                        <?php opcionesRazasMascota($razas); ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fecha de nacimiento</label>
                    <input type="date" class="form-control input-lg" id="editarFechaNacimiento"
                           name="editarFechaNacimiento" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Fecha de registro</label>
                    <input type="date" class="form-control input-lg" id="editarFechaRegistro"
                           name="editarFechaRegistro" max="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group"><input type="number" class="form-control input-lg" id="editarPeso" name="editarPeso" min="0.01" max="9999.99" step="0.01" placeholder="Peso en kg" required></div>
                <div class="form-group">
                    <select class="form-control input-lg" id="editarSexo" name="editarSexo" required>
                        <option value="Macho">Macho</option><option value="Hembra">Hembra</option>
                    </select>
                </div>
                <div class="form-group"><input type="text" class="form-control input-lg" id="editarColor" name="editarColor" maxlength="50" placeholder="Color (opcional)"></div>
            </div></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>
    </div></div>
</div>
