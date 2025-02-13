// Gestión de hornos
class FurnaceManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Inicializar DataTable
        $('#furnacesTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            }
        });

        // Filtro por cliente
        document.getElementById('clientFilter').addEventListener('change', (e) => {
            this.filterByClient(e.target.value);
        });

        // Preview de imagen de layout
        document.getElementById('layout_image').addEventListener('change', (e) => {
            this.handleLayoutPreview(e);
        });
    }

    filterByClient(clientId) {
        const table = $('#furnacesTable').DataTable();
        if (clientId) {
            table.column(0).search(clientId).draw();
        } else {
            table.column(0).search('').draw();
        }
    }

    handleLayoutPreview(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('currentLayout');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                preview.innerHTML = `
                    <img src="${e.target.result}" class="img-fluid mt-2" style="max-height: 200px">
                `;
            };
            reader.readAsDataURL(file);
        } else {
            preview.innerHTML = '';
        }
    }

    async showFurnaceModal(furnaceId = null) {
        const modal = document.getElementById('furnaceModal');
        const modalTitle = document.getElementById('furnaceModalTitle');
        const form = document.getElementById('furnaceForm');

        form.reset();
        document.getElementById('furnace_id').value = '';
        document.getElementById('currentLayout').innerHTML = '';

        if (furnaceId) {
            modalTitle.textContent = 'Editar Horno';
            await this.loadFurnaceData(furnaceId);
        } else {
            modalTitle.textContent = 'Nuevo Horno';
        }

        new bootstrap.Modal(modal).show();
    }

    async loadFurnaceData(furnaceId) {
        try {
            const response = await fetch(`api/furnaces.php?id=${furnaceId}`);
            const data = await response.json();

            if (data.success) {
                const furnace = data.furnace;
                document.getElementById('furnace_id').value = furnace.id;
                document.getElementById('client_id').value = furnace.client_id;
                document.getElementById('name').value = furnace.name;
                document.getElementById('location').value = furnace.location;

                if (furnace.layout_image) {
                    document.getElementById('currentLayout').innerHTML = `
                        <img src="uploads/layouts/${furnace.layout_image}" 
                             class="img-fluid mt-2" 
                             style="max-height: 200px">
                    `;
                }
            } else {
                showNotification('error', 'Error al cargar los datos del horno');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al comunicarse con el servidor');
        }
    }

    async saveFurnace() {
        const form = document.getElementById('furnaceForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);

        try {
            const response = await fetch('api/furnaces.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('furnaceModal')).hide();
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al guardar el horno');
        }
    }

    async viewFurnace(furnaceId) {
        try {
            const response = await fetch(`api/furnaces.php?id=${furnaceId}`);
            const data = await response.json();

            if (data.success) {
                const furnace = data.furnace;
                
                // Llenar información general
                document.getElementById('view_company').textContent = furnace.company;
                document.getElementById('view_name').textContent = furnace.name;
                document.getElementById('view_location').textContent = furnace.location;

                // Mostrar layout
                const layoutDiv = document.getElementById('view_layout');
                if (furnace.layout_image) {
                    layoutDiv.innerHTML = `
                        <img src="uploads/layouts/${furnace.layout_image}" 
                             class="img-fluid" alt="Layout del horno">
                    `;
                } else {
                    layoutDiv.innerHTML = '<p class="text-muted">Sin layout disponible</p>';
                }

                // Cargar zonas
                const zonesResponse = await fetch(`api/zones.php?furnace_id=${furnaceId}`);
                const zonesData = await zonesResponse.json();
                
                const zonesList = document.getElementById('view_zones_list');
                if (zonesData.success && zonesData.zones.length > 0) {
                    const table = document.createElement('table');
                    table.className = 'table table-sm';
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Última Inspección</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${zonesData.zones.map(zone => `
                                <tr>
                                    <td>${zone.name}</td>
                                    <td>${zone.description || ''}</td>
                                    <td>${zone.last_inspection || 'Sin inspección'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    `;
                    zonesList.innerHTML = '';
                    zonesList.appendChild(table);
                } else {
                    zonesList.innerHTML = '<p class="text-muted">No hay zonas configuradas</p>';
                }

                // Cargar inspecciones recientes
                const inspectionsResponse = await fetch(`api/inspections.php?furnace_id=${furnaceId}&limit=5`);
                const inspectionsData = await inspectionsResponse.json();
                
                const inspectionsList = document.getElementById('view_inspections_list');
                if (inspectionsData.success && inspectionsData.inspections.length > 0) {
                    const table = document.createElement('table');
                    table.className = 'table table-sm';
                    table.innerHTML = `
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Inspector</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${inspectionsData.inspections.map(inspection => `
                                <tr>
                                    <td>${new Date(inspection.inspection_date).toLocaleDateString()}</td>
                                    <td>${inspection.inspector}</td>
                                    <td>
                                        <span class="badge bg-${this.getStatusBadgeClass(inspection.status)}">
                                            ${inspection.status}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="viewInspection(${inspection.id})">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="generateReport(${inspection.id})">
                                            <i class="fas fa-file-pdf"></i>
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    `;
                    inspectionsList.innerHTML = '';
                    inspectionsList.appendChild(table);
                } else {
                    inspectionsList.innerHTML = '<p class="text-muted">No hay inspecciones registradas</p>';
                }

                new bootstrap.Modal(document.getElementById('viewFurnaceModal')).show();
            } else {
                showNotification('error', 'Error al cargar los datos del horno');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al comunicarse con el servidor');
        }
    }

    async manageZones(furnaceId) {
        // Redirigir a la página de gestión de zonas
        window.location.href = `?section=zones&furnace_id=${furnaceId}`;
    }

    async newInspection(furnaceId) {
        // Redirigir a la página de nueva inspección
        window.location.href = `?section=inspections&action=new&furnace_id=${furnaceId}`;
    }

    async deleteFurnace(furnaceId) {
        if (!confirm('¿Está seguro de eliminar este horno? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const response = await fetch(`api/furnaces.php?id=${furnaceId}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message);
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al eliminar el horno');
        }
    }

    getStatusBadgeClass(status) {
        switch (status.toLowerCase()) {
            case 'completed':
                return 'success';
            case 'pending':
                return 'warning';
            case 'in_progress':
                return 'info';
            default:
                return 'secondary';
        }
    }
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.furnaceManager = new FurnaceManager();
});

// Funciones globales para los eventos onclick
function showFurnaceModal(furnaceId = null) {
    window.furnaceManager.showFurnaceModal(furnaceId);
}

function editFurnace(furnaceId) {
    window.furnaceManager.showFurnaceModal(furnaceId);
}

function viewFurnace(furnaceId) {
    window.furnaceManager.viewFurnace(furnaceId);
}

function manageZones(furnaceId) {
    window.furnaceManager.manageZones(furnaceId);
}

function newInspection(furnaceId) {
    window.furnaceManager.newInspection(furnaceId);
}

function deleteFurnace(furnaceId) {
    window.furnaceManager.deleteFurnace(furnaceId);
}

function saveFurnace() {
    window.furnaceManager.saveFurnace();
}