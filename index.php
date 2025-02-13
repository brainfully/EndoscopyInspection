<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Comprobar conexión a la base de datos
$database = new Database();
$db = $database->getConnection();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Inspección Endoscópica</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar principal -->
    <?php include 'includes/header.php'; ?>

    <!-- Contenedor principal -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <?php include 'includes/sidebar.php'; ?>
            </div>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div id="notifications" class="position-fixed top-0 end-0 p-3" style="z-index: 1050"></div>
                
                <!-- Breadcrumb -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" id="breadcrumb">
                            <li class="breadcrumb-item active">Dashboard</li>
                        </ol>
                    </nav>
                </div>

                <!-- Contenido dinámico -->
                <div id="main-content">
                    <?php
                    $section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
                    $file = "layouts/{$section}.php";
                    
                    if (file_exists($file)) {
                        include $file;
                    } else {
                        include 'layouts/dashboard.php';
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/layout-editor.js"></script>
    <script src="assets/js/inspection-manager.js"></script>
</body>
</html>