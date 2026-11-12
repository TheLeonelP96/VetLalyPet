<?php
$comprobante = ControladorPagos::ctrObtenerComprobante($_GET['id'] ?? null);
?>
<style>
.comprobante-pago{max-width:760px;margin:20px auto;background:#fff;padding:35px;border:1px solid #ddd}
.comprobante-encabezado{border-bottom:2px solid #26b99a;margin-bottom:22px;padding-bottom:15px}
@media print{
  .left_col,.top_nav,footer,.btnImprimirComprobante,.btnVolverComprobante{display:none!important}
  .right_col{margin:0!important;padding:0!important}.comprobante-pago{border:0;margin:0 auto;box-shadow:none}
}
</style>
<div class="right_col" role="main">
<?php if (!is_array($comprobante)): ?>
    <div class="alert alert-danger">El comprobante solicitado no existe.</div>
    <a href="Pagos" class="btn btn-default">Volver a pagos</a>
<?php else: ?>
    <div class="comprobante-pago">
        <div class="comprobante-encabezado row">
            <div class="col-xs-7"><h2 style="margin-top:0">Veterinaria LalyPet's</h2><p>Comprobante de pago</p></div>
            <div class="col-xs-5 text-right"><h3>N.º <?php echo str_pad((string) (int) $comprobante['IDpago'], 6, '0', STR_PAD_LEFT); ?></h3><p><?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($comprobante['fechapago']))); ?></p></div>
        </div>
        <div class="row">
            <div class="col-xs-6"><p><strong>Cliente:</strong> <?php echo Seguridad::escapar($comprobante['propietario']); ?></p><p><strong>Mascota:</strong> <?php echo Seguridad::escapar($comprobante['nombrem']); ?></p></div>
            <div class="col-xs-6"><p><strong>Veterinario:</strong> <?php echo Seguridad::escapar($comprobante['veterinario']); ?></p><p><strong>Fecha de atención:</strong> <?php echo Seguridad::escapar(date('d/m/Y H:i', strtotime($comprobante['fechaAtencion']))); ?></p></div>
        </div>
        <table class="table table-bordered" style="margin-top:20px">
            <thead><tr><th>Concepto</th><th class="text-right">Importe</th></tr></thead>
            <tbody><?php foreach ($comprobante['conceptos'] as $concepto): ?><tr>
                <td><?php echo Seguridad::escapar($concepto['concepto']); ?></td>
                <td class="text-right">Bs <?php echo number_format((float) $concepto['importe'], 2, '.', ','); ?></td>
            </tr><?php endforeach; ?></tbody>
            <tfoot><tr><th class="text-right">TOTAL</th><th class="text-right">Bs <?php echo number_format((float) $comprobante['montopago'], 2, '.', ','); ?></th></tr></tfoot>
        </table>
        <div class="row">
            <div class="col-xs-6"><p><strong>Método:</strong> <?php echo Seguridad::escapar($comprobante['metodopago']); ?></p></div>
            <div class="col-xs-6"><p><strong>Pago recibido por:</strong> <?php echo Seguridad::escapar($comprobante['recibidoPor']); ?></p></div>
        </div>
        <p class="text-center text-muted" style="margin-top:35px">Gracias por confiar el cuidado de su mascota a LalyPet's.</p>
        <div class="text-center" style="margin-top:20px">
            <a href="Pagos" class="btn btn-default btnVolverComprobante">Volver</a>
            <button type="button" class="btn btn-primary btnImprimirComprobante"><i class="fa fa-print"></i> Imprimir o guardar en PDF</button>
        </div>
    </div>
<?php endif; ?>
</div>
