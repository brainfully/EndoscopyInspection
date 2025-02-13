<?php
// Obtener estadísticas generales
$stats = [
    'clients' => $db->query("SELECT COUNT(*) FROM clients")->fetchColumn(),
    'furnaces' => $db->query("SELECT COUNT(*) FROM furnaces")->fetchColumn(),
    'inspections' => $db->query("SELECT COUNT(*) FROM inspections")->fetchColumn(),
    'recent_inspections' => $db->query("
        SELECT i.*, f.name as furnace_name, c.company 
        FROM inspections i
        JOIN furnaces f ON i.furnace_id = f.id
        JOIN clients c ON f.client_id = c.id
        ORDER BY i.inspection_date DESC LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC)
];
?>

<div class="container-fluid">
    <!-- Tarjetas de estadísticas -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Clientes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['clients']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Hornos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['furnaces']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-industry fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Inspecciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['inspections']; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Inspecciones Recientes -->
    <div class="row">
        <div class="col">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inspecciones Recientes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Horno</th>
                                    <th>Inspector</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stats['recent_inspections'] as $inspection): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($inspection['inspection_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($inspection['company']); ?></td>
                                    <td><?php echo htmlspecialchars($inspection['furnace_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inspection['inspector']); ?></td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $inspection['status']; ?></span>
                                    </td>
                                    <td>
                                        <a href="" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="generateReport(<?php echo $inspection['id']; ?>)">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>