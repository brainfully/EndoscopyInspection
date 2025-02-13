<?php
if (!$inspection_id) {
    echo '<div class="alert alert-danger">No se ha especificado una inspección</div>';
    return;
}

// Obtener información de la inspección
$query = "SELECT i.*, f.name as furnace_name, f.layout_image, c.company 
          FROM inspections i
          JOIN furnaces f ON i.furnace_id = f.id
          JOIN clients c ON f.client_id = c.id
          WHERE i.id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$inspection_id]);
$inspection = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inspection) {
    echo '<div class="alert alert-danger">Inspección no encontrada</div>';
    return;
}
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Detalles de Inspección</h2>
            <p class="text-muted">
                Horno: <?php echo htmlspecialchars($inspection['furnace_name']); ?> | 
                Cliente: <?php echo htmlspecialchars($inspection['company']); ?>
            </p>
        </div>
        <div class="btn-group">
            <button type="button" class="btn btn-secondary" onclick="history.back()">
                <i class="fas fa-arrow-left"></i> Volver
            </button>
            <button type="button" class="btn btn-primary" onclick="generateReport(<?php echo $inspection_id; ?>)">
                <i class="fas fa-file-pdf"></i> Generar Reporte
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Información general -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th>Fecha:</th>
                            <td><?php echo date('d/m/Y', strtotime($inspection['inspection_date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Inspector:</th>
                            <td><?php echo htmlspecialchars($inspection['inspector']); ?></td>
                        </tr>
                        <tr>
                            <th>Estado:</th>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $inspection['status'] === 'completed' ? 'success' : 
                                        ($inspection['status'] === 'in_progress' ? 'info' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($inspection['status']); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                    <?php if ($inspection['notes']): ?>
                        <div class="mt-3">
                            <h6>Notas Generales:</h6>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($inspection['notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Layout del horno -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Layout del Horno</h6>
                </div>
                <div class="card-body">
                    <?php if ($inspection['layout_image']): ?>
                        <div class="layout-container">
                            <img src="uploads/layouts/<?php echo $inspection['layout_image']; ?>" 
                                 class="img-fluid" alt="Layout del horno">
                            <div id="zoneMarkers"></div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No hay layout disponible para este horno
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Imágenes por zona -->
    <div class="card shadow mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">Imágenes por Zona</h6>
        </div>
        <div class="card-body">
            <?php
            // Obtener zonas e imágenes
            $query = "SELECT z.*, 
                     (SELECT COUNT(*) FROM inspection_images 
                      WHERE zone_id = z.id AND inspection_id = ?) as image_count
                     FROM zones z 
                     WHERE z.furnace_id = ? 
                     ORDER BY z.name";
            $stmt = $db->prepare($query);
            $stmt->execute([$inspection_id, $inspection['furnace_id']]);
            
            while ($zone = $stmt->fetch(PDO::FETCH_ASSOC)):
                if ($zone['image_count'] > 0):
            ?>
            <div class="zone-section mb-4">
                <h5 class="border-bottom pb-2"><?php echo htmlspecialchars($zone['name']); ?></h5>
                <div class="row">
                    <?php
                    $query = "SELECT * FROM inspection_images 
                             WHERE inspection_id = ? AND zone_id = ?";
                    $imgStmt = $db->prepare($query);
                    $imgStmt->execute([$inspection_id, $zone['id']]);
                    
                    while ($image = $imgStmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <div class="col-md-3 mb-3">
                        <div class="card">
                            <img src="uploads/inspections/<?php echo $image['image_path']; ?>" 
                                 class="card-img-top inspection-image" 
                                 alt="Imagen de inspección"
                                 onclick="showImageModal(this.src, '<?php echo $zone['name']; ?>')">
                            <?php if ($image['notes']): ?>
                            <div class="card-body">
                                <p class="card-text small">
                                    <?php echo nl2br(htmlspecialchars($image['notes'])); ?>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php 
                endif;
            endwhile; 
            ?>
        </div>
    </div>
</div>

<!-- Modal para ver imágenes -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <img src="" class="img-fluid w-100">
            </div>
        </div>
    </div>
</div>

<script>
function showImageModal(src, zoneName) {
    const modal = document.getElementById('imageModal');
    const modalTitle = modal.querySelector('.modal-title');
    const modalImage = modal.querySelector('img');
    
    modalTitle.textContent = `Zona: ${zoneName}`;
    modalImage.src = src;
    
    new bootstrap.Modal(modal).show();
}
</script>