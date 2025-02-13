class InspectionSystem {
    constructor() {
        this.initializeEventListeners();
        this.layoutEditor = null;
    }

    initializeEventListeners() {
        // Navegación
        document.querySelectorAll('[data-section]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.loadSection(e.target.dataset.section);
            });
        });

        // Manejo de formularios
        document.addEventListener('submit', (e) => {
            if (e.target.matches('.ajax-form')) {
                e.preventDefault();
                this.handleFormSubmit(e.target);
            }
        });
    }

    async loadSection(section) {
        try {
            const response = await fetch(`layouts/${section}.php`);
            const content = await response.text();
            document.getElementById('main-content').innerHTML = content;

            // Inicializar componentes específicos de la sección
            if (section === 'inspections') {
                this.initializeLayoutEditor();
            }
        } catch (error) {
            console.error('Error loading section:', error);
        }
    }

    initializeLayoutEditor() {
        const canvas = document.getElementById('layout-canvas');
        if (canvas) {
            this.layoutEditor = new LayoutEditor(canvas);
        }
    }

    async handleFormSubmit(form) {
        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showNotification('success', result.message);
                // Recargar sección si es necesario
                if (result.reload) {
                    this.loadSection(result.section);
                }
            } else {
                this.showNotification('error', result.message);
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            this.showNotification('error', 'Error al procesar la solicitud');
        }
    }

    showNotification(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.getElementById('notifications').appendChild(alertDiv);
        
        // Auto-cerrar después de 5 segundos
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Inicializar el sistema cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    window.inspectionSystem = new InspectionSystem();
});