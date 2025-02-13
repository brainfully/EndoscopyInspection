<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$furnace_id = isset($_GET['furnace_id']) ? intval($_GET['furnace_id']) : null;
$inspection_id = isset($_GET['id']) ? intval($_GET['id']) : null;

switch ($action) {
    case 'new':
        include 'layouts/inspections/new.php';
        break;
    case 'detail':
        include 'layouts/inspections/detail.php';
        break;
    default:
        // Listado de inspecciones
        $query = "SELECT i.*, f.name as furnace_name, c.company,
                  (SELECT COUNT(*) FROM inspection_images WHERE inspection_id = i.id) as image_count
                  FROM inspections i
                  JOIN furnaces f ON i.furnace_id = f.id
                  JOIN clients c ON f.client_id = c.id
                  ORDER BY i.inspection_date DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $inspections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Inspecciones</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-plus"></i> Nueva Inspección
            </button>
            <ul class="dropdown-menu">
                <?php
                $query = "SELECT f.id, f.name, c.company 
                         FROM furnaces f
                         JOIN clients c ON f.client_id = c.id
                         ORDER BY c.company, f.name";
                $stmt = $db->prepare($query);
                $stmt->execute();
                while ($furnace = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<li><a class='dropdown-item' href='?section=inspections&action=new&furnace_id={$furnace['id']}'>
                            {$furnace['company']} - {$furnace['name']}
                          </a></li>";
                }
                ?>
            </ul>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <label for="clientFilter" class="form-label">Cliente</label>
                    <select class="form-select" id="clientFilter">
                        <option value="">Todos</option>
                        <?php
                        $query = "SELECT DISTINCT c.id, c.company 
                                 FROM clients c
                                 JOIN furnaces f ON f.client_id = c.id
                                 JOIN inspections i ON i.furnace_id = f.id
                                 ORDER BY c.company";
                        $stmt = $db->prepare($query);
                        $stmt->execute();
                        while ($client = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$client['id']}'>{$client['company']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="furnaceFilter" class="form-label">Horno</label>
                    <select class="form-select" id="furnaceFilter">
                        <option value="">Todos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="dateRangeFilter" class="form-label">Rango de Fechas</label>
                    <input type="text" class="form-control" id="dateRangeFilter">
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Estado</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">Todos</option>
                        <option value="completed">Completada</option>
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En Progreso</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de inspecciones -->
    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="inspectionsTable">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Horno</th>
                            <th>Inspector</th>
                            <th>Imágenes</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inspections as $inspection): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($inspection['inspection_date'])); ?></td>
                            <td><?php echo htmlspecialchars($inspection['company']); ?></td>
                            <td><?php echo htmlspecialchars($inspection['furnace_name']); ?></td>
                            <td><?php echo htmlspecialchars($inspection['inspector']); ?></td>
                            <td><?php echo $inspection['image_count']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $inspection['status'] === 'completed' ? 'success' : 
                                        ($inspection['status'] === 'in_progress' ? 'info' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($inspection['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-primary"
                                            onclick="generateReport(<?php echo $inspection['id']; ?>)">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            onclick="deleteInspection(<?php echo $inspection['id']; ?>)">
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

<!-- JavaScript específico para inspecciones -->
<script src="assets/js/inspections.js"></script>
<?php
        break;
}
?>