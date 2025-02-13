<?php
if (!$furnace_id) {
    echo '<div class="alert alert-danger">No se ha especificado un horno</div>';
    return;
}

// Obtener información del horno
$query = "SELECT f.*, c.company 
          FROM furnaces f 
          JOIN clients c ON f.client_id = c.id 
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
            <h2>Nueva Inspección</h2>
            <p class="text-muted">
                Horno: <?php echo htmlspecialchars($furnace['name']); ?> | 
                Cliente: <?php echo htmlspecialchars($furnace['company']); ?>
            </p>
        </div>
        <button type="button" class="btn btn-secondary" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Volver
        </button>
    </div>

    <form id="inspectionForm" class="needs-validation" novalidate>
        <input type="hidden" name="furnace_id" value="<?php echo $furnace_id; ?>">
        
        <!-- Información general -->
        <div class="card shadow mb-4">
            <div class="card-header">
                <h6 class="m-0 font-weight-bold text-primary">Información General</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="inspection_date" class="form-label">Fecha de Inspección</label>
                            <input type="date" class="form-control" id="inspection_date" 
                                   name="inspection_date" required 
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="inspector" class="form-label">Inspector</label>
                            <input type="text" class="form-control" id="inspector" 
                                   name="inspector" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="status" class="form-label">Estado</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="completed">Completada</option>
                                <option value="in_progress">En Progreso</option>
                                <option value="pending">Pendiente</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="notes" class="form-label">Notas Generales</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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
                // Obtener zonas del horno
                $query = "SELECT * FROM zones WHERE furnace_id = ? ORDER BY name";
                $stmt = $db->prepare($query);
                $stmt->execute([$furnace_id]);
                
                while ($zone = $stmt->fetch(PDO::FETCH_ASSOC)):
                ?>
                <div class="zone-section mb-4">
                    <h5 class="border-bottom pb-2"><?php echo htmlspecialchars($zone['name']); ?></h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Imágenes</label>
                                <input type="file" class="form-control zone-images" 
                                       name="zone_<?php echo $zone['id']; ?>_images[]" 
                                       multiple accept="image/*" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Notas de la Zona</label>
                                <textarea class="form-control" 
                                          name="zone_<?php echo $zone['id']; ?>_notes" 
                                          rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="image-preview" id="preview_zone_<?php echo $zone['id']; ?>"></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="d-flex justify-content-end mb-4">
            <button type="button" class="btn btn-secondary me-2" onclick="history.back()">
                Cancelar
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Guardar Inspección
            </button>
        </div>
    </form>
</div>

<!-- JavaScript para el formulario de inspección -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('inspectionForm');
    const zoneImages = document.querySelectorAll('.zone-images');

    // Preview de imágenes
    zoneImages.forEach(input => {
        input.addEventListener('change', function(e) {
            const zoneId = this.name.match(/zone_(\d+)_images/)[1];
            const previewDiv = document.getElementById(`preview_zone_${zoneId}`);
            previewDiv.innerHTML = '';

            if (this.files) {
                Array.from(this.files).forEach((file, index) => {
                    const reader = new FileReader();
                    const imgContainer = document.createElement('div');
                    imgContainer.className = 'preview-image-container';

                    reader.onload = function(e) {
                        imgContainer.innerHTML = `
                            <div class="position-relative d-inline-block me-2 mb-2">
                                <img src="${e.target.result}" class="img-thumbnail" 
                                     style="max-height: 150px">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0"
                                        onclick="removeImage(this, '${zoneId}', ${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `;
                    };

                    reader.readAsDataURL(file);
                    previewDiv.appendChild(imgContainer);
                });
            }
        });
    });

    // Manejo del envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!form.checkValidity()) {
            e.stopPropagation();
            form.classList.add('was-validated');
            return;
        }

        try {
            const formData = new FormData(form);
            const response = await fetch('api/inspections.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', 'Inspección guardada correctamente');
                window.location.href = `?section=inspections&action=detail&id=${data.inspection_id}`;
            } else {
                showNotification('error', data.message || 'Error al guardar la inspección');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al procesar la solicitud');
        }
    });
});

function removeImage(button, zoneId, index) {
    const input = document.querySelector(`input[name="zone_${zoneId}_images[]"]`);
    const container = button.closest('.preview-image-container');
    
    // Crear un nuevo FileList sin la imagen eliminada
    const dt = new DataTransfer();
    const { files } = input;
    
    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            dt.items.add(files[i]);
        }
    }
    
    input.files = dt.files;
    container.remove();
}
</script>