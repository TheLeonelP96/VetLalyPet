<?php

date_default_timezone_set('America/La_Paz');

require_once __DIR__ . '/../modelos/Usuarios.modelos.php';
require_once __DIR__ . '/../modelos/Clientes.modelos.php';
require_once __DIR__ . '/../modelos/Mascotas.modelos.php';
require_once __DIR__ . '/../modelos/Vacunas.modelos.php';
require_once __DIR__ . '/../modelos/HistorialVacunas.modelos.php';

$baseUrl = 'http://localhost/LalyPets/';
$sufijo = bin2hex(random_bytes(4));
$correoAdmin = "prueba.historial.$sufijo@example.test";
$passwordAdmin = 'TemporalHist_2026!';
$ids = ['historiales' => [], 'citas' => [], 'mascotas' => [], 'clientes' => [], 'vacunas' => [], 'usuarios' => []];
$stocksOriginales = [];

function verificarHV(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK - $mensaje\n";
}

function clienteHttpHV(): CurlHandle
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 15,
    ]);
    return $curl;
}

function solicitarHV(CurlHandle $curl, string $url, ?array $datos = null): array
{
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, $datos !== null);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $datos ?? '');
    $respuesta = curl_exec($curl);
    if ($respuesta === false) {
        throw new RuntimeException(curl_error($curl));
    }
    $cabecera = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
    return [
        'status' => curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
        'headers' => substr($respuesta, 0, $cabecera),
        'body' => substr($respuesta, $cabecera),
    ];
}

function csrfHV(string $html): string
{
    if (!preg_match('/name=["\']csrf-token["\'] content=["\']([^"\']+)["\']/', $html, $resultado)
        && !preg_match('/name=["\']csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $resultado)) {
        throw new RuntimeException('No se encontró el token CSRF.');
    }
    return html_entity_decode($resultado[1], ENT_QUOTES, 'UTF-8');
}

try {
    $anonimo = clienteHttpHV();
    verificarHV(solicitarHV($anonimo, $baseUrl . 'ajax/historialVacunas.ajax.php', [
        'accion' => 'historial', 'IDhistorialVac' => '1',
    ])['status'] === 401, 'El AJAX exige una sesión');
    curl_close($anonimo);

    verificarHV(ModeloUsuarios::mdlCrearUsuario([
        'nombre' => 'Admin', 'apellido' => 'Temporal Historial', 'telefono' => '70000301',
        'correo' => $correoAdmin, 'password' => password_hash($passwordAdmin, PASSWORD_DEFAULT),
        'turno' => 'Mañana', 'foto' => '', 'IDrol' => 1,
    ]) === 'ok', 'Se preparó el administrador temporal');
    $admin = ModeloUsuarios::mdlBuscarPorCorreo($correoAdmin);
    $idUsuario = (int) $admin['IDusuario'];
    $ids['usuarios'][] = $idUsuario;

    verificarHV(ModeloClientes::mdlCrearCliente([
        'nombre' => 'Cliente', 'apellido' => "Historial $sufijo",
        'telefono' => '70000302', 'registro' => date('Y-m-d'),
    ]) === 'ok', 'Se preparó el propietario temporal');
    $stmt = Conexion::conectar()->prepare('SELECT IDcliente FROM clientes WHERE telefono_c = :telefono ORDER BY IDcliente DESC LIMIT 1');
    $stmt->execute([':telefono' => '70000302']);
    $idCliente = (int) $stmt->fetchColumn();
    $ids['clientes'][] = $idCliente;

    $stmt = Conexion::conectar()->query(
        "SELECT r.IDraza FROM raza r INNER JOIN especie e ON e.IDespecie = r.IDespecie
         WHERE e.nombre_Especie = 'Canino' ORDER BY r.IDraza LIMIT 1"
    );
    $idRazaCanina = (int) $stmt->fetchColumn();
    verificarHV($idRazaCanina > 0, 'Existe una raza canina para la prueba');
    $nombreMascota = "CaninoHist$sufijo";
    verificarHV(ModeloMascotas::mdlCrearMascota([
        'nombre' => $nombreMascota, 'registro' => date('Y-m-d'), 'nacimiento' => '2024-01-01',
        'peso' => '8.50', 'sexo' => 'Macho', 'descripcion' => 'Prueba historial', 'color' => 'Marrón',
        'IDcliente' => $idCliente, 'IDraza' => $idRazaCanina,
    ]) === 'ok', 'Se preparó la mascota canina temporal');
    $stmt = Conexion::conectar()->prepare('SELECT IDmascota FROM mascotas WHERE nombrem = :nombre');
    $stmt->execute([':nombre' => $nombreMascota]);
    $idMascota = (int) $stmt->fetchColumn();
    $ids['mascotas'][] = $idMascota;

    $vacunasCaninas = ModeloVacunas::mdlMostrarVacunas('IDespecie', 3);
    if (isset($vacunasCaninas['IDvacuna'])) {
        $vacunasCaninas = [$vacunasCaninas];
    }
    $vacunaCanina = $vacunasCaninas[0] ?? null;
    verificarHV(is_array($vacunaCanina) && (int) $vacunaCanina['stock'] > 0,
        'Existe una vacuna canina disponible');
    $idVacunaCanina = (int) $vacunaCanina['IDvacuna'];
    $stockCaninaInicial = (int) $vacunaCanina['stock'];
    $stocksOriginales[$idVacunaCanina] = $stockCaninaInicial;

    $vacunasFelinas = ModeloVacunas::mdlMostrarVacunas('IDespecie', 2);
    if (isset($vacunasFelinas['IDvacuna'])) {
        $vacunasFelinas = [$vacunasFelinas];
    }
    $idVacunaFelina = (int) $vacunasFelinas[0]['IDvacuna'];

    $nombreVacunaTemporal = "Refuerzo canino $sufijo";
    verificarHV(ModeloVacunas::mdlCrearVacuna([
        'nombre' => $nombreVacunaTemporal, 'descripcion' => 'Vacuna temporal de integración',
        'precio' => '75.00', 'stock' => 3, 'IDespecie' => 3,
    ]) === 'ok', 'Se preparó una segunda vacuna canina');
    $stmt = Conexion::conectar()->prepare('SELECT IDvacuna FROM vacuna WHERE nombreVac = :nombre AND IDespecie = 3');
    $stmt->execute([':nombre' => $nombreVacunaTemporal]);
    $idVacunaTemporal = (int) $stmt->fetchColumn();
    $ids['vacunas'][] = $idVacunaTemporal;

    $idServicio = (int) Conexion::conectar()->query(
        "SELECT IDservicio FROM servicios WHERE estadoServicio = 1 AND tipoServicio = 'Vacunación' LIMIT 1"
    )->fetchColumn();
    $idServicioPeluqueria = (int) Conexion::conectar()->query(
        "SELECT IDservicio FROM servicios WHERE estadoServicio = 1 AND tipoServicio = 'Peluquería + baño' LIMIT 1"
    )->fetchColumn();
    verificarHV($idServicio > 0 && $idServicioPeluqueria > 0,
        'Las citas ofrecen Vacunación y Peluquería + baño');
    $fechaAplicacion = date('Y-m-d');
    $conexionCita = Conexion::conectar();
    $stmt = $conexionCita->prepare(
        "INSERT INTO citas (motivoCita, estadoCita, fechaAtencion, IDusuario, IDmascota, IDservicio)
         VALUES ('Vacunación temporal', 'Completada', :fecha, :usuario, :mascota, :servicio)"
    );
    $stmt->execute([
        ':fecha' => $fechaAplicacion . ' 10:00:00', ':usuario' => $idUsuario,
        ':mascota' => $idMascota, ':servicio' => $idServicio,
    ]);
    $idCita = (int) $conexionCita->lastInsertId();
    $ids['citas'][] = $idCita;
    $stmt = $conexionCita->prepare(
        "INSERT INTO citas (motivoCita, estadoCita, fechaAtencion, IDusuario, IDmascota, IDservicio)
         VALUES ('Peluquería temporal', 'Completada', :fecha, :usuario, :mascota, :servicio)"
    );
    $stmt->execute([
        ':fecha' => $fechaAplicacion . ' 11:00:00', ':usuario' => $idUsuario,
        ':mascota' => $idMascota, ':servicio' => $idServicioPeluqueria,
    ]);
    $idCitaPeluqueria = (int) $conexionCita->lastInsertId();
    $ids['citas'][] = $idCitaPeluqueria;

    $navegador = clienteHttpHV();
    $inicio = solicitarHV($navegador, $baseUrl);
    $login = solicitarHV($navegador, $baseUrl, [
        'csrf_token' => csrfHV($inicio['body']), 'imgEmail' => $correoAdmin, 'imgPassword' => $passwordAdmin,
    ]);
    verificarHV($login['status'] === 302, 'Se inició una sesión de prueba');

    $pagina = solicitarHV($navegador, $baseUrl . 'HistorialVac');
    $token = csrfHV($pagina['body']);
    verificarHV($pagina['status'] === 200 && str_contains($pagina['body'], 'Historial de vacunación'),
        'El módulo principal abre correctamente');
    verificarHV(!preg_match('/<th>\s*ID\s*<\/th>/i', $pagina['body'])
        && !str_contains($pagina['body'], 'Eliminar'), 'El historial no muestra ID ni ofrece borrado');

    $opciones = solicitarHV($navegador, $baseUrl . 'ajax/historialVacunas.ajax.php', [
        'csrf_token' => $token, 'accion' => 'opciones_mascota', 'IDmascota' => (string) $idMascota,
    ]);
    $jsonOpciones = json_decode($opciones['body'], true);
    $idsVacunasOfrecidas = array_map('intval', array_column($jsonOpciones['vacunas'] ?? [], 'IDvacuna'));
    verificarHV($opciones['status'] === 200
        && in_array($idVacunaCanina, $idsVacunasOfrecidas, true)
        && !in_array($idVacunaFelina, $idsVacunasOfrecidas, true),
        'El selector ofrece vacunas compatibles y excluye las de otra especie');
    $idsCitasOfrecidas = array_map('intval', array_column($jsonOpciones['citas'] ?? [], 'IDcita'));
    verificarHV(in_array($idCita, $idsCitasOfrecidas, true)
        && !in_array($idCitaPeluqueria, $idsCitasOfrecidas, true),
        'El historial ofrece la cita de vacunación y excluye la de peluquería');

    $alta = solicitarHV($navegador, $baseUrl . 'HistorialVac', [
        'csrf_token' => $token,
        'nuevaFechaAplicacion' => $fechaAplicacion,
        'nuevaFechaProximaDosis' => date('Y-m-d', strtotime('+30 days')),
        'nuevaObservacionVacuna' => 'Primera aplicación de prueba',
        'nuevaVacunaHistorial' => (string) $idVacunaCanina,
        'nuevaCitaHistorial' => (string) $idCita,
        'nuevaMascotaHistorial' => (string) $idMascota,
    ]);
    verificarHV($alta['status'] === 302, 'El alta usa POST/Redirect/GET');
    $stmt = Conexion::conectar()->prepare(
        'SELECT IDhistorialVac FROM historialvac WHERE IDmascota = :mascota AND IDvacuna = :vacuna AND fechaAplicacion = :fecha'
    );
    $stmt->execute([':mascota' => $idMascota, ':vacuna' => $idVacunaCanina, ':fecha' => $fechaAplicacion]);
    $idHistorial = (int) $stmt->fetchColumn();
    if ($idHistorial < 1) {
        $paginaError = solicitarHV($navegador, $baseUrl . 'HistorialVac');
        preg_match('/<div class="alert[^>]*>(.*?)<\/div>/s', $paginaError['body'], $alerta);
        throw new RuntimeException('No se creó el historial: ' . trim(strip_tags($alerta[1] ?? 'sin mensaje')));
    }
    verificarHV($idHistorial > 0, 'La aplicación quedó relacionada con mascota, vacuna y cita');
    $ids['historiales'][] = $idHistorial;
    verificarHV((int) ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaCanina)['stock'] === $stockCaninaInicial - 1,
        'Registrar una aplicación descuenta exactamente una dosis');

    solicitarHV($navegador, $baseUrl . 'HistorialVac', [
        'csrf_token' => $token, 'nuevaFechaAplicacion' => $fechaAplicacion,
        'nuevaFechaProximaDosis' => '', 'nuevaObservacionVacuna' => 'Duplicada',
        'nuevaVacunaHistorial' => (string) $idVacunaCanina, 'nuevaCitaHistorial' => '',
        'nuevaMascotaHistorial' => (string) $idMascota,
    ]);
    verificarHV((int) ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaCanina)['stock'] === $stockCaninaInicial - 1,
        'Un registro duplicado no vuelve a descontar stock');

    $conteoAntes = (int) Conexion::conectar()->query('SELECT COUNT(*) FROM historialvac')->fetchColumn();
    solicitarHV($navegador, $baseUrl . 'HistorialVac', [
        'csrf_token' => $token, 'nuevaFechaAplicacion' => $fechaAplicacion,
        'nuevaFechaProximaDosis' => '', 'nuevaObservacionVacuna' => 'Incompatible',
        'nuevaVacunaHistorial' => (string) $idVacunaFelina, 'nuevaCitaHistorial' => '',
        'nuevaMascotaHistorial' => (string) $idMascota,
    ]);
    verificarHV((int) Conexion::conectar()->query('SELECT COUNT(*) FROM historialvac')->fetchColumn() === $conteoAntes,
        'El servidor rechaza una vacuna incompatible con la especie');

    $ajax = solicitarHV($navegador, $baseUrl . 'ajax/historialVacunas.ajax.php', [
        'csrf_token' => $token, 'accion' => 'historial', 'IDhistorialVac' => (string) $idHistorial,
    ]);
    $jsonHistorial = json_decode($ajax['body'], true);
    verificarHV($ajax['status'] === 200 && (int) $jsonHistorial['IDmascota'] === $idMascota
        && (int) $jsonHistorial['IDvacuna'] === $idVacunaCanina,
        'El AJAX devuelve todas las relaciones para corregir el registro');

    solicitarHV($navegador, $baseUrl . 'HistorialVac', [
        'csrf_token' => $token, 'IDhistorialVac' => (string) $idHistorial,
        'editarFechaAplicacion' => $fechaAplicacion,
        'editarFechaProximaDosis' => date('Y-m-d', strtotime('+60 days')),
        'editarObservacionVacuna' => 'Aplicación corregida',
        'editarVacunaHistorial' => (string) $idVacunaTemporal,
        'editarCitaHistorial' => (string) $idCita,
        'editarMascotaHistorial' => (string) $idMascota,
    ]);
    $editado = ModeloHistorialVacunas::mdlMostrarHistorial('IDhistorialVac', $idHistorial);
    verificarHV((int) $editado['IDvacuna'] === $idVacunaTemporal
        && $editado['observaciones'] === 'Aplicación corregida', 'La corrección actualiza el historial');
    verificarHV((int) ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaCanina)['stock'] === $stockCaninaInicial
        && (int) ModeloVacunas::mdlMostrarVacunas('IDvacuna', $idVacunaTemporal)['stock'] === 2,
        'Cambiar la vacuna devuelve la dosis anterior y descuenta la nueva');
    curl_close($navegador);
} finally {
    foreach (['historiales' => 'historialvac:IDhistorialVac', 'citas' => 'citas:IDcita',
        'mascotas' => 'mascotas:IDmascota', 'clientes' => 'clientes:IDcliente',
        'vacunas' => 'vacuna:IDvacuna', 'usuarios' => 'usuarios:IDusuario'] as $grupo => $destino) {
        if ($ids[$grupo] === []) {
            continue;
        }
        [$tabla, $columna] = explode(':', $destino);
        $marcadores = implode(',', array_fill(0, count($ids[$grupo]), '?'));
        Conexion::conectar()->prepare("DELETE FROM $tabla WHERE $columna IN ($marcadores)")->execute($ids[$grupo]);
    }
    foreach ($stocksOriginales as $idVacuna => $stockOriginal) {
        Conexion::conectar()->prepare('UPDATE vacuna SET stock = :stock WHERE IDvacuna = :IDvacuna')
            ->execute([':stock' => $stockOriginal, ':IDvacuna' => $idVacuna]);
    }
}

echo "PRUEBAS_HISTORIAL_VACUNAS_COMPLETADAS\n";
