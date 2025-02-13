<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$inspection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener información de la inspección
$query = "SELECT i.*, f.name as furnace_name, c.company 
          FROM inspections i
          JOIN furnaces f ON i.furnace_id = f.id
          JOIN clients c ON f.client_id = c.id
          WHERE i.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$inspection_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo "<div class='alert alert-danger'>Inspección no encontrada</div>";
    return;
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#" data-section="furnaces">Hornos</a></li>
                    <li class="breadcrumb-item"><a href="#" data-section="inspections">Inspecciones</a></li>
                    <li class="breadcrumb-item active">Detalle de Inspección</li>
                </ol>
            </nav>
            <div class="d-flex justify-content-between align-items-center">
                <h2>Inspección - <?php echo $inspection['furnace_name']; ?></h2>
                <div>
                    <button class="btn btn-primary" onclick="generateReport(<?php echo $inspection_id; ?>)">
                        <i class="fas fa-file-pdf"></i> Generar Reporte
                    </button>
                </div>
            </div>
            <p class="text-muted">Cliente: <?php echo $inspection['company']; ?></p>
        </div>
    </div>

    <!-- Información general -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Información General</h5>
                    <table class="table table-borderless">
                        <tr>
                            <th>Fecha:</th>
                            <td><?php echo date('d/m/Y', strtotime($inspection['inspection_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Inspector:</th>
                            <td><?php echo $inspection['inspector']; ?></td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td><span class="badge bg-success"><?php echo $inspection['status']; ?></span></td>
                        </tr>
                    </table>
                    <?php if ($inspection['notes']): ?>
                        <h6>Notas Generales:</h6>
                        <p><?php echo nl2br($inspection['notes']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Imágenes por zona -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Inspección por Zonas</h5>
                    <?php
                    $query = "SELECT z.*, COUNT(ii.id) as image_count 
                             FROM zones z
                             LEFT JOIN inspection_images ii ON z.id = ii.zone_id AND ii.inspection_id = ?
                             WHERE z.furnace_id = ?
                             GROUP BY z.id
                             ORDER BY z.name";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$inspection_id, $inspection['furnace_id']]);
                    
                    while ($zone = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <div class="zone-section mb-4">
                            <h6><?php echo $zone['name']; ?></h6>
                            <div class="row">
                                <?php
                                $imagesQuery = "SELECT * FROM inspection_images 
                                              WHERE inspection_id = ? AND zone_id = ?";
                                $imagesStmt = $db->prepare($imagesQuery);
                                $imagesStmt->execute([$inspection_id, $zone['id']]);
                                
                                while ($image = $imagesStmt->fetch(PDO::FETCH_ASSOC)):
                                ?>
                                    <div class="col-md-3 mb-3">
                                        <div class="card">
                                            <img src="<?php echo $image['image_path']; ?>" 
                                                 class="card-img-top inspection-image" 
                                                 alt="Inspección"
                                                 onclick="showImageModal(this.src)">
                                            <?php if ($image['notes']): ?>
                                                <div class="card-body">
                                                    <p class="card-text small"><?php echo $image['notes']; ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver imágenes -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body p-0">
                <img src="" id="modalImage" class="img-fluid">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>