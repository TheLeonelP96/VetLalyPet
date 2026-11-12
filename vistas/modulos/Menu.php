<div class="col-md-3 left_col">
    <div class="left_col scroll-view">
    <div class="navbar nav_title" style="border: 0;">
        <a href="Perfil" class="site_title"><i class="fa fa-paw"></i> <span>LalyPet's!</span></a>
    </div>

    <div class="clearfix"></div>

    <!-- menu profile quick info -->
    <div class="profile clearfix">
        <div class="profile_pic">
            <?php
                if($_SESSION["foto"]!= "")
                {
                    echo '<img src="'.$_SESSION["foto"].' " class="img-circle profile_img" alt="">';
                }
                else
                {
                    echo'<img src="vistas/production/images/img.jpg" class="img-circle profile_img" alt="">';
                }  
            
            ?>
        
        </div>
        <div class="profile_info">
            <span>Bienvenido,</span>
            <h2>
                <?php 
                    if($_SESSION["nombre"] != "")
                    {
                    echo '<p class="text-xs text-secondary mb-0">'.$_SESSION["nombre"].' '.$_SESSION["apellido"].'</p>';
                    }
                    else
                    {
                        echo '<p class="text-xs text-secondary mb-0">Gordon Freeman</p>';
                    }
                
                ?>

            </h2>
        </div>
    </div>
    <!-- /menu profile quick info -->

    <br />

    <!-- sidebar menu -->
    <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
        <div class="menu_section">
        <h3>General</h3>
        <ul class="nav side-menu">
            <li><a><i class="fa fa-home"></i> Registros <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">

                <?php    

                    switch($_SESSION["IDrol"])
                    {
                    case 1:
                        echo '<li><a href="Usuarios">Usuarios</a></li>
                            <li><a href="Mascotas">Mascotas</a></li>
                            <li><a href="Clientes">Clientes</a></li>
                            
                            
                            ';
                    break;
                    case 2:
                        echo '<li><a href="Mascotas">Mascotas</a></li>
                            <li><a href="Clientes">Clientes</a></li>
                            
                            ';
                        break;
                    case 3:
                        echo '<li><a href="Mascotas">Mascotas</a></li>
                            <li><a href="Clientes">Clientes</a></li>
                           
                            ';
                        break;
                    }

                ?>
                
            </ul>
            </li>
            <li><a><i class="fa fa-calendar"></i>Citas <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                        
                <?php    

                    switch($_SESSION["IDrol"])
                    {
                    case 1:
                        echo '
                            
                            <li><a href="Calendar">Calendario</a></li>
                            
                            ';
                    break;
                    case 2:
                        echo '

                            <li><a href="Calendar">Calendario</a></li>

                            ';
                        break;
                    case 3:
                        echo '

                            <li><a href="Calendar">Calendario</a></li>

                            ';
                        break;
                    }

                ?>


            </ul>
            </li>
            <li><a><i class="fa fa-desktop"></i> Servicios <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="Vacunas">Vacunas</a></li>
                <li><a href="Servicios">Servicios</a></li>
            </ul>
            </li>
            <li><a><i class="fa fa-desktop"></i> Historial <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="HistorialVac">Historial de vacunas</a></li>
                <li><a href="SeguimientoVac">Seguimiento de vacunación</a></li>
            </ul>
            </li>
                <li><a href="Pagos"><i class="fa fa-money"></i> Pagos</a></li>
                <?php if ((int) ($_SESSION['IDrol'] ?? 0) === 1): ?>
                <li><a href="Reportes"><i class="fa fa-bar-chart"></i> Reportes</a></li>
                <?php endif; ?>
            <!--li><a><i class="fa fa-table"></i> Tables <span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="tables.html">Tables</a></li>
                <li><a href="tables_dynamic.html">Table Dynamic</a></li>
            </ul>
            </li>
            <li><a><i class="fa fa-bar-chart-o"></i> Data Presentation <span class="fa fa-cheFron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="chartjs.html">Chart JS</a></li>
                <li><a href="chartjs2.html">Chart JS2</a></li>
                <li><a href="morisjs.html">Moris JS</a></li>
                <li><a href="echarts.html">ECharts</a></li>
                <li><a href="other_charts.html">Other Charts</a></li>
            </ul>
            </li>
            <li><a><i class="fa fa-clone"></i>Layouts<span class="fa fa-chevron-down"></span></a>
            <ul class="nav child_menu">
                <li><a href="fixed_sidebar.html">Fixed Sidebar</a></li>
                <li><a href="fixed_footer.html">Fixed Footer</a></li>
            </ul>
            </li-->
        </ul>
        </div>
        <div class="menu_section">
            <!--h3>Live On</h3>
            <ul class="nav side-menu">
                <li><a><i class="fa fa-bug"></i> Additional Pages <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    <li><a href="e_commerce.html">E-commerce</a></li>
                    <li><a href="projects.html">Projects</a></li>
                    <li><a href="project_detail.html">Project Detail</a></li>
                    <li><a href="contacts.html">Contacts</a></li>
                    <li><a href="profile.html">Profile</a></li>
                </ul>
                </li>
                <li><a><i class="fa fa-windows"></i> Extras <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    <li><a href="page_403.html">403 Error</a></li>
                    <li><a href="page_404.html">404 Error</a></li>
                    <li><a href="page_500.html">500 Error</a></li>
                    <li><a href="plain_page.html">Plain Page</a></li>
                    <li><a href="login.html">Login Page</a></li>
                    <li><a href="pricing_tables.html">Pricing Tables</a></li>
                </ul>
                </li>
                <li><a><i class="fa fa-sitemap"></i> Multilevel Menu <span class="fa fa-chevron-down"></span></a>
                <ul class="nav child_menu">
                    <li><a href="#level1_1">Level One</a>
                    <li><a>Level One<span class="fa fa-chevron-down"></span></a>
                        <ul class="nav child_menu">
                        <li class="sub_menu"><a href="level2.html">Level Two</a>
                        </li>
                        <li><a href="#level2_1">Level Two</a>
                        </li>
                        <li><a href="#level2_2">Level Two</a>
                        </li>
                        </ul>
                    </li>
                    <li><a href="#level1_2">Level One</a>
                    </li>
                </ul>
                </li>                  
                <li><a href="javascript:void(0)"><i class="fa fa-laptop"></i> Landing Page <span class="label label-success pull-right">Coming Soon</span></a></li>
            </ul-->
        </div>

    </div>
    <!-- /sidebar menu -->

    <!-- /menu footer buttons -->
    <div class="sidebar-footer hidden-small">
        
        <a data-toggle="tooltip" data-placement="top" title="Cerrar sesión" href="salir" class="btnCerrarSesion">
        <span class="glyphicon glyphicon-off" aria-hidden="true"></span>
        </a>
    </div>
    <!-- /menu footer buttons -->
    </div>
</div>
