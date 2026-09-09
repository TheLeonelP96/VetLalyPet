<?php Seguridad::iniciarSesion(); ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="csrf-token" content="<?php echo Seguridad::escapar(Seguridad::tokenCsrf()); ?>">
	<link rel="icon" href="images/favicon.ico" type="image/ico" />

    <title>LalyPets | Gestión veterinaria</title>

    <!-- Bootstrap -->
    <link href="vistas/vendors/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">.
    <!-- Font Awesome -->
    <link href="vistas/vendors/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <!-- NProgress -->
    <link href="vistas/vendors/nprogress/nprogress.css" rel="stylesheet">
    <!-- iCheck -->
    <link href="vistas/vendors/iCheck/skins/flat/green.css" rel="stylesheet">

    <link href="vistas/vendors/animate.css/animate.min.css" rel="stylesheet">
    <link href="
    https://cdn.jsdelivr.net/npm/sweetalert2@11.26.24/dist/sweetalert2.min.css
    " rel="stylesheet">
	
    <!-- bootstrap-progressbar -->
    <link href="vistas/vendors/bootstrap-progressbar/css/bootstrap-progressbar-3.3.4.min.css" rel="stylesheet">
    <!-- JQVMap -->
    <link href="vistas/vendors/jqvmap/dist/jqvmap.min.css" rel="stylesheet"/>
    <!-- bootstrap-daterangepicker -->
    <link href="vistas/vendors/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

    <!-- Custom Theme Style -->
    <link href="vistas/build/css/custom.min.css" rel="stylesheet">

    <link href="vistas/vendors/datatables.net-bs/css/dataTables.bootstrap.min.css" rel="stylesheet">
    <link href="vistas/vendors/datatables.net-buttons-bs/css/buttons.bootstrap.min.css" rel="stylesheet">
    <link href="vistas/vendors/datatables.net-fixedheader-bs/css/fixedHeader.bootstrap.min.css" rel="stylesheet">
    <link href="vistas/vendors/datatables.net-responsive-bs/css/responsive.bootstrap.min.css" rel="stylesheet">
    <link href="vistas/vendors/datatables.net-scroller-bs/css/scroller.bootstrap.min.css" rel="stylesheet">
  </head>

  <body class="nav-md">
    <?php
        if(isset($_SESSION['IniciarSesion'])&& $_SESSION['IniciarSesion']=='ok')
        {
             echo' <div class="container body">';
                echo'<div class="main_container">';

                    include "modulos/Menu.php";
                    include "modulos/Header.php";
                    if(isset($_GET["ruta"]))
                    {
                    $ruta = $_GET["ruta"];
                    $rutasPermitidas = ["Inicio", "Mascotas", "Clientes", "Calendar", "salir", "Vacunas", "HistorialVac", "Pagos"];
                    if ((int) ($_SESSION["IDrol"] ?? 0) === 1) {
                        $rutasPermitidas[] = "Usuarios";
                    }
                    if(in_array($ruta, $rutasPermitidas, true))
                    {
                        include "modulos/".$ruta.".php";
                    }
                    else
                    {
                        include "modulos/404.php";
                    }

                    }
                    else
                    {
                    include "modulos/Inicio.php";
                    }
                    include "modulos/Footer.php";
                echo'</div>';
            echo'</div>';

        }
         else
        {
            include "modulos/login.php";
        }
           
    
    ?>
   
      
        
        <!-- /footer content -->
      
  

    <!-- jQuery -->
    <script src="vistas/vendors/jquery/dist/jquery.min.js"></script>
    <!-- Bootstrap -->
    <script src="vistas/vendors/bootstrap/dist/js/bootstrap.min.js"></script>
    <!-- FastClick -->
    <script src="vistas/vendors/fastclick/lib/fastclick.js"></script>
    <!-- NProgress -->
    <script src="vistas/vendors/nprogress/nprogress.js"></script>
    <!-- Chart.js -->
    <script src="vistas/vendors/Chart.js/dist/Chart.min.js"></script>
    <!-- gauge.js -->
    <script src="vistas/vendors/gauge.js/dist/gauge.min.js"></script>
    <!-- bootstrap-progressbar -->
    <script src="vistas/vendors/bootstrap-progressbar/bootstrap-progressbar.min.js"></script>
    <!-- iCheck -->
    <script src="vistas/vendors/iCheck/icheck.min.js"></script>
    <!-- Skycons -->
    <script src="vistas/vendors/skycons/skycons.js"></script>
    <!-- Flot -->
    <script src="vistas/vendors/Flot/jquery.flot.js"></script>
    <script src="vistas/vendors/Flot/jquery.flot.pie.js"></script>
    <script src="vistas/vendors/Flot/jquery.flot.time.js"></script>
    <script src="vistas/vendors/Flot/jquery.flot.stack.js"></script>
    <script src="vistas/vendors/Flot/jquery.flot.resize.js"></script>
    <!-- Flot plugins -->
    <script src="vistas/vendors/flot.orderbars/js/jquery.flot.orderBars.js"></script>
    <script src="vistas/vendors/flot-spline/js/jquery.flot.spline.min.js"></script>
    <script src="vistas/vendors/flot.curvedlines/curvedLines.js"></script>
    <!-- DateJS -->
    <script src="vistas/vendors/DateJS/build/date.js"></script>
    <!-- JQVMap -->
    <script src="vistas/vendors/jqvmap/dist/jquery.vmap.js"></script>
    <script src="vistas/vendors/jqvmap/dist/maps/jquery.vmap.world.js"></script>
    <script src="vistas/vendors/jqvmap/examples/js/jquery.vmap.sampledata.js"></script>
    <!-- bootstrap-daterangepicker -->
    <script src="vistas/vendors/moment/min/moment.min.js"></script>
    <script src="vistas/vendors/bootstrap-daterangepicker/daterangepicker.js"></script>

    <script src="vistas/vendors/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="vistas/vendors/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
    <script src="vistas/vendors/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="vistas/vendors/datatables.net-buttons-bs/js/buttons.bootstrap.min.js"></script>
    <script src="vistas/vendors/datatables.net-buttons/js/buttons.flash.min.js"></script>
    <script src="vistas/vendors/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="vistas/vendors/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="vistas/vendors/datatables.net-fixedheader/js/dataTables.fixedHeader.min.js"></script>
    <script src="vistas/vendors/datatables.net-keytable/js/dataTables.keyTable.min.js"></script>
    <script src="vistas/vendors/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="vistas/vendors/datatables.net-responsive-bs/js/responsive.bootstrap.js"></script>
    <script src="vistas/vendors/datatables.net-scroller/js/dataTables.scroller.min.js"></script>
    <script src="vistas/vendors/jszip/dist/jszip.min.js"></script>
    <script src="vistas/vendors/pdfmake/build/pdfmake.min.js"></script>
    <script src="vistas/vendors/pdfmake/build/vfs_fonts.js"></script>
    <script src="vistas/js/usuarios.js?v=<?php echo (int) filemtime(__DIR__ . '/js/usuarios.js'); ?>"></script>
    <script src="vistas/js/mascotas.js?v=<?php echo (int) filemtime(__DIR__ . '/js/mascotas.js'); ?>"></script>
    <script src="vistas/js/clientes.js?v=<?php echo (int) filemtime(__DIR__ . '/js/clientes.js'); ?>"></script>
    <script src="vistas/js/citas.js?v=<?php echo (int) filemtime(__DIR__ . '/js/citas.js'); ?>"></script>
    <script src="vistas/js/vacunas.js?v=<?php echo (int) filemtime(__DIR__ . '/js/vacunas.js'); ?>"></script>
    <script src="vistas/js/historialVacunas.js?v=<?php echo (int) filemtime(__DIR__ . '/js/historialVacunas.js'); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="vistas/js/pagos.js?v=<?php echo (int) filemtime(__DIR__ . '/js/pagos.js'); ?>"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.20/index.global.min.js"></script>
    <!-- Custom Theme Scripts -->
    <script src="vistas/build/js/custom.min.js"></script>
    <script src="
    https://cdn.jsdelivr.net/npm/sweetalert2@11.26.24/dist/sweetalert2.all.min.js
    "></script>
    
  </body>
</html>
