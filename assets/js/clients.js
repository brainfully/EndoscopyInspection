// Gestión de clientes
class ClientManager {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Inicializar DataTable
        $('#clientsTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json'
            }
        });
    }

    async showClientModal(clientId = null) {
        const modal = document.getElementById('clientModal');
        const modalTitle = document.getElementById('clientModalTitle');
        const form = document.getElementById('clientForm');

        form.reset();
        document.getElementById('client_id').value = '';

        if (clientId) {
            modalTitle.textContent = 'Editar Cliente';
            await this.loadClientData(clientId);
        } else {
            modalTitle.textContent = 'Nuevo Cliente';
        }

        new bootstrap.Modal(modal).show();
    }

    async loadClientData(clientId) {
        try {
            const response = await fetch(`api/clients.php?id=${clientId}`);
            const data = await response.json();

            if (data.success) {
                const client = data.client;
                document.getElementById('client_id').value = client.id;
                document.getElementById('company').value = client.company;
                document.getElementById('name').value = client.name;
                document.getElementById('contact_email').value = client.contact_email;
                document.getElementById('contact_phone').value = client.contact_phone;
                document.getElementById('address').value = client.address;
            } else {
                showNotification('error', 'Error al cargar los datos del cliente');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al comunicarse con el servidor');
        }
    }

    async saveClient() {
        const form = document.getElementById('clientForm');
        const formData = new FormData(form);

        try {
            const response = await fetch('api/clients.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('clientModal')).hide();
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al guardar el cliente');
        }
    }

    async viewClient(clientId) {
        try {
            const response = await fetch(`api/clients.php?id=${clientId}`);
            const data = await response.json();

            if (data.success) {
                const client = data.client;
                
                // Llenar información del cliente
                document.getElementById('view_company').textContent = client.company;
                document.getElementById('view_name').textContent = client.name;
                document.getElementById('view_email').textContent = client.contact_email;
                document.getElementById('view_phone').textContent = client.contact_phone;
                document.getElementById('view_address').textContent = client.address;

                // Cargar lista de hornos
                const furnacesResponse = await fetch(`api/furnaces.php?client_id=${clientId}`);
                const furnacesData = await furnacesResponse.json();

                const furnacesList = document.getElementById('view_furnaces_list');
                furnacesList.innerHTML = '';

                if (furnacesData.success && furnacesData.furnaces.length > 0) {
                    const ul = document.createElement('ul');
                    ul.className = 'list-group';
                    
                    furnacesData.furnaces.forEach(furnace => {
                        const li = document.createElement('li');
                        li.className = 'list-group-item d-flex justify-content-between align-items-center';
                        li.innerHTML = `
                            ${furnace.name}
                            <span class="badge bg-primary rounded-pill">
                                ${furnace.inspection_count} inspecciones
                            </span>
                        `;
                        ul.appendChild(li);
                    });
                    
                    furnacesList.appendChild(ul);
                } else {
                    furnacesList.innerHTML = '<p class="text-muted">No hay hornos registrados</p>';
                }

                new bootstrap.Modal(document.getElementById('viewClientModal')).show();
            } else {
                showNotification('error', 'Error al cargar los datos del cliente');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al comunicarse con el servidor');
        }
    }

    async deleteClient(clientId) {
        if (!confirm('¿Está seguro de eliminar este cliente?')) {
            return;
        }

        try {
            const response = await fetch(`api/clients.php?id=${clientId}`, {
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
            showNotification('error', 'Error al eliminar el cliente');
        }
    }
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.clientManager = new ClientManager();
});

// Funciones globales para los eventos onclick
function showClientModal(clientId = null) {
    window.clientManager.showClientModal(clientId);
}

function editClient(clientId) {
    window.clientManager.showClientModal(clientId);
}

function viewClient(clientId) {
    window.clientManager.viewClient(clientId);
}

function deleteClient(clientId) {
    window.clientManager.deleteClient(clientId);
}

function saveClient() {
    window.clientManager.saveClient();
}