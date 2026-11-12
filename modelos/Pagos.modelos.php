<?php
// Ejecuta las consultas de cobros y calcula los detalles que deben pagarse.

require_once __DIR__ . '/conexion.php';

final class ModeloPagos
{
    private const FILTROS = ['IDpago'=>'p.IDpago', 'IDcita'=>'cs.IDcita', 'metodopago'=>'p.metodopago'];

    // Construye la consulta base con las relaciones necesarias para mostrar pagos.
    private static function seleccionBase(): string
    {
        return "SELECT p.*, MIN(c.IDcita) AS IDcita, MIN(c.fechaAtencion) AS fechaAtencion,
                       GROUP_CONCAT(DISTINCT c.IDcita ORDER BY c.IDcita) AS IDcitas,
                       GROUP_CONCAT(DISTINCT s.tipoServicio ORDER BY s.tipoServicio SEPARATOR ', ') AS tipoServicio,
                       GROUP_CONCAT(DISTINCT m.nombrem ORDER BY m.nombrem SEPARATOR ', ') AS nombrem,
                       CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                       GROUP_CONCAT(DISTINCT CONCAT_WS(' ', vet.nombre_U, vet.apellido_U)
                                    ORDER BY vet.nombre_U SEPARATOR ', ') AS veterinario,
                       CONCAT_WS(' ', cobrador.nombre_U, cobrador.apellido_U) AS recibidoPor
                FROM pago p
                INNER JOIN clientes cli ON cli.IDcliente = p.IDcliente
                INNER JOIN usuarios cobrador ON cobrador.IDusuario = p.IDusuario
                INNER JOIN pagoservicio dp ON dp.IDpago = p.IDpago
                INNER JOIN citaServicio cs ON cs.IDcitaServicio = dp.IDcitaServicio
                INNER JOIN tiposervicios s ON s.IDtiposervicio = cs.IDservicio
                INNER JOIN citas c ON c.IDcita = cs.IDcita
                INNER JOIN mascotas m ON m.IDmascota = c.IDmascota
                INNER JOIN usuarios vet ON vet.IDusuario = c.IDusuario";
    }

    // Busca todos los pagos o uno específico según el filtro recibido.
    public static function mdlMostrarPagos($item = null, $valor = null): mixed
    {
        $sql = self::seleccionBase(); $parametros=[];
        if ($item !== null) {
            if (!isset(self::FILTROS[$item])) throw new InvalidArgumentException('Filtro de pago no permitido.');
            $sql .= ' WHERE ' . self::FILTROS[$item] . ' = :valor';
            $parametros[':valor']=$valor;
        }
        $sql .= ' GROUP BY p.IDpago ORDER BY p.fechapago DESC, p.IDpago DESC';
        $stmt=Conexion::conectar()->prepare($sql); $stmt->execute($parametros);
        return $item === null ? $stmt->fetchAll() : ($stmt->fetch() ?: null);
    }

    // Devuelve la información resumida que se imprime en el comprobante.
    public static function mdlObtenerComprobante(int $idPago): ?array
    {
        $pago=self::mdlMostrarPagos('IDpago',$idPago);
        if (!is_array($pago)) return null;
        $stmt=Conexion::conectar()->prepare(
            'SELECT concepto, subtotal AS importe FROM pagoservicio WHERE IDpago=:id ORDER BY IDpagoServicio'
        );
        $stmt->execute([':id'=>$idPago]);
        $pago['conceptos']=$stmt->fetchAll();
        return $pago;
    }

    // Obtiene citas completadas que aún tienen servicios sin cobrar.
    public static function mdlListarCitasPendientesPago(): array
    {
        return Conexion::conectar()->query(
            "SELECT c.IDcita, c.fechaAtencion, m.nombrem,
                    CONCAT_WS(' ', cli.nombrec, cli.apellidoc) AS propietario,
                    GROUP_CONCAT(s.tipoServicio ORDER BY cs.IDcitaServicio SEPARATOR ', ') AS tipoServicio,
                    CONCAT_WS(' ', u.nombre_U, u.apellido_U) AS veterinario,
                    (SELECT COUNT(*) FROM historialvac h WHERE h.IDcita=c.IDcita) AS vacunasAplicadas,
                    SUM(CASE WHEN s.tipoServicio='Vacunación' THEN
                        COALESCE((SELECT SUM(v.preciovac) FROM historialvac hv
                            INNER JOIN esquemaVac ev ON ev.IDesquemaVac=hv.ID_esquemaVac
                            INNER JOIN vacuna v ON v.IDvacuna=ev.IDvacuna
                            WHERE hv.IDcita=c.IDcita),0)
                        ELSE cs.precioAplicado END) AS montoCalculado,
                    MAX(s.tipoServicio='Vacunación') AS incluyeVacunacion
             FROM citas c
             INNER JOIN mascotas m ON m.IDmascota=c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente=m.IDcliente
             INNER JOIN usuarios u ON u.IDusuario=c.IDusuario
             INNER JOIN citaServicio cs ON cs.IDcita=c.IDcita
             INNER JOIN tiposervicios s ON s.IDtiposervicio=cs.IDservicio
             LEFT JOIN pagoservicio dp ON dp.IDcitaServicio=cs.IDcitaServicio
             WHERE c.estadoCita='Completada' AND dp.IDpagoServicio IS NULL
             GROUP BY c.IDcita, c.fechaAtencion, m.nombrem, cli.nombrec, cli.apellidoc,
                      u.nombre_U, u.apellido_U
             ORDER BY c.fechaAtencion DESC"
        )->fetchAll();
    }

    // Calcula conceptos y total de una cita sin modificar sus datos.
    public static function mdlDetalleCobroCita(int $idCita): ?array
    {
        $conexion=Conexion::conectar();
        $stmt=$conexion->prepare(
            "SELECT c.IDcita,c.estadoCita,c.fechaAtencion,m.nombrem,m.IDcliente,
                    CONCAT_WS(' ',cli.nombrec,cli.apellidoc) AS propietario
             FROM citas c INNER JOIN mascotas m ON m.IDmascota=c.IDmascota
             INNER JOIN clientes cli ON cli.IDcliente=m.IDcliente WHERE c.IDcita=:id"
        );
        $stmt->execute([':id'=>$idCita]); $cita=$stmt->fetch();
        if (!$cita) return null;
        $detalles=self::detallesCobrables($conexion,$idCita,false);
        $stmtVac=$conexion->prepare(
            'SELECT v.nombreVac,v.preciovac FROM historialvac h
             INNER JOIN esquemaVac ev ON ev.IDesquemaVac=h.ID_esquemaVac
             INNER JOIN vacuna v ON v.IDvacuna=ev.IDvacuna WHERE h.IDcita=:id ORDER BY v.nombreVac'
        );
        $stmtVac->execute([':id'=>$idCita]); $vacunas=$stmtVac->fetchAll();
        $conceptos=[]; $monto=0.0; $incluyeVac=false;
        foreach ($detalles as $detalle) {
            if ($detalle['tipoServicio']==='Vacunación') {
                $incluyeVac=true;
                $importe=array_sum(array_map(static fn($v)=>(float)$v['preciovac'],$vacunas));
                $nombre=$vacunas ? 'Vacunación: '.implode(', ',array_column($vacunas,'nombreVac')) : 'Vacunación';
            } else { $importe=(float)$detalle['precioAplicado']; $nombre=$detalle['tipoServicio']; }
            $monto += $importe;
            $conceptos[]=['IDcitaServicio'=>(int)$detalle['IDcitaServicio'],'concepto'=>$nombre,
                'importe'=>number_format($importe,2,'.','')];
        }
        $cita['vacunas']=$vacunas; $cita['conceptos']=$conceptos;
        $cita['tipoServicio']=implode(', ',array_column($detalles,'tipoServicio'));
        $cita['montoCalculado']=number_format($monto,2,'.','');
        $cita['cobrable']=$cita['estadoCita']==='Completada' && $detalles!==[] && $monto>0
            && (!$incluyeVac || $vacunas!==[]);
        return $cita;
    }

    // Registra el pago y sus detalles dentro de una transacción.
    public static function mdlCrearPago(array $datos): string
    {
        $conexion=Conexion::conectar();
        try {
            $conexion->beginTransaction();
            $stmt=$conexion->prepare(
                'SELECT c.estadoCita,m.IDcliente FROM citas c INNER JOIN mascotas m ON m.IDmascota=c.IDmascota
                 WHERE c.IDcita=:id FOR UPDATE'
            );
            $stmt->execute([':id'=>$datos['IDcita']]); $cita=$stmt->fetch();
            if (!$cita || $cita['estadoCita']!=='Completada') { $conexion->rollBack(); return 'cita_invalida'; }
            $detalles=self::detallesCobrables($conexion,$datos['IDcita'],true);
            if ($detalles===[]) { $conexion->rollBack(); return 'duplicado'; }
            $stmtVac=$conexion->prepare(
                'SELECT v.nombreVac,v.preciovac FROM historialvac h
                 INNER JOIN esquemaVac ev ON ev.IDesquemaVac=h.ID_esquemaVac
                 INNER JOIN vacuna v ON v.IDvacuna=ev.IDvacuna WHERE h.IDcita=:id FOR UPDATE'
            );
            $stmtVac->execute([':id'=>$datos['IDcita']]); $vacunas=$stmtVac->fetchAll();
            $lineas=[]; $total=0.0;
            foreach ($detalles as $detalle) {
                if ($detalle['tipoServicio']==='Vacunación') {
                    if ($vacunas===[]) { $conexion->rollBack(); return 'sin_vacunas'; }
                    $importe=array_sum(array_map(static fn($v)=>(float)$v['preciovac'],$vacunas));
                    $concepto='Vacunación: '.implode(', ',array_column($vacunas,'nombreVac'));
                } else { $importe=(float)$detalle['precioAplicado']; $concepto=$detalle['tipoServicio']; }
                if ($importe<=0) { $conexion->rollBack(); return 'monto_invalido'; }
                $total+=$importe;
                $lineas[]=['id'=>$detalle['IDcitaServicio'],'concepto'=>$concepto,'importe'=>$importe];
            }
            if ($total>99999999.99) { $conexion->rollBack(); return 'monto_invalido'; }
            $stmtPago=$conexion->prepare(
                'INSERT INTO pago (montopago,metodopago,fechapago,IDusuario,IDcliente)
                 VALUES (:monto,:metodo,:fecha,:usuario,:cliente)'
            );
            $stmtPago->execute([':monto'=>number_format($total,2,'.',''),':metodo'=>$datos['metodo'],
                ':fecha'=>$datos['fecha'],
                ':usuario'=>$datos['IDusuario'],':cliente'=>$cita['IDcliente']]);
            $idPago=(int)$conexion->lastInsertId();
            $stmtDetalle=$conexion->prepare(
                'INSERT INTO pagoservicio (IDpago,IDcitaServicio,concepto,precioCobrado,subtotal)
                 VALUES (:pago,:cs,:concepto,:precio,:subtotal)'
            );
            foreach ($lineas as $linea) $stmtDetalle->execute([':pago'=>$idPago,':cs'=>$linea['id'],
                ':concepto'=>$linea['concepto'],':precio'=>$linea['importe'],':subtotal'=>$linea['importe']]);
            $conexion->commit(); return 'ok';
        } catch (PDOException $e) {
            if ($conexion->inTransaction()) $conexion->rollBack();
            return $e->getCode()==='23000'?'duplicado':'error';
        } catch (Throwable $e) { if ($conexion->inTransaction()) $conexion->rollBack(); return 'error'; }
    }

    // Actualiza método, fecha y usuario receptor sin alterar el total histórico.
    public static function mdlEditarPago(array $datos): string
    {
        try {
            $stmt=Conexion::conectar()->prepare(
                'UPDATE pago SET metodopago=:metodo,
                    fechapago=:fecha,IDusuario=:usuario WHERE IDpago=:id'
            );
            $stmt->execute([':metodo'=>$datos['metodo'],':fecha'=>$datos['fecha'],
                ':usuario'=>$datos['IDusuario'],':id'=>$datos['IDpago']]);
            return $stmt->rowCount()<=1?'ok':'error';
        } catch (Throwable $e) { return 'error'; }
    }

    // Obtiene los servicios pendientes de cobro y opcionalmente bloquea sus filas.
    private static function detallesCobrables(PDO $conexion,int $idCita,bool $bloquear): array
    {
        $sql="SELECT cs.IDcitaServicio,cs.precioAplicado,s.tipoServicio FROM citaServicio cs
              INNER JOIN tiposervicios s ON s.IDtiposervicio=cs.IDservicio
              LEFT JOIN pagoservicio dp ON dp.IDcitaServicio=cs.IDcitaServicio
              WHERE cs.IDcita=:id AND dp.IDpagoServicio IS NULL ORDER BY cs.IDcitaServicio";
        if ($bloquear) $sql.=' FOR UPDATE';
        $stmt=$conexion->prepare($sql); $stmt->execute([':id'=>$idCita]); return $stmt->fetchAll();
    }
}
