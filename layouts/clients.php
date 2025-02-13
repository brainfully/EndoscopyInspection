<?php
$database = new Database();
$db = $database->getConnection();

// Obtener lista de clientes
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM furnaces WHERE client_id = c.id) as furnace_count,
          (SELECT COUNT(*) FROM inspections i 
           JOIN furnaces f ON i.furnace_id = f.id 
           WHERE f.client_id = c.id) as inspection_count
          FROM clients c 
          ORDER BY c.company";
$stmt = $db->prepare($query);
$stmt->execute();
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Clientes</h2>
        <button type="button" class="btn btn-primary" onclick="showClientModal()">
            <i class="fas fa-plus"></i> Nuevo Cliente
        </button>
    </div>

    <!-- Tabla de clientes -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="clientsTable">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Contacto</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Hornos</th>
                            <th>Inspecciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['company']); ?></td>
                            <td><?php echo htmlspecialchars($client['name']); ?></td>
                            <td><?php echo htmlspecialchars($client['contact_email']); ?></td>
                            <td><?php echo htmlspecialchars($client['contact_phone']); ?></td>
                            <td><?php echo $client['furnace_count']; ?></td>
                            <td><?php echo $client['inspection_count']; ?></td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-info" 
                                            onclick="viewClient(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" 
                                            onclick="editClient(<?php echo $client['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="deleteClient(<?php echo $client['id']; ?>)">
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

<!-- Modal para Nuevo/Editar Cliente -->
<div class="modal fade" id="clientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientModalTitle">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="clientForm">
                    <input type="hidden" id="client_id" name="id">
                    
                    <div class="mb-3">
                        <label for="company" class="form-label">Empresa</label>
                        <input type="text" class="form-control" id="company" name="company" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label">Nombre de Contacto</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contact_phone" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone">
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Dirección</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="saveClient()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Ver Cliente -->
<div class="modal fade" id="viewClientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Información General</h6>
                        <table class="table table-borderless">
                            <tr>
                                <th>Empresa:</th>
                                <td id="view_company"></td>
                            </tr>
                            <tr>
                                <th>Contacto:</th>
                                <td id="view_name"></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td id="view_email"></td>
                            </tr>
                            <tr>
                                <th>Teléfono:</th>
                                <td id="view_phone"></td>
                            </tr>
                            <tr>
                                <th>Dirección:</th>
                                <td id="view_address"></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Hornos</h6>
                        <div id="view_furnaces_list"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para clientes -->
<script src="assets/js/clients.js"></script>