class ZoneManager {
    constructor() {
        this.furnaceId = document.querySelector('input[name="furnace_id"]').value;
        this.layoutImage = document.getElementById('layoutImage');
        this.zoneMarkers = document.getElementById('zoneMarkers');
        this.zones = [];
        this.selectedZone = null;
        this.isAddingZone = false;

        this.initializeEventListeners();
        this.loadZones();
    }

    initializeEventListeners() {
        // Evento para click en el layout
        if (this.layoutImage) {
            this.layoutImage.parentElement.addEventListener('click', (e) => {
                if (this.isAddingZone) {
                    const rect = this.layoutImage.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    // Actualizar campos de posición
                    document.getElementById('positionX').value = Math.round(x);
                    document.getElementById('positionY').value = Math.round(y);
                    
                    // Mostrar marcador temporal
                    this.showTemporaryMarker(x, y);
                }
            });
        }
    }

    async loadZones() {
        try {
            const response = await fetch(`api/zones.php?furnace_id=${this.furnaceId}`);
            const data = await response.json();

            if (data.success) {
                this.zones = data.zones;
                this.renderZoneMarkers();
            }
        } catch (error) {
            console.error('Error loading zones:', error);
            showNotification('error', 'Error al cargar las zonas');
        }
    }

    renderZoneMarkers() {
        if (!this.zoneMarkers) return;

        this.zoneMarkers.innerHTML = '';
        this.zones.forEach(zone => {
            const marker = document.createElement('div');
            marker.className = 'zone-marker';
            marker.style.left = `${zone.position_x}px`;
            marker.style.top = `${zone.position_y}px`;
            marker.title = zone.name;
            marker.dataset.zoneId = zone.id;

            marker.addEventListener('click', (e) => {
                e.stopPropagation();
                this.editZone(zone.id);
            });

            this.zoneMarkers.appendChild(marker);
        });
    }

    showTemporaryMarker(x, y) {
        const tempMarker = document.createElement('div');
        tempMarker.className = 'zone-marker temporary';
        tempMarker.style.left = `${x}px`;
        tempMarker.style.top = `${y}px`;
        
        // Eliminar marcador temporal anterior si existe
        const oldTemp = this.zoneMarkers.querySelector('.temporary');
        if (oldTemp) oldTemp.remove();
        
        this.zoneMarkers.appendChild(tempMarker);
    }

    showZoneModal(zoneId = null) {
        const modal = document.getElementById('zoneModal');
        const modalTitle = document.getElementById('zoneModalTitle');
        const form = document.getElementById('zoneForm');

        form.reset();
        document.getElementById('zone_id').value = '';
        this.isAddingZone = !zoneId;

        if (zoneId) {
            modalTitle.textContent = 'Editar Zona';
            this.loadZoneData(zoneId);
        } else {
            modalTitle.textContent = 'Nueva Zona';
        }

        new bootstrap.Modal(modal).show();
    }

    async loadZoneData(zoneId) {
        try {
            const response = await fetch(`api/zones.php?id=${zoneId}`);
            const data = await response.json();

            if (data.success) {
                const zone = data.zone;
                document.getElementById('zone_id').value = zone.id;
                document.getElementById('zoneName').value = zone.name;
                document.getElementById('zoneDescription').value = zone.description;
                document.getElementById('positionX').value = zone.position_x;
                document.getElementById('positionY').value = zone.position_y;

                this.selectedZone = zone;
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al cargar los datos de la zona');
        }
    }

    async saveZone() {
        const form = document.getElementById('zoneForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const zoneId = formData.get('id');
        const method = zoneId ? 'PUT' : 'POST';

        try {
            const response = await fetch('api/zones.php', {
                method: method,
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('zoneModal')).hide();
                this.loadZones();
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al guardar la zona');
        }
    }

    async deleteZone(zoneId) {
        if (!confirm('¿Está seguro de eliminar esta zona? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            const response = await fetch(`api/zones.php?id=${zoneId}`, {
                method: 'DELETE'
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message);
                this.loadZones();
                location.reload();
            } else {
                showNotification('error', data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al eliminar la zona');
        }
    }
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.zoneManager = new ZoneManager();
});

// Funciones globales para los eventos onclick
function showZoneModal(zoneId = null) {
    window.zoneManager.showZoneModal(zoneId);
}

function editZone(zoneId) {
    window.zoneManager.showZoneModal(zoneId);
}

function deleteZone(zoneId) {
    window.zoneManager.deleteZone(zoneId);
}

function saveZone() {
    window.zoneManager.saveZone();
}