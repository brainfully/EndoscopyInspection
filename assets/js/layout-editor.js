// JavaScript Document

class LayoutEditor {
    constructor(furnaceId) {
        this.furnaceId = furnaceId;
        this.canvas = document.getElementById('layoutCanvas');
        this.ctx = this.canvas.getContext('2d');
        this.zones = [];
        this.selectedZone = null;
        this.layoutImage = null;
        
        this.initializeCanvas();
        this.loadLayoutImage();
        this.loadZones();
        this.setupEventListeners();
    }

    initializeCanvas() {
        // Ajustar tamaño del canvas al contenedor
        const container = this.canvas.parentElement;
        this.canvas.width = container.offsetWidth;
        this.canvas.height = container.offsetWidth * 0.6; // Proporción 5:3
    }

    async loadLayoutImage() {
        try {
            const response = await fetch(`api/furnaces.php?id=${this.furnaceId}&action=layout`);
            const data = await response.json();
            
            if (data.success && data.layout_url) {
                this.layoutImage = new Image();
                this.layoutImage.src = data.layout_url;
                this.layoutImage.onload = () => this.render();
            }
        } catch (error) {
            console.error('Error loading layout image:', error);
        }
    }

    async loadZones() {
        try {
            const response = await fetch(`api/zones.php?furnace_id=${this.furnaceId}`);
            const data = await response.json();
            
            if (data.success) {
                this.zones = data.zones;
                this.render();
            }
        } catch (error) {
            console.error('Error loading zones:', error);
        }
    }

    setupEventListeners() {
        this.canvas.addEventListener('click', (e) => this.handleCanvasClick(e));
        this.canvas.addEventListener('mousemove', (e) => this.handleMouseMove(e));
        
        // Responsive canvas
        window.addEventListener('resize', () => {
            this.initializeCanvas();
            this.render();
        });
    }

    handleCanvasClick(event) {
        const rect = this.canvas.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // Verificar si se hizo clic en una zona
        const clickedZone = this.zones.find(zone => 
            Math.abs(zone.position_x - x) < 10 && 
            Math.abs(zone.position_y - y) < 10
        );

        if (clickedZone) {
            this.selectedZone = clickedZone;
            this.showZoneDetails(clickedZone);
        } else if (document.getElementById('zoneForm').style.display === 'block') {
            // Si estamos en modo de agregar zona, guardar la posición
            document.getElementById('positionX').value = Math.round(x);
            document.getElementById('positionY').value = Math.round(y);
        }
    }

    render() {
        // Limpiar canvas
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        // Dibujar imagen de layout
        if (this.layoutImage) {
            this.ctx.drawImage(this.layoutImage, 0, 0, this.canvas.width, this.canvas.height);
        }
        
        // Dibujar zonas
        this.zones.forEach(zone => {
            this.ctx.beginPath();
            this.ctx.arc(zone.position_x, zone.position_y, 5, 0, Math.PI * 2);
            this.ctx.fillStyle = zone === this.selectedZone ? '#ff0000' : '#00ff00';
            this.ctx.fill();
            
            // Etiqueta de la zona
            this.ctx.fillStyle = '#000';
            this.ctx.font = '12px Arial';
            this.ctx.fillText(zone.name, zone.position_x + 10, zone.position_y + 5);
        });
    }

    showZoneDetails(zone) {
        // Mostrar modal con detalles de la zona
        document.getElementById('zoneModalTitle').textContent = 'Editar Zona';
        document.getElementById('zone_id').value = zone.id;
        document.getElementById('zoneName').value = zone.name;
        document.getElementById('zoneDescription').value = zone.description;
        document.getElementById('positionX').value = zone.position_x;
        document.getElementById('positionY').value = zone.position_y;
        
        new bootstrap.Modal(document.getElementById('zoneModal')).show();
    }
}

// Inicializar editor cuando el documento esté listo
function initializeLayoutEditor(furnaceId) {
    window.layoutEditor = new LayoutEditor(furnaceId);
}