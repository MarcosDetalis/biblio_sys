<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="description" content="Vali is a responsive and free admin theme built with Bootstrap 4, SASS and PUG.js. It's fully customizable and modular.">
    <!-- Twitter meta-->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:site" content="@pratikborsadiya">
    <meta property="twitter:creator" content="@pratikborsadiya">
    <!-- Open Graph Meta-->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Vali Admin">
    <meta property="og:title" content="Vali - Free Bootstrap 4 admin theme">
    <meta property="og:url" content="http://pratikborsadiya.in/blog/vali-admin">
    <meta property="og:image" content="http://pratikborsadiya.in/blog/vali-admin/hero-social.png">
    <meta property="og:description" content="Vali is a responsive and free admin theme built with Bootstrap 4, SASS and PUG.js. It's fully customizable and modular.">
    <title>Panel Administrativo</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Main CSS-->
    <link href="<?php echo base_url; ?>Assets/css/main.css" rel="stylesheet" />
    <link href="<?php echo base_url; ?>Assets/css/datatables.min.css" rel="stylesheet" crossorigin="anonymous" />
    <link href="<?php echo base_url; ?>Assets/css/select2.min.css" rel="stylesheet" />
    <link href="<?php echo base_url; ?>Assets/css/estilos.css" rel="stylesheet" />
    <!-- Font-icon css-->
    <link rel="stylesheet" type="text/css" href="<?php echo base_url; ?>Assets/css/font-awesome.min.css">
</head>

<body class="app sidebar-mini">
    <!-- Navbar-->
    <header class="app-header"><a class="app-header__logo" href="<?php echo base_url; ?>Configuracion/admin">Biblioteca</a>
        <!-- Sidebar toggle button--><a class="app-sidebar__toggle" href="#" data-toggle="sidebar" aria-label="Hide Sidebar"></a>
        <!-- Navbar Right Menu-->
        <ul class="app-nav">
            <!--Notification Menu-->
            <li class="dropdown"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Show notifications"><i class="fa fa-bell-o fa-lg"><span class="badgenot bg-primar rounded-pill" id="not">0</span></i></a>
                <ul class="app-notification dropdown-menu dropdown-menu-right">
                    <li class="app-notification__title">Notificaciones</li>
                    <div class="app-notification__content" id="listaNotificaciones">
                        <li class="app-notification__item text-muted small px-3 py-2">Sin novedades.</li>
                    </div>
                    <li class="app-notification__footer" id="footerNotificaciones"></li>
                </ul>
            </li>
            <!-- User Menu-->
            <li class="dropdown"><a class="app-nav__item" href="#" data-toggle="dropdown" aria-label="Open Profile Menu"><i class="fa fa-user fa-lg"></i></a>
                <ul class="dropdown-menu settings-menu dropdown-menu-right">
                    <li><a class="dropdown-item" href="#" id="modalPass"><i class="fa fa-user fa-lg"></i> Perfil</a></li>
                    <li><a class="dropdown-item" href="<?php echo base_url; ?>Usuarios/salir"><i class="fa fa-sign-out fa-lg"></i> Salir</a></li>
                </ul>
            </li>
        </ul>
    </header>
    <!-- Sidebar menu-->
    <div class="app-sidebar__overlay" data-toggle="sidebar"></div>
    <aside class="app-sidebar">
        <div class="app-sidebar__user"><img class="app-sidebar__user-avatar" src="<?php echo base_url; ?>Assets/img/logo_uni.jpg" alt="User Image" width="50">
            <div>
                <p class="app-sidebar__user-name"><?php echo $_SESSION['nombre'] ?></p>
                <p class="app-sidebar__user-designation"><?php echo $_SESSION['usuario']; ?></p>
            </div>
        </div>
        <ul class="app-menu">
            <!-- "Solicitud" ocultada: era redundante con "Reservas" (mismo listado de
                 reservas) salvo por el botón "+" que crea un préstamo directo sin QR.
                 El controlador (Controllers/Prestamos.php) sigue intacto por si se
                 necesita reactivar; solo se quitó el acceso del menú. -->
            <li><a class="app-menu__item" href="<?php echo base_url; ?>Reservas"><i class="app-menu__icon fa fa-calendar"></i><span class="app-menu__label">Reservas</span></a></li>
            <li><a class="app-menu__item" href="<?php echo base_url; ?>Estudiantes"><i class="app-menu__icon fa fa-graduation-cap"></i><span class="app-menu__label">Estudiantes</span></a></li>
            <!-- "Materias" ocultada del menú: se usaba como clasificación de
                 libros en paralelo con "Categorías", sin conectar ninguna de
                 las dos con nada. El catálogo del alumno filtra por
                 categoría, no por materia, así que ahora "Categorías"
                 reemplaza a "Materias" en el formulario de libros. El
                 controlador (Controllers/Materia.php) sigue intacto por si
                 se necesita en el futuro; solo se quitó el acceso del menú. -->
            <li class="treeview"><a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-list"></i><span class="app-menu__label">Libros</span><i class="treeview-indicator fa fa-angle-right"></i></a>
                <ul class="treeview-menu">
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Autor"><i class="icon fa fa-address-book-o"></i> Autor</a></li>
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Editorial"><i class="icon fa fa-tags"></i> Editorial</a></li>
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Categoria"><i class="icon fa fa-list-alt"></i> Categorías</a></li>
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Libros"><i class="icon fa fa-book"></i> Libros</a></li>
                </ul>
            </li>
            <li class="treeview"><a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-wrench"></i><span class="app-menu__label">Administración</span><i class="treeview-indicator fa fa-angle-right"></i></a>
                <ul class="treeview-menu">
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Usuarios"><i class="icon fa fa-user-o"></i> Usuarios</a></li>
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Configuracion"><i class="icon fa fa-cogs"></i> Configuración</a></li>
                    <li><a class="treeview-item" href="<?php echo base_url; ?>Carrera"><i class="icon fa fa-cogs"></i> Carreras</a></li>


                 

                </ul>
            </li>
            <li class="treeview"><a class="app-menu__item" href="#" data-toggle="treeview"><i class="app-menu__icon fa fa-file-text"></i><span class="app-menu__label">Reportes</span><i class="treeview-indicator fa fa-angle-right"></i></a>
                <ul class="treeview-menu">
                    <li><a class="treeview-item" href="<?php echo base_url; ?>EscanerQr/reportes"><i class="icon fa fa-bar-chart"></i> Reservas (pendientes/retiradas/devueltas)</a></li>
                    <li><a class="treeview-item" target="_blank" href="<?php echo base_url; ?>Prestamos/pdf"><i class="icon fa fa-file-pdf-o"></i> Libros Prestados</a></li>
                </ul>
            </li>

         

            <li><a class="app-menu__item" href="<?php echo base_url; ?>EscanerQr">
            <i class="app-menu__icon fa fa-file-text"></i>
            
            <span class="app-menu__label">Escaner Qr</span></a></li>

          


           

        </ul>
    </aside>
    <main class="app-content">

