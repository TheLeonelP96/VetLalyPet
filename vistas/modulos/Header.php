<?php $alertasEncabezado = ControladorAlertas::ctrAlertasActuales(); ?>
<!-- top navigation -->
<div class="top_nav">
    <div class="nav_menu">
    <nav>
        <div class="nav toggle">
        <a id="menu_toggle"><i class="fa fa-bars"></i></a>
        </div>

        <ul class="nav navbar-nav navbar-right">
            <li class="">
                <a href="javascript:;" class="user-profile dropdown-toggle" data-toggle="dropdown" aria-expanded="false">

                <?php
                    if($_SESSION["foto"]!= "")
                    {
                        echo '<img src="'.$_SESSION["foto"].' " class="user-image" alt="">'.$_SESSION["nombre"].'';
                    }
                    else
                    {
                        echo'<img src="vistas/production/images/img.jpg" alt="">'.$_SESSION["nombre"].'';
                    }  
                
                ?>
                    <span class=" fa fa-angle-down"></span>
                </a>
                <ul class="dropdown-menu dropdown-usermenu pull-right">
                <li><a href="Perfil"><i class="fa fa-user pull-right"></i>Perfil</a></li>
                <li><a href="salir" class="btnCerrarSesion"><i class="fa fa-sign-out pull-right"></i>Cerrar sesión</a></li>
                </ul>
            </li>

            <li role="presentation" class="dropdown">
                <a href="javascript:;" class="dropdown-toggle info-number" data-toggle="dropdown" aria-expanded="false">
                <i class="fa fa-bell-o"></i>
                <?php if ($alertasEncabezado !== []): ?>
                    <span class="badge bg-green"><?php echo count($alertasEncabezado); ?></span>
                <?php endif; ?>
                </a>
                <ul id="menu1" class="dropdown-menu list-unstyled msg_list" role="menu">
                <?php if ($alertasEncabezado === []): ?>
                    <li><div class="text-center" style="padding:12px">No hay alertas pendientes.</div></li>
                <?php else: ?>
                    <?php foreach ($alertasEncabezado as $alertaEncabezado): ?>
                        <li><a href="<?php echo Seguridad::escapar($alertaEncabezado['url']); ?>">
                            <span class="image"><i class="fa <?php echo Seguridad::escapar($alertaEncabezado['icono'] . ' ' . $alertaEncabezado['clase']); ?>" style="font-size:22px"></i></span>
                            <span><strong><?php echo Seguridad::escapar($alertaEncabezado['titulo']); ?></strong></span>
                            <span class="message"><?php echo Seguridad::escapar($alertaEncabezado['detalle']); ?></span>
                        </a></li>
                    <?php endforeach; ?>
                <?php endif; ?>
                </ul>
            </li>
        </ul>
    </nav>
    </div>
</div>
<!-- /top navigation -->
