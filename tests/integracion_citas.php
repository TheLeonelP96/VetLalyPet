<?php

require_once __DIR__ . '/../modelos/Usuarios.modelos.php';
require_once __DIR__ . '/../modelos/Clientes.modelos.php';
require_once __DIR__ . '/../modelos/Mascotas.modelos.php';
require_once __DIR__ . '/../modelos/Citas.modelos.php';

$baseUrl = 'http://localhost/LalyPets/';
$sufijo = bin2hex(random_bytes(4));
$password = 'TemporalCitas_2026!';
$fecha = (new DateTimeImmutable('tomorrow'))->format('Y-m-d');
$idsUsuarios = [];
$idsClientes = [];
$idsMascotas = [];

function verificarCita(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK - $mensaje\n";
}

function navegadorCita(): CurlHandle
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEFILE => '',
        CURLOPT_TIMEOUT => 15,
    ]);
    return $curl;
}

function solicitarCita(CurlHandle $curl, string $url, ?array $datos = null): array
{
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, $datos !== null);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $datos ?? '');
    $respuesta = curl_exec($curl);
    if ($respuesta === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $tamano = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    return [
        'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'headers' => substr($respuesta, 0, $tamano),
        'body' => substr($respuesta, $tamano),
    ];
}

function csrfCita(string $html): string
{
    if (!preg_match('/name=["\']csrf-token["\'] content=["\']([^"\']+)["\']/', $html, $resultado)
        && !preg_match('/name=["\']csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $resultado)) {
        throw new RuntimeException('No se encontró el token CSRF.');
    }
    return html_entity_decode($resultado[1], ENT_QUOTES, 'UTF-8');
}

function crearUsuarioCita(string $correo, string $nombre, int $rol, string $password): int
{
    $respuesta = ModeloUsuarios::mdlCrearUsuario([
        'nombre' => $nombre, 'apellido' => 'Temporal', 'telefono' => '70000200',
        'correo' => $correo, 'password' => password_hash($password, PASSWORD_DEFAULT),
        'turno' => 'Horario continuo', 'foto' => '', 'IDrol' => $rol,
    ]);
    if ($respuesta !== 'ok') {
        throw new RuntimeException('No se pudo preparar el usuario temporal.');
    }
    return (int) ModeloUsuarios::mdlBuscarPorCorreo($correo)['IDusuario'];
}

try {
    $anonimo = navegadorCita();
    verificarCita(solicitarCita($anonimo, $baseUrl . 'ajax/citas.ajax.php', [
        'accion' => 'disponibilidad',
    ])['status'] === 401, 'El AJAX de Citas exige una sesión');
    curl_close($anonimo);

    $correoAdmin = "prueba.citas.admin.$sufijo@example.test";
    $correoVetUno = "prueba.citas.vet1.$sufijo@example.test";
    $correoVetDos = "prueba.citas.vet2.$sufijo@example.test";
    $idAdmin = crearUsuarioCita($correoAdmin, 'Administrador', 1, $password);
    $idVetUno = crearUsuarioCita($correoVetUno, 'Veterinario Uno', 2, $password);
    $idVetDos = crearUsuarioCita($correoVetDos, 'Veterinario Dos', 2, $password);
    $idsUsuarios = [$idAdmin, $idVetUno, $idVetDos];

    verificarCita(ModeloClientes::mdlCrearCliente([
        'nombre' => 'Cliente', 'apellido' => 'Agenda', 'telefono' => '70000201',
        'registro' => date('Y-m-d'),
    ]) === 'ok', 'Se preparó el cliente temporal');
    $stmt = Conexion::conectar()->prepare('SELECT IDcliente FROM clientes WHERE telefono_c = :telefono');
    $stmt->execute([':telefono' => '70000201']);
    $idCliente = (int) $stmt->fetchColumn();
    $idsClientes[] = $idCliente;

    $razas = ModeloMascotas::mdlListarRazas();
    $idRaza = (int) $razas[0]['IDraza'];
    verificarCita(ModeloMascotas::mdlCrearMascota([
        'nombre' => "Agenda$sufijo", 'registro' => date('Y-m-d'),
        'nacimiento' => '2024-01-01', 'peso' => '5.00', 'sexo' => 'Hembra',
        'descripcion' => 'Prueba de citas', 'color' => 'Negro',
        'IDcliente' => $idCliente, 'IDraza' => $idRaza,
    ]) === 'ok', 'Se preparó la mascota temporal');
    $stmt = Conexion::conectar()->prepare('SELECT IDmascota FROM mascotas WHERE IDcliente = :IDcliente');
    $stmt->execute([':IDcliente' => $idCliente]);
    $idMascota = (int) $stmt->fetchColumn();
    $idsMascotas[] = $idMascota;

    $servicios = ModeloCitas::mdlListarServiciosActivos();
    verificarCita($servicios !== [], 'Existe un servicio activo para la agenda');
    $idServicio = (int) $servicios[0]['IDservicio'];

    $navegador = navegadorCita();
    $inicio = solicitarCita($navegador, $baseUrl);
    $login = solicitarCita($navegador, $baseUrl, [
        'csrf_token' => csrfCita($inicio['body']), 'imgEmail' => $correoAdmin, 'imgPassword' => $password,
    ]);
    verificarCita($login['status'] === 302, 'Se inició una sesión administrativa de prueba');
    $calendario = solicitarCita($navegador, $baseUrl . 'Calendar');
    $token = csrfCita($calendario['body']);
    verificarCita($calendario['status'] === 200
        && str_contains($calendario['body'], 'Calendario de citas')
        && str_contains($calendario['body'], 'nuevoServicio'),
        'El calendario carga con el selector de servicios');
    preg_match('/window\.mascotasParaCitas = (\[.*?\]);/s', $calendario['body'], $mascotasVista);
    $mascotasAsociadas = isset($mascotasVista[1]) ? json_decode($mascotasVista[1], true) : null;
    verificarCita(is_array($mascotasAsociadas) && count(array_filter(
        $mascotasAsociadas,
        static fn(array $mascota): bool =>
            (int) $mascota['IDcliente'] === $idCliente && (int) $mascota['IDmascota'] === $idMascota
    )) === 1, 'El calendario precarga la asociación entre cliente y mascota');

    $csrfInvalido = solicitarCita($navegador, $baseUrl . 'ajax/citas.ajax.php', [
        'accion' => 'disponibilidad', 'csrf_token' => 'invalido',
    ]);
    verificarCita($csrfInvalido['status'] === 403, 'El AJAX rechaza un token CSRF inválido');

    $fueraHorario = solicitarCita($navegador, $baseUrl . 'ajax/citas.ajax.php', [
        'accion' => 'disponibilidad', 'csrf_token' => $token, 'IDusuario' => (string) $idVetUno,
        'fechaCita' => $fecha, 'horaCita' => '19:00',
    ]);
    $jsonFuera = json_decode($fueraHorario['body'], true);
    verificarCita($fueraHorario['status'] === 200 && !$jsonFuera['horarioValido'] && !$jsonFuera['disponible'],
        'El servidor rechaza citas fuera del horario de atención');

    $disponible = solicitarCita($navegador, $baseUrl . 'ajax/citas.ajax.php', [
        'accion' => 'disponibilidad', 'csrf_token' => $token, 'IDusuario' => (string) $idVetUno,
        'fechaCita' => $fecha, 'horaCita' => '10:00',
    ]);
    verificarCita((json_decode($disponible['body'], true)['disponible'] ?? false) === true,
        'El AJAX confirma un bloque válido y libre');

    $datosCrear = [
        'csrf_token' => $token, 'nuevoMotivo' => 'Control preventivo',
        'fechaCitaCrear' => $fecha, 'seleccionarHora' => '10:00',
        'seleccionarMascota' => (string) $idMascota, 'nuevoUsuario' => (string) $idVetUno,
        'nuevoServicio' => (string) $idServicio,
    ];
    verificarCita(solicitarCita($navegador, $baseUrl . 'Calendar', $datosCrear)['status'] === 302,
        'El alta de cita usa POST/Redirect/GET');
    $stmt = Conexion::conectar()->prepare(
        'SELECT * FROM citas WHERE IDmascota = :IDmascota AND IDusuario = :IDusuario AND fechaAtencion = :fecha'
    );
    $stmt->execute([':IDmascota' => $idMascota, ':IDusuario' => $idVetUno, ':fecha' => "$fecha 10:00:00"]);
    $citaUno = $stmt->fetch();
    verificarCita(is_array($citaUno) && (int) $citaUno['IDservicio'] === $idServicio
        && $citaUno['estadoCita'] === 'Pendiente', 'La cita guarda todas las relaciones del esquema nuevo');
    $idCitaUno = (int) $citaUno['IDcita'];

    $cantidad = (int) Conexion::conectar()->query('SELECT COUNT(*) FROM citas')->fetchColumn();
    solicitarCita($navegador, $baseUrl . 'Calendar', $datosCrear);
    verificarCita((int) Conexion::conectar()->query('SELECT COUNT(*) FROM citas')->fetchColumn() === $cantidad,
        'No se permite reservar dos citas al mismo veterinario y hora');

    $datosOtroVeterinario = $datosCrear;
    $datosOtroVeterinario['nuevoMotivo'] = 'Segunda opinión';
    $datosOtroVeterinario['nuevoUsuario'] = (string) $idVetDos;
    solicitarCita($navegador, $baseUrl . 'Calendar', $datosOtroVeterinario);
    $stmt = Conexion::conectar()->prepare(
        'SELECT IDcita FROM citas WHERE IDusuario = :IDusuario AND fechaAtencion = :fecha'
    );
    $stmt->execute([':IDusuario' => $idVetDos, ':fecha' => "$fecha 10:00:00"]);
    $idCitaDos = (int) $stmt->fetchColumn();
    verificarCita($idCitaDos > 0, 'Otro veterinario sí puede atender en el mismo bloque');

    $editarEnOcupado = [
        'csrf_token' => $token, 'idCitaEditar' => (string) $idCitaUno,
        'btnAccionCita' => 'guardar', 'editarMotivo' => 'Intento ocupado',
        'fechaCitaEditar' => $fecha, 'editarHora' => '10:00',
        'editarMascota' => (string) $idMascota, 'editarUsuario' => (string) $idVetDos,
        'editarServicio' => (string) $idServicio,
    ];
    solicitarCita($navegador, $baseUrl . 'Calendar', $editarEnOcupado);
    verificarCita((int) ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno)['IDusuario'] === $idVetUno,
        'Una reprogramación tampoco puede ocupar el bloque de otro veterinario');

    $editar = $editarEnOcupado;
    $editar['editarMotivo'] = 'Control reprogramado';
    $editar['editarHora'] = '10:30';
    $editar['editarUsuario'] = (string) $idVetUno;
    solicitarCita($navegador, $baseUrl . 'Calendar', $editar);
    $citaEditada = ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno);
    verificarCita($citaEditada['motivoCita'] === 'Control reprogramado'
        && $citaEditada['fechaAtencion'] === "$fecha 10:30:00", 'La cita puede editarse y reprogramarse');

    solicitarCita($navegador, $baseUrl . 'Calendar', [
        'csrf_token' => $token, 'idCitaEditar' => (string) $idCitaUno, 'btnAccionCita' => 'cancelar',
    ]);
    verificarCita(ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno)['estadoCita'] === 'Cancelada',
        'Cancelar conserva la cita y cambia solamente su estado');

    $datosOcupante = $datosCrear;
    $datosOcupante['nuevoMotivo'] = 'Ocupa reactivación';
    $datosOcupante['seleccionarHora'] = '10:30';
    solicitarCita($navegador, $baseUrl . 'Calendar', $datosOcupante);
    $stmt = Conexion::conectar()->prepare(
        'SELECT IDcita FROM citas WHERE IDusuario = :IDusuario AND fechaAtencion = :fecha AND estadoCita = :estado'
    );
    $stmt->execute([':IDusuario' => $idVetUno, ':fecha' => "$fecha 10:30:00", ':estado' => 'Pendiente']);
    $idOcupante = (int) $stmt->fetchColumn();

    $reactivar = [
        'csrf_token' => $token, 'idCitaEditar' => (string) $idCitaUno,
        'btnAccionCita' => 'reactivar', 'editarMotivo' => 'Control reprogramado',
        'fechaCitaEditar' => $fecha, 'editarHora' => '10:30',
        'editarMascota' => (string) $idMascota, 'editarUsuario' => (string) $idVetUno,
        'editarServicio' => (string) $idServicio,
    ];
    solicitarCita($navegador, $baseUrl . 'Calendar', $reactivar);
    verificarCita(ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno)['estadoCita'] === 'Cancelada',
        'La cita no se reactiva si el horario quedó ocupado');

    ModeloCitas::mdlActualizarEstadoCita($idOcupante, 'Pendiente', 'Cancelada');
    solicitarCita($navegador, $baseUrl . 'Calendar', $reactivar);
    verificarCita(ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno)['estadoCita'] === 'Pendiente',
        'La cita se reactiva con un solo clic cuando el bloque está libre');

    solicitarCita($navegador, $baseUrl . 'Calendar', [
        'csrf_token' => $token, 'idCitaEditar' => (string) $idCitaUno, 'btnAccionCita' => 'completar',
    ]);
    verificarCita(ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno)['estadoCita'] === 'Completada',
        'Una cita pendiente puede marcarse como completada');
    $ocupadoPorCompletada = solicitarCita($navegador, $baseUrl . 'ajax/citas.ajax.php', [
        'accion' => 'disponibilidad', 'csrf_token' => $token, 'IDusuario' => (string) $idVetUno,
        'fechaCita' => $fecha, 'horaCita' => '10:30',
    ]);
    verificarCita((json_decode($ocupadoPorCompletada['body'], true)['disponible'] ?? true) === false,
        'Una cita completada continúa reservando su bloque');
    solicitarCita($navegador, $baseUrl . 'Calendar', [
        'csrf_token' => $token, 'idCitaEditar' => (string) $idCitaUno, 'btnAccionCita' => 'cancelar',
    ]);
    verificarCita(ModeloCitas::mdlMostrarCitas('IDcita', $idCitaUno)['estadoCita'] === 'Completada',
        'Una cita completada no puede cancelarse ni reabrirse');

    $cantidadAntesFuera = (int) Conexion::conectar()->query('SELECT COUNT(*) FROM citas')->fetchColumn();
    $fuera = $datosCrear;
    $fuera['seleccionarHora'] = '06:30';
    solicitarCita($navegador, $baseUrl . 'Calendar', $fuera);
    verificarCita((int) Conexion::conectar()->query('SELECT COUNT(*) FROM citas')->fetchColumn() === $cantidadAntesFuera,
        'El controlador también bloquea altas fuera del horario');

    $fechaConcurrente = "$fecha 14:00:00";
    $inicioComun = microtime(true) + 0.5;
    $comando = static fn() => [
        PHP_BINARY, __DIR__ . '/soporte/crear_cita_concurrente.php', $fechaConcurrente,
        (string) $idVetUno, (string) $idMascota, (string) $idServicio, (string) $inicioComun,
    ];
    $descriptores = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $procesoUno = proc_open($comando(), $descriptores, $tuberiasUno);
    $procesoDos = proc_open($comando(), $descriptores, $tuberiasDos);
    $salidaUno = trim(stream_get_contents($tuberiasUno[1]));
    $salidaDos = trim(stream_get_contents($tuberiasDos[1]));
    fclose($tuberiasUno[1]); fclose($tuberiasUno[2]);
    fclose($tuberiasDos[1]); fclose($tuberiasDos[2]);
    proc_close($procesoUno); proc_close($procesoDos);
    $resultados = [$salidaUno, $salidaDos];
    sort($resultados);
    verificarCita($resultados === ['ocupado', 'ok'],
        'Dos solicitudes concurrentes producen una reserva y un rechazo');

    curl_close($navegador);
} finally {
    if ($idsMascotas !== []) {
        $marcadores = implode(',', array_fill(0, count($idsMascotas), '?'));
        Conexion::conectar()->prepare("DELETE FROM citas WHERE IDmascota IN ($marcadores)")->execute($idsMascotas);
        Conexion::conectar()->prepare("DELETE FROM mascotas WHERE IDmascota IN ($marcadores)")->execute($idsMascotas);
    }
    if ($idsClientes !== []) {
        $marcadores = implode(',', array_fill(0, count($idsClientes), '?'));
        Conexion::conectar()->prepare("DELETE FROM clientes WHERE IDcliente IN ($marcadores)")->execute($idsClientes);
    }
    if ($idsUsuarios !== []) {
        $marcadores = implode(',', array_fill(0, count($idsUsuarios), '?'));
        Conexion::conectar()->prepare("DELETE FROM usuarios WHERE IDusuario IN ($marcadores)")->execute($idsUsuarios);
    }
}

echo "PRUEBAS_CITAS_COMPLETADAS\n";
