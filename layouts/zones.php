<?php
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

if (!$furnace) {
    echo '<div class="alert alert-danger">Horno no encontrado</div>';
    return;
}
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Gestión de Zonas - <?php echo htmlspecialchars($furnace['name']); ?></h2>
            <p class="text-muted">Cliente: <?php echo htmlspecialchars($furnace['company']); ?></p>
        </div>
        <div>
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
            <button type="button" class="btn btn-primary" onclick="showZoneModal()">
                <i class="fas fa-plus"></i> Nueva Zona
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Layout del horno -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Layout del Horno</h6>
                </div>
                <div class="card-body">
                    <div class="layout-container">
                        <?php if ($furnace['layout_image']): ?>
                            <img src="uploads/layouts/<?php echo $furnace['layout_image']; ?>" 
                                 class="img-fluid" alt="Layout del horno"
                                 id="layoutImage">
                            <div id="zoneMarkers"></div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No hay layout disponible para este horno
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de zonas -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Zonas Configuradas</h6>
                </div>
                <div class="card-body">
                    <div id="zonesList">
                        <?php
                        $query = "SELECT z.*, 
                                 (SELECT COUNT(*) FROM inspection_images WHERE zone_id = z.id) as image_count
                                 FROM zones z 
                                 WHERE z.furnace_id = ? 
                                 ORDER BY z.name";
                        $stmt = $db->prepare($query);
                        $stmt->execute([$furnace_id]);
                        
                        while ($zone = $stmt->fetch(PDO::FETCH_ASSOC)):
                        ?>
                            <div class="zone-item mb-3 p-2 border rounded" data-zone-id="<?php echo $zone['id']; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?php echo htmlspecialchars($zone['name']); ?></strong>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-primary"
                                                onclick="editZone(<?php echo $zone['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="deleteZone(<?php echo $zone['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if ($zone['description']): ?>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($zone['description']); ?>
                                    </small>
                                <?php endif; ?>
                                <small class="text-info">
                                    <?php echo $zone['image_count']; ?> imágenes registradas
                                </small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nueva/Editar Zona -->
<div class="modal fade" id="zoneModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="zoneModalTitle">Nueva Zona</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="zoneForm">
                    <input type="hidden" id="zone_id" name="id">
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
                        <label class="form-label">Posición en el Layout</label>
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
                        <small class="form-text text-muted">
                            Haga clic en el layout para establecer la posición
                        </small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveZone()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para zonas -->
<script src="assets/js/zones.js"></script>