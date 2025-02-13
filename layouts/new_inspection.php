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
                    <li class="breadcrumb-item"><a href="#" data-section="inspections">Inspecciones</a></li>
                    <li class="breadcrumb-item active">Nueva Inspección</li>
                </ol>
            </nav>
            <h2>Nueva Inspección - <?php echo $furnace['name']; ?></h2>
            <p class="text-muted">Cliente: <?php echo $furnace['company']; ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <form id="inspectionForm" class="needs-validation" novalidate>
                        <input type="hidden" name="furnace_id" value="<?php echo $furnace_id; ?>">
                        
                        <!-- Información básica -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="inspection_date" class="form-label">Fecha de Inspección</label>
                                    <input type="date" class="form-control" id="inspection_date" name="inspection_date" required>
                                </div>
                                <div class="mb-3">
                                    <label for="inspector" class="form-label">Inspector</label>
                                    <input type="text" class="form-control" id="inspector" name="inspector" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notas Generales</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="4"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Zonas del horno -->
                        <h4 class="mb-3">Imágenes por Zona</h4>
                        <div id="zonesContainer">
                            <?php
                            $query = "SELECT * FROM zones WHERE furnace_id = ? ORDER BY name";
                            $stmt = $db->prepare($query);
                            $stmt->execute([$furnace_id]);
                            while ($zone = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<div class='card mb-3'>";
                                echo "<div class='card-header'>{$zone['name']}</div>";
                                echo "<div class='card-body'>";
                                echo "<div class='row'>";
                                echo "<div class='col-md-6'>";
                                echo "<div class='mb-3'>";
                                echo "<label class='form-label'>Imágenes de la Zona</label>";
                                echo "<input type='file' class='form-control zone-images' name='zone_{$zone['id']}_images[]' multiple accept='image/*'>";
                                echo "</div>";
                                echo "</div>";
                                echo "<div class='col-md-6'>";
                                echo "<div class='mb-3'>";
                                echo "<label class='form-label'>Notas de la Zona</label>";
                                echo "<textarea class='form-control' name='zone_{$zone['id']}_notes' rows='3'></textarea>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                                echo "<div class='image-preview-container' id='preview_zone_{$zone['id']}'></div>";
                                echo "</div>";
                                echo "</div>";
                                echo "</div>";
                            }
                            ?>
                        </div>

                        <!-- Botones de acción -->
                        <div class="row mt-4">
                            <div class="col">
                                <button type="button" class="btn btn-secondary" onclick="cancelInspection()">Cancelar</button>
                                <button type="submit" class="btn btn-primary">Guardar Inspección</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>