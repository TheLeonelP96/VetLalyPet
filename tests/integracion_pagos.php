<?php

date_default_timezone_set('America/La_Paz');

require_once __DIR__ . '/../modelos/Usuarios.modelos.php';
require_once __DIR__ . '/../modelos/Clientes.modelos.php';
require_once __DIR__ . '/../modelos/Mascotas.modelos.php';
require_once __DIR__ . '/../modelos/Pagos.modelos.php';
require_once __DIR__ . '/../modelos/HistorialVacunas.modelos.php';

$baseUrl = 'http://localhost/LalyPets/';
$sufijo = bin2hex(random_bytes(4));
$correo = "prueba.pagos.$sufijo@example.test";
$password = 'TemporalPago_2026!';
$ids = ['pagos' => [], 'historiales' => [], 'citas' => [], 'mascotas' => [], 'clientes' => [], 'usuarios' => []];

function verificarPago(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException($mensaje);
    }
    echo "OK - $mensaje\n";
}

function clienteHttpPago(): CurlHandle
{
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 15,
    ]);
    return $curl;
}

function solicitarPago(CurlHandle $curl, string $url, ?array $datos = null): array
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

function csrfPago(string $html): string
{
    if (!preg_match('/name=["\']csrf-token["\'] content=["\']([^"\']+)["\']/', $html, $resultado)
        && !preg_match('/name=["\']csrf_token["\'] value=["\']([^"\']+)["\']/', $html, $resultado)) {
        throw new RuntimeException('No se encontró el token CSRF.');
    }
    return html_entity_decode($resultado[1], ENT_QUOTES, 'UTF-8');
}

try {
    $anonimo = clienteHttpPago();
    verificarPago(solicitarPago($anonimo, $baseUrl . 'ajax/pagos.ajax.php', ['IDpago' => '1'])['status'] === 401,
        'El AJAX de Pagos exige una sesión');
    curl_close($anonimo);

    verificarPago(ModeloUsuarios::mdlCrearUsuario([
        'nombre' => 'Admin', 'apellido' => 'Temporal Pagos', 'telefono' => '70000401',
        'correo' => $correo, 'password' => password_hash($password, PASSWORD_DEFAULT),
        'turno' => 'Mañana', 'foto' => '', 'IDrol' => 1,
    ]) === 'ok', 'Se preparó el administrador temporal');
    $idUsuario = (int) ModeloUsuarios::mdlBuscarPorCorreo($correo)['IDusuario'];
    $ids['usuarios'][] = $idUsuario;

    verificarPago(ModeloClientes::mdlCrearCliente([
        'nombre' => 'Cliente', 'apellido' => "Pago $sufijo", 'telefono' => '70000402',
        'registro' => date('Y-m-d'),
    ]) === 'ok', 'Se preparó el propietario temporal');
    $stmt = Conexion::conectar()->prepare('SELECT IDcliente FROM clientes WHERE telefono_c = :telefono ORDER BY IDcliente DESC LIMIT 1');
    $stmt->execute([':telefono' => '70000402']);
    $idCliente = (int) $stmt->fetchColumn();
    $ids['clientes'][] = $idCliente;

    $idRaza = (int) Conexion::conectar()->query(
        "SELECT r.IDraza FROM raza r INNER JOIN especie e ON e.IDespecie = r.IDespecie
         WHERE e.nombre_Especie = 'Felino' ORDER BY r.IDraza LIMIT 1"
    )->fetchColumn();
    $nombreMascota = "MascotaPago$sufijo";
    verificarPago(ModeloMascotas::mdlCrearMascota([
        'nombre' => $nombreMascota, 'registro' => date('Y-m-d'), 'nacimiento' => '2024-01-01',
        'peso' => '5.00', 'sexo' => 'Hembra', 'descripcion' => '', 'color' => '',
        'IDcliente' => $idCliente, 'IDraza' => $idRaza,
    ]) === 'ok', 'Se preparó la mascota felina temporal');
    $stmt = Conexion::conectar()->prepare('SELECT IDmascota FROM mascotas WHERE nombrem = :nombre');
    $stmt->execute([':nombre' => $nombreMascota]);
    $idMascota = (int) $stmt->fetchColumn();
    $ids['mascotas'][] = $idMascota;

    $pdo = Conexion::conectar();
    $idVacunacion = (int) $pdo->query("SELECT IDservicio FROM servicios WHERE tipoServicio = 'Vacunación'")->fetchColumn();
    $idPeluqueria = (int) $pdo->query("SELECT IDservicio FROM servicios WHERE tipoServicio = 'Peluquería + baño'")->fetchColumn();
    $vacunas = $pdo->query("SELECT IDvacuna, preciovac FROM vacuna WHERE IDespecie = 2 AND estadoVac = 1 ORDER BY IDvacuna LIMIT 2")->fetchAll();
    verificarPago(count($vacunas) === 2 && $idVacunacion > 0 && $idPeluqueria > 0,
        'Existen dos vacunas felinas y los dos servicios');
    $totalVacunas = number_format((float) $vacunas[0]['preciovac'] + (float) $vacunas[1]['preciovac'], 2, '.', '');

    $stmtCita = $pdo->prepare(
        'INSERT INTO citas (motivoCita, estadoCita, fechaAtencion, IDusuario, IDmascota, IDservicio)
         VALUES (:motivo, :estado, :fecha, :usuario, :mascota, :servicio)'
    );
    $fechaCita = date('Y-m-d');
    $stmtCita->execute([':motivo' => 'Vacunación múltiple', ':estado' => 'Completada', ':fecha' => "$fechaCita 09:00:00",
        ':usuario' => $idUsuario, ':mascota' => $idMascota, ':servicio' => $idVacunacion]);
    $idCitaVacunacion = (int) $pdo->lastInsertId();
    $ids['citas'][] = $idCitaVacunacion;
    $stmtCita->execute([':motivo' => 'Peluquería completa', ':estado' => 'Completada', ':fecha' => "$fechaCita 10:00:00",
        ':usuario' => $idUsuario, ':mascota' => $idMascota, ':servicio' => $idPeluqueria]);
    $idCitaPeluqueria = (int) $pdo->lastInsertId();
    $ids['citas'][] = $idCitaPeluqueria;
    $stmtCita->execute([':motivo' => 'Cita aún pendiente', ':estado' => 'Pendiente', ':fecha' => date('Y-m-d', strtotime('+1 day')) . ' 09:00:00',
        ':usuario' => $idUsuario, ':mascota' => $idMascota, ':servicio' => $idPeluqueria]);
    $idCitaPendiente = (int) $pdo->lastInsertId();
    $ids['citas'][] = $idCitaPendiente;
    $stmtCita->execute([':motivo' => 'Vacunación sin aplicaciones', ':estado' => 'Completada', ':fecha' => "$fechaCita 11:00:00",
        ':usuario' => $idUsuario, ':mascota' => $idMascota, ':servicio' => $idVacunacion]);
    $idCitaSinVacunas = (int) $pdo->lastInsertId();
    $ids['citas'][] = $idCitaSinVacunas;

    $stmtHistorial = $pdo->prepare(
        'INSERT INTO historialvac (fechaAplicacion, fechaProximaDosis, observaciones, IDvacuna, IDcita, IDmascota)
         VALUES (:fecha, NULL, :observacion, :vacuna, :cita, :mascota)'
    );
    foreach ($vacunas as $vacuna) {
        $stmtHistorial->execute([':fecha' => $fechaCita, ':observacion' => 'Aplicación para cálculo',
            ':vacuna' => $vacuna['IDvacuna'], ':cita' => $idCitaVacunacion, ':mascota' => $idMascota]);
        $ids['historiales'][] = (int) $pdo->lastInsertId();
    }

    $navegador = clienteHttpPago();
    $inicio = solicitarPago($navegador, $baseUrl);
    verificarPago(solicitarPago($navegador, $baseUrl, [
        'csrf_token' => csrfPago($inicio['body']), 'imgEmail' => $correo, 'imgPassword' => $password,
    ])['status'] === 302, 'Se inició una sesión de prueba');

    $calendario = solicitarPago($navegador, $baseUrl . 'Calendar');
    preg_match('/<select[^>]+id="nuevoServicio"[^>]*>(.*?)<\/select>/s', $calendario['body'], $selectorServicio);
    verificarPago(isset($selectorServicio[1]) && !str_contains($selectorServicio[1], 'Bs'),
        'Citas no muestra precios al seleccionar servicio');
    verificarPago(str_contains($calendario['body'], 'btnRegistrarVacunacionCita')
        && str_contains($calendario['body'], 'btnRegistrarPagoCita'),
        'El detalle de Cita integra accesos a vacunación y pago');

    $pagina = solicitarPago($navegador, $baseUrl . 'Pagos');
    $token = csrfPago($pagina['body']);
    verificarPago($pagina['status'] === 200 && str_contains($pagina['body'], 'Historial de pagos'),
        'El módulo Pagos abre correctamente');
    verificarPago(!str_contains($pagina['body'], 'name="nuevoMontoPago"'),
        'El navegador no puede enviar un monto manual');

    $detalleVac = solicitarPago($navegador, $baseUrl . 'ajax/pagos.ajax.php', [
        'csrf_token' => $token, 'accion' => 'detalle_cita', 'IDcita' => (string) $idCitaVacunacion,
    ]);
    $jsonVac = json_decode($detalleVac['body'], true);
    verificarPago($detalleVac['status'] === 200 && count($jsonVac['vacunas']) === 2
        && $jsonVac['montoCalculado'] === $totalVacunas,
        'El cobro de vacunación suma todas las vacunas aplicadas');

    $detallePeluqueria = solicitarPago($navegador, $baseUrl . 'ajax/pagos.ajax.php', [
        'csrf_token' => $token, 'accion' => 'detalle_cita', 'IDcita' => (string) $idCitaPeluqueria,
    ]);
    $jsonPeluqueria = json_decode($detallePeluqueria['body'], true);
    verificarPago($jsonPeluqueria['montoCalculado'] === '60.00',
        'El cobro de Peluquería + baño usa el precio del servicio');
    $detalleSinVacunas = solicitarPago($navegador, $baseUrl . 'ajax/pagos.ajax.php', [
        'csrf_token' => $token, 'accion' => 'detalle_cita', 'IDcita' => (string) $idCitaSinVacunas,
    ]);
    verificarPago(json_decode($detalleSinVacunas['body'], true)['cobrable'] === false,
        'Una cita de vacunación sin aplicaciones todavía no es cobrable');

    $fechaPago = date('Y-m-d\TH:i');
    solicitarPago($navegador, $baseUrl . 'Pagos', [
        'csrf_token' => $token, 'nuevoMontoPago' => '0.01',
        'nuevoMetodoPago' => 'Efectivo', 'nuevaReferenciaPago' => '',
        'nuevaFechaPago' => $fechaPago, 'nuevaCitaPago' => (string) $idCitaVacunacion,
    ]);
    $stmt = $pdo->prepare('SELECT IDpago FROM pago WHERE IDcita = :cita');
    $stmt->execute([':cita' => $idCitaVacunacion]);
    $idPagoVac = (int) $stmt->fetchColumn();
    $ids['pagos'][] = $idPagoVac;
    $pagoVac = ModeloPagos::mdlMostrarPagos('IDpago', $idPagoVac);
    verificarPago($idPagoVac > 0 && $pagoVac['montopago'] === $totalVacunas,
        'El servidor ignora montos manipulados y guarda el total calculado');

    solicitarPago($navegador, $baseUrl . 'Pagos', [
        'csrf_token' => $token, 'nuevoMetodoPago' => 'Efectivo', 'nuevaReferenciaPago' => '',
        'nuevaFechaPago' => $fechaPago, 'nuevaCitaPago' => (string) $idCitaVacunacion,
    ]);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM pago WHERE IDcita = :cita');
    $stmt->execute([':cita' => $idCitaVacunacion]);
    verificarPago((int) $stmt->fetchColumn() === 1, 'Una cita solo puede tener un pago');

    solicitarPago($navegador, $baseUrl . 'Pagos', [
        'csrf_token' => $token, 'nuevoMetodoPago' => 'Efectivo', 'nuevaReferenciaPago' => '',
        'nuevaFechaPago' => $fechaPago, 'nuevaCitaPago' => (string) $idCitaPendiente,
    ]);
    $stmt->execute([':cita' => $idCitaPendiente]);
    verificarPago((int) $stmt->fetchColumn() === 0, 'Una cita pendiente no puede cobrarse');
    solicitarPago($navegador, $baseUrl . 'Pagos', [
        'csrf_token' => $token, 'nuevoMetodoPago' => 'Efectivo', 'nuevaReferenciaPago' => '',
        'nuevaFechaPago' => $fechaPago, 'nuevaCitaPago' => (string) $idCitaSinVacunas,
    ]);
    $stmt->execute([':cita' => $idCitaSinVacunas]);
    verificarPago((int) $stmt->fetchColumn() === 0,
        'El servidor bloquea el pago de vacunación hasta registrar vacunas');

    $ajax = solicitarPago($navegador, $baseUrl . 'ajax/pagos.ajax.php', [
        'csrf_token' => $token, 'accion' => 'pago', 'IDpago' => (string) $idPagoVac,
    ]);
    $jsonPago = json_decode($ajax['body'], true);
    verificarPago($ajax['status'] === 200 && $jsonPago['montopago'] === $totalVacunas,
        'El AJAX conserva el monto histórico al editar');

    solicitarPago($navegador, $baseUrl . 'Pagos', [
        'csrf_token' => $token, 'IDpago' => (string) $idPagoVac,
        'editarMontoPago' => '1.00', 'editarMetodoPago' => 'QR',
        'editarReferenciaPago' => "QR-$sufijo", 'editarFechaPago' => $fechaPago,
    ]);
    $pagoEditado = ModeloPagos::mdlMostrarPagos('IDpago', $idPagoVac);
    verificarPago($pagoEditado['montopago'] === $totalVacunas
        && $pagoEditado['metodopago'] === 'QR' && $pagoEditado['referenciapago'] === "QR-$sufijo",
        'Editar método y referencia no altera el total histórico');
    $respuestaHistorialPagado = ModeloHistorialVacunas::mdlEditarHistorial([
        'IDhistorialVac' => $ids['historiales'][0], 'fechaAplicacion' => $fechaCita,
        'fechaProximaDosis' => null, 'observaciones' => 'Intento posterior al pago',
        'IDvacuna' => (int) $vacunas[0]['IDvacuna'], 'IDcita' => $idCitaVacunacion,
        'IDmascota' => $idMascota,
    ]);
    verificarPago($respuestaHistorialPagado === 'cita_pagada',
        'Después del cobro se bloquean cambios en las vacunas aplicadas');

    solicitarPago($navegador, $baseUrl . 'Pagos', [
        'csrf_token' => $token, 'nuevoMetodoPago' => 'Efectivo', 'nuevaReferenciaPago' => '',
        'nuevaFechaPago' => $fechaPago, 'nuevaCitaPago' => (string) $idCitaPeluqueria,
    ]);
    $stmt->execute([':cita' => $idCitaPeluqueria]);
    $idPagoPeluqueria = (int) $pdo->query("SELECT IDpago FROM pago WHERE IDcita = $idCitaPeluqueria")->fetchColumn();
    $ids['pagos'][] = $idPagoPeluqueria;
    verificarPago(ModeloPagos::mdlMostrarPagos('IDpago', $idPagoPeluqueria)['montopago'] === '60.00',
        'El pago de peluquería guarda automáticamente Bs 60.00');
    curl_close($navegador);
} finally {
    foreach (['pagos' => 'pago:IDpago', 'historiales' => 'historialvac:IDhistorialVac',
        'citas' => 'citas:IDcita', 'mascotas' => 'mascotas:IDmascota',
        'clientes' => 'clientes:IDcliente', 'usuarios' => 'usuarios:IDusuario'] as $grupo => $destino) {
        if ($ids[$grupo] === []) {
            continue;
        }
        [$tabla, $columna] = explode(':', $destino);
        $marcadores = implode(',', array_fill(0, count($ids[$grupo]), '?'));
        Conexion::conectar()->prepare("DELETE FROM $tabla WHERE $columna IN ($marcadores)")->execute($ids[$grupo]);
    }
}

echo "PRUEBAS_PAGOS_COMPLETADAS\n";
