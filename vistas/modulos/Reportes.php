<?php
$reporte = ControladorReportes::ctrObtenerReporte($_GET['desde'] ?? null, $_GET['hasta'] ?? null);
?>
<style>
#impresionTablaReporte{display:none}
table.tabla-reporte{width:100%!important}
body.reporte-en-impresion #contenedorReportes{visibility:hidden}
body.reporte-en-impresion{background:#fff!important}
@media print{
  body.imprimiendo-tabla-reporte>div:not(#impresionTablaReporte),body.imprimiendo-tabla-reporte>script{display:none!important}
  body.imprimiendo-tabla-reporte>#impresionTablaReporte{display:block!important;width:100%;margin:0;padding:0;color:#222;background:#fff}
  #impresionTablaReporte .x_panel{margin:0;padding:0;border:0;box-shadow:none}
  #impresionTablaReporte .btnImprimirTablaReporte{display:none!important}
  #impresionTablaReporte table{width:100%!important}
}
</style>
<div class="right_col" id="contenedorReportes" role="main">
  <div class="page-title">
    <div class="title_left">
      <h3>Reportes</h3>
    </div>
  </div>
  <div class="clearfix"></div>
  <div class="x_panel filtros-reporte">
    <div class="x_content">
      <form method="get" action="Reportes" class="form-inline">
        <input type="hidden" name="ruta" value="Reportes">
        <div class="form-group">
          <label>Desde&nbsp;</label><input type="date" class="form-control" name="desde" value="<?php echo Seguridad::escapar($reporte['desde']); ?>" required>
        </div>
        <div class="form-group" style="margin-left:12px">
          <label>Hasta&nbsp;</label><input type="date" class="form-control" name="hasta" value="<?php echo Seguridad::escapar($reporte['hasta']); ?>" required>
        </div>
        <button class="btn btn-success" type="submit" style="margin-left:12px"><i class="fa fa-filter"></i> Aplicar</button>
      </form>
    </div>
  </div>
  <p><strong>Periodo:</strong> 
  <?php 
    echo date('d/m/Y', strtotime($reporte['desde'])); 
  ?> al 
  <?php 
    echo date('d/m/Y', strtotime($reporte['hasta'])); 
  ?></p>

  <div class="x_panel">
    <div class="x_title"><h2>Reporte de esquema de vacunación</h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <a href="SeguimientoVac" class="btn btn-primary"><i class="fa fa-list-alt"></i> Ver esquemas por mascota</a>
    </div>
  </div>

  <div class="x_panel">
    <div class="x_title">
      <h2>Reporte de citas</h2><div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="table-responsive">
        <table id="tablaReporteCitas" class="table table-striped table-bordered tabla-reporte">
          <thead>
            <tr>
              <th>Fecha y hora</th>
              <th>Cliente</th>
              <th>Mascota</th>
              <th>Servicios</th>
              <th>Veterinario</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reporte['detalleCitas'] as $f): ?>
            <tr>
              <td><?php echo date('d/m/Y H:i', strtotime($f['fechaAtencion'])); ?></td>
              <td><?php echo Seguridad::escapar($f['cliente']); ?></td>
              <td><?php echo Seguridad::escapar($f['mascota']); ?></td>
              <td><?php echo Seguridad::escapar($f['servicios']); ?></td>
              <td><?php echo Seguridad::escapar($f['responsable']); ?></td>
              <td><?php echo Seguridad::escapar($f['estadoCita']); ?></td>
            </tr><?php endforeach; ?>
            <?php if ($reporte['detalleCitas'] === []): ?>
            <tr>
              <td colspan="6" class="text-center">No existen citas en el periodo seleccionado.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="x_panel">
    <div class="x_title">
      <h2>Reporte de pagos de servicio</h2>
      <div class="clearfix">
      </div>
    </div>
    <div class="x_content">
      <div class="table-responsive">
        <table id="tablaReportePagos" class="table table-striped table-bordered tabla-reporte">
          <thead>
            <tr>
              <th>N.º</th>
              <th>Fecha y hora</th>
              <th>Cliente</th>
              <th>Servicios cobrados</th>
              <th>Método</th>
              <th>Recibido por</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reporte['pagos'] as $f): ?>
            <tr>
              <td><?php echo (int) $f['IDpago']; ?></td>
              <td><?php echo date('d/m/Y H:i', strtotime($f['fechapago'])); ?></td>
              <td><?php echo Seguridad::escapar($f['cliente']); ?></td>
              <td><?php echo Seguridad::escapar($f['conceptos']); ?></td>
              <td><?php echo Seguridad::escapar($f['metodopago']); ?></td>
              <td><?php echo Seguridad::escapar($f['recibidoPor']); ?></td>
              <td>Bs <?php echo number_format((float) $f['montopago'], 2, '.', ','); ?></td>
            </tr>
              <?php endforeach; ?>
              <?php if ($reporte['pagos'] === []): ?>
                <tr>
                  <td colspan="7" class="text-center">No existen pagos en el periodo seleccionado.</td>
                </tr>
                <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="x_panel">
    <div class="x_title">
      <h2>Reporte de clientes más frecuentes</h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="table-responsive">
        <table id="tablaReporteClientes" class="table table-striped table-bordered tabla-reporte">
          <thead>
            <tr>
              <th>Cliente</th>
              <th>Teléfono</th>
              <th>Mascotas</th>
              <th>Atenciones</th>
              <th>Última cita</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reporte['clientesFrecuentes'] as $f): ?>
            <tr>
              <td><?php echo Seguridad::escapar($f['cliente']); ?></td>
              <td><?php echo Seguridad::escapar($f['telefono']); ?></td>
              <td><?php echo (int) $f['cantidadMascotas']; ?></td>
              <td><?php echo (int) $f['cantidadCitas']; ?></td>
              <td><?php echo date('d/m/Y', strtotime($f['ultimaCita'])); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if ($reporte['clientesFrecuentes'] === []): ?>
            <tr>
              <td colspan="5" class="text-center">No existen clientes con atenciones en el periodo seleccionado.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="x_panel">
    <div class="x_title">
      <h2>Reporte de mascotas</h2>
      <div class="clearfix"></div>
    </div>
    <div class="x_content">
      <div class="table-responsive">
        <table id="tablaReporteMascotas" class="table table-striped table-bordered tabla-reporte">
          <thead>
            <tr>
              <th>Mascota</th>
              <th>Especie</th>
              <th>Raza</th>
              <th>Sexo</th>
              <th>Edad</th>
              <th>Propietario</th>
              <th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reporte['mascotas'] as $f): ?>
              <tr>
                <td><?php echo Seguridad::escapar($f['mascota']); ?></td>
                <td><?php echo Seguridad::escapar($f['especie']); ?></td>
                <td><?php echo Seguridad::escapar($f['raza']); ?></td>
                <td><?php echo Seguridad::escapar($f['sexo']); ?></td>
                <td><?php echo (int) $f['edad'] < 1 ? 'Menor de 1 año' : (int) $f['edad'] . ' años'; ?></td>
                <td><?php echo Seguridad::escapar($f['propietario']); ?></td>
                <td><?php echo (int) $f['habilitadoM'] === 1 ? 'Habilitada' : 'Inhabilitada'; ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if ($reporte['mascotas'] === []): ?>
              <tr>
                <td colspan="7" class="text-center">No existen mascotas registradas.</td>
              </tr>
              <?php endif; ?>   
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
