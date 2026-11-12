<?php
$usuarios = ControladorUsuarios::ctrMostrarUsuarios();
$flash = $_SESSION['usuario_flash'] ?? null;
unset($_SESSION['usuario_flash']);
$roles = [1 => 'Administrador', 2 => 'Veterinario', 3 => 'Recepcionista'];
$turnos = ['Mañana', 'Tarde', 'Horario continuo'];
?>
<div class="right_col" role="main">
    <div class="page-title">
        <div class="title_left">
            <h3>Usuarios <small>Acceso mediante correo electrónico</small></h3>
        </div>
        <div class="title_right">
            <button class="btn btn-primary pull-right" data-toggle="modal" data-target="#miModal">
                <i class="fa fa-plus"></i> Agregar usuario
            </button>
        </div>
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
            <div class="x_title"><h2>Usuarios registrados</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table id="datatable-fixed-header" class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th>Foto</th>
                        <th>Nombre</th>
                        <th>Rol</th>
                        <th>Correo</th>
                        <th>Turno</th>
                        <th>Sesión</th>
                        <th>Cuenta</th>
                        <th>Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usuarios as $usuario): ?>
                        <?php
                        $id = (int) $usuario['IDusuario'];
                        $habilitado = (int) $usuario['estado_U'] === 1;
                        $foto = $usuario['foto'] !== '' ? $usuario['foto'] : 'vistas/js/img/img.jpg';
                        ?>
                        <tr>
                            <td><img src="<?php echo Seguridad::escapar($foto); ?>" class="avatar avatar-sm me-3" alt=""></td>
                            <td>
                                <strong><?php echo Seguridad::escapar($usuario['nombre_U'] . ' ' . $usuario['apellido_U']); ?></strong><br>
                                <small><?php echo Seguridad::escapar($usuario['telefono_U']); ?></small>
                            </td>
                            <td><?php echo Seguridad::escapar($usuario['nombre_Rol']); ?></td>
                            <td><?php echo Seguridad::escapar($usuario['email_U']); ?></td>
                            <td><?php echo Seguridad::escapar($usuario['turno_U']); ?></td>
                            <td><?php echo (int) $usuario['sesion_U'] === 1 ? 'En línea' : 'Desconectado'; ?></td>
                            <td>
                                <span class="label label-<?php echo $habilitado ? 'success' : 'default'; ?>">
                                    <?php echo $habilitado ? 'Habilitada' : 'Deshabilitada'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning btnEditUsuario"
                                            data-id="<?php echo $id; ?>"
                                            title="Editar"><i class="fa fa-pencil"></i></button>
                                    <button type="button"
                                            class="btn <?php echo $habilitado ? 'btn-danger' : 'btn-success'; ?> btnEstadoUsuario"
                                            data-id="<?php echo $id; ?>" data-estado="<?php echo $habilitado ? 0 : 1; ?>"
                                            <?php echo $id === (int) $_SESSION['IDusuario'] && $habilitado ? 'disabled title="No puede deshabilitar su propia cuenta"' : ''; ?>>
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

<div id="miModal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="Usuarios" enctype="multipart/form-data">
                <?php echo Seguridad::campoCsrf(); ?>
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Agregar usuario</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <input type="text" class="form-control input-lg" name="nuevoNombre" maxlength="50" placeholder="Nombre" required>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control input-lg" name="nuevoApellido" maxlength="50" placeholder="Apellido" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" class="form-control input-lg" name="nuevoTelefono" maxlength="20" placeholder="Teléfono" required>
                        </div>
                        <div class="form-group">
                            <input type="email" class="form-control input-lg" name="nuevoEmail" maxlength="254" placeholder="Correo electrónico (será su acceso)" required>
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control input-lg" name="nuevoPassword" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Contraseña (mínimo 8 caracteres)" required>
                        </div>
                        <div class="form-group">
                            <select class="form-control input-lg" name="nuevoRol" required>
                                <option value="" selected disabled>Seleccionar rol</option>
                                <?php foreach ($roles as $idRol => $rol): ?><option value="<?php echo $idRol; ?>"><?php echo Seguridad::escapar($rol); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select class="form-control input-lg" name="nuevoTurno" required>
                                <option value="" selected disabled>Seleccionar turno</option>
                                <?php foreach ($turnos as $turno): ?><option value="<?php echo Seguridad::escapar($turno); ?>"><?php echo Seguridad::escapar($turno); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Foto (opcional)</label>
                            <input type="file" class="nuevaFoto" name="nuevaFoto" accept="image/jpeg,image/png">
                            <p class="help-block">JPG o PNG, máximo 2 MB.</p>
                            <img src="vistas/js/img/img.jpg" class="img-thumbnail previsualizar" width="100" alt="Vista previa">
                        </div>
                    </div>
                </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary">Guardar usuario</button>
            </div>
        </form>
    </div></div>
</div>

<div id="modUsuario" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="Usuarios" enctype="multipart/form-data">
                <?php echo Seguridad::campoCsrf(); ?>
                <input type="hidden" name="IDusuario" id="IDusuario">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Modificar usuario</h4>
                </div>
                <div class="modal-body">
                    <div class="box-body">
                        <div class="form-group">
                            <input type="text" class="form-control input-lg" id="editarNombre" name="editarNombre" maxlength="50" placeholder="Nombre" required>
                        </div>
                        <div class="form-group">
                            <input type="text" class="form-control input-lg" id="editarApellido" name="editarApellido" maxlength="50" placeholder="Apellido" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" class="form-control input-lg" id="editarTelefono" name="editarTelefono" maxlength="20" placeholder="Teléfono" required>
                        </div>
                        <div class="form-group">
                            <input type="email" class="form-control input-lg" id="editarEmail" name="editarEmail" maxlength="254" placeholder="Correo electrónico" required>
                        </div>
                        <div class="form-group">
                            <input type="password" class="form-control input-lg" id="editarPassword" name="editarPassword" minlength="8" maxlength="72" autocomplete="new-password" placeholder="Dejar vacío para conservar la contraseña">
                        </div>
                        <div class="form-group">
                            <select class="form-control input-lg" id="editarRol" name="editarRol" required>
                                <?php foreach ($roles as $idRol => $rol): ?><option value="<?php echo $idRol; ?>"><?php echo Seguridad::escapar($rol); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <select class="form-control input-lg" id="editarTurno" name="editarTurno" required>
                                <?php foreach ($turnos as $turno): ?><option value="<?php echo Seguridad::escapar($turno); ?>"><?php echo Seguridad::escapar($turno); ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Reemplazar foto (opcional)</label>
                            <input type="file" class="nuevaFoto" name="editarFoto" accept="image/jpeg,image/png">
                            <p class="help-block">JPG o PNG, máximo 2 MB.</p>
                            <img src="vistas/js/img/img.jpg" class="img-thumbnail previsualizar" width="100" alt="Vista previa">
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
