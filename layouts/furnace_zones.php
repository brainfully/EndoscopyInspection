<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$furnace_id = isset($_GET['furnace_id']) ? intval($_GET['furnace_id']) : 0;

// Obtener información del horno
$query = "SELECT f.*, c.company 
          FROM furnaces f 
          LEFT JOIN clients c ON f.client_id = c.id 
          WHERE f.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$furnace_id]);
$furnace = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#" data-section="furnaces">Hornos</a></li>
                    <li class="breadcrumb-item active"><?php echo $furnace['name']; ?> - Zonas</li>
                </ol>
            </nav>
            <h2>Configuración de Zonas - <?php echo $furnace['name']; ?></h2>
            <p class="text-muted">Cliente: <?php echo $furnace['company']; ?></p>
        </div>
    </div>

    <div class="row">
        <!-- Layout del Horno -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Layout del Horno</h5>
                    <div class="layout-container">
                        <canvas id="layoutCanvas" class="w-100"></canvas>
                        <div id="zoneMarkers"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Zonas -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Zonas Configuradas</h5>
                    <button class="btn btn-primary mb-3" onclick="addZone()">
                        <i class="fas fa-plus"></i> Nueva Zona
                    </button>
                    <div id="zonesList">
                        <?php
                        $query = "SELECT * FROM zones WHERE furnace_id = ? ORDER BY name";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$furnace_id]);
                        while ($zone = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<div class='zone-item mb-2' data-zone-id='{$zone['id']}'>";
                            echo "<div class='d-flex justify-content-between align-items-center'>";
                            echo "<span>{$zone['name']}</span>";
                            echo "<div class='btn-group'>";
                            echo "<button class='btn btn-sm btn-outline-primary' onclick='editZone({$zone['id']})'><i class='fas fa-edit'></i></button>";
                            echo "<button class='btn btn-sm btn-outline-danger' onclick='deleteZone({$zone['id']})'><i class='fas fa-trash'></i></button>";
                            echo "</div>";
                            echo "</div>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Zonas -->
<div class="modal fade" id="zoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="zoneModalTitle">Nueva Zona</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="zoneForm">
                    <input type="hidden" name="zone_id" id="zone_id">
                    <input type="hidden" name="furnace_id" value="<?php echo $furnace_id; ?>">
                    <div class="mb-3">
                        <label for="zoneName" class="form-label">Nombre de la Zona</label>
                        <input type="text" class="form-control" id="zoneName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="zoneDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" id="zoneDescription" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Posición</label>
                        <div class="row">
                            <div class="col">
                                <label for="positionX" class="form-label">X</label>
                                <input type="number" class="form-control" id="positionX" name="position_x" required>
                            </div>
                            <div class="col">
                                <label for="positionY" class="form-label">Y</label>
                                <input type="number" class="form-control" id="positionY" name="position_y" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="saveZone()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para la página -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeLayoutEditor(<?php echo $furnace_id; ?>);
});
</script>