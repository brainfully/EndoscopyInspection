class InspectionManager {
    constructor() {
        this.form = document.getElementById('inspectionForm');
        this.imagePreviews = {};
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Manejar envío del formulario
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveInspection();
        });

        // Manejar preview de imágenes
        document.querySelectorAll('.zone-images').forEach(input => {
            input.addEventListener('change', (e) => {
                this.handleImagePreview(e.target);
            });
        });
    }

    handleImagePreview(input) {
        const zoneId = input.name.match(/zone_(\d+)_images/)[1];
        const previewContainer = document.getElementById(`preview_zone_${zoneId}`);
        previewContainer.innerHTML = '';

        if (input.files && input.files.length > 0) {
            for (let i = 0; i < input.files.length; i++) {
                const reader = new FileReader();
                const imagePreview = document.createElement('div');
                imagePreview.className = 'image-preview-item';

                reader.onload = (e) => {
                    imagePreview.innerHTML = `
                        <img src="${e.target.result}" class="img-thumbnail m-1" style="max-height: 150px">
                        <button type="button" class="btn btn-sm btn-danger remove-image" data-index="${i}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                };

                reader.readAsDataURL(input.files[i]);
                previewContainer.appendChild(imagePreview);
            }
        }
    }

    async saveInspection() {
        if (!this.form.checkValidity()) {
            this.form.reportValidity();
            return;
        }

        const formData = new FormData(this.form);
        
        try {
            const response = await fetch('api/inspections.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', 'Inspección guardada correctamente');
                // Redirigir a la vista de inspección
                window.location.href = `?section=inspections&view=detail&id=${data.inspection_id}`;
            } else {
                showNotification('error', data.message || 'Error al guardar la inspección');
            }
        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'Error al procesar la solicitud');
        }
    }

    cancelInspection() {
        if (confirm('¿Está seguro de cancelar la inspección? Los cambios no guardados se perderán.')) {
            window.location.href = '?section=inspections';
        }
    }
}

// Funciones auxiliares
function showNotification(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.getElementById('notifications').appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.inspectionManager = new InspectionManager();
});