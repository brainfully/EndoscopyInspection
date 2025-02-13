<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

// Obtener lista de hornos con información del cliente
$query = "SELECT f.*, c.company,
          (SELECT COUNT(*) FROM zones WHERE furnace_id = f.id) as zone_count,
          (SELECT COUNT(*) FROM inspections WHERE furnace_id = f.id) as inspection_count,
          (SELECT MAX(inspection_date) FROM inspections WHERE furnace_id = f.id) as last_inspection
          FROM furnaces f
          LEFT JOIN clients c ON f.client_id = c.id
          ORDER BY c.company, f.name";
$stmt = $db->prepare($query);
$stmt->execute();
$furnaces = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener lista de clientes para el formulario
$query = "SELECT id, company FROM clients ORDER BY company";
$stmt = $db->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Hornos</h2>
        <button type="button" class="btn btn-primary" onclick="showFurnaceModal()">
            <i class="fas fa-plus"></i> Nuevo Horno
        </button>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label for="clientFilter" class="form-label">Filtrar por Cliente</label>
                    <select class="form-select" id="clientFilter">
                        <option value="">Todos los clientes</option>
                        <?php foreach ($clients as $client): ?>
                            <option value="<?php echo $client['id']; ?>">
                                <?php echo htmlspecialchars($client['company']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de hornos -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="furnacesTable">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Nombre/ID</th>
                            <th>Ubicación</th>
                            <th>Zonas</th>
                            <th>Inspecciones</th>
                            <th>Última Inspección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($furnaces as $furnace): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($furnace['company']); ?></td>
                            <td><?php echo htmlspecialchars($furnace['name']); ?></td>
                            <td><?php echo htmlspecialchars($furnace['location']); ?></td>
                            <td><?php echo $furnace['zone_count']; ?></td>
                            <td><?php echo $furnace['inspection_count']; ?></td>
                            <td>
                                <?php 
                                if ($furnace['last_inspection']) {
                                    echo date('d/m/Y', strtotime($furnace['last_inspection']));
                                } else {
                                    echo '<span class="text-muted">Sin inspecciones</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewFurnace(<?php echo $furnace['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="editFurnace(<?php echo $furnace['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" 
                                            onclick="manageZones(<?php echo $furnace['id']; ?>)">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="newInspection(<?php echo $furnace['id']; ?>)">
                                        <i class="fas fa-clipboard-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteFurnace(<?php echo $furnace['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nuevo/Editar Horno -->
<div class="modal fade" id="furnaceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="furnaceModalTitle">Nuevo Horno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="furnaceForm">
                    <input type="hidden" id="furnace_id" name="id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Cliente</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">Seleccionar Cliente</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo htmlspecialchars($client['company']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre/Identificador</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="location" class="form-label">Ubicación</label>
                        <input type="text" class="form-control" id="location" name="location">
                    </div>
                    
                    <div class="mb-3">
                        <label for="layout_image" class="form-label">Layout del Horno</label>
                        <input type="file" class="form-control" id="layout_image" name="layout_image" 
                               accept="image/*">
                        <div id="currentLayout" class="mt-2"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveFurnace()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Horno -->
<div class="modal fade" id="viewFurnaceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Horno</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información General</h6>
                        <table class="table table-borderless">
                            <tr>
                                <th>Cliente:</th>
                                <td id="view_company"></td>
                            </tr>
                            <tr>
                                <th>Nombre/ID:</th>
                                <td id="view_name"></td>
                            </tr>
                            <tr>
                                <th>Ubicación:</th>
                                <td id="view_location"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Layout</h6>
                        <div id="view_layout" class="text-center"></div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Zonas del Horno</h6>
                        <div id="view_zones_list"></div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Últimas Inspecciones</h6>
                        <div id="view_inspections_list"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para hornos -->
<script src="assets/js/furnaces.js"></script>