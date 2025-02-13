document.addEventListener('DOMContentLoaded', function() {
    // Layout Editor
    class LayoutEditor {
        constructor(canvasId) {
            this.canvas = document.getElementById(canvasId);
            this.ctx = this.canvas.getContext('2d');
            this.arrows = [];
            this.selectedZone = null;
            this.initializeEvents();
        }

        initializeEvents() {
            this.canvas.addEventListener('click', (e) => {
                if (this.selectedZone) {
                    const rect = this.canvas.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    this.addArrow(x, y);
                }
            });
        }

        addArrow(x, y, angle = 0) {
            this.arrows.push({
                x: x,
                y: y,
                angle: angle,
                zone: this.selectedZone
            });
            this.render();
        }

        render() {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.drawLayout();
            this.drawArrows();
        }

        // ... más métodos para manipular el layout y las flechas
    }

    // Inicializar el editor cuando se carga una inspección
    const editor = new LayoutEditor('layout-canvas');
});


function generatePDF(inspectionId) {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Agregar encabezado
    doc.setFontSize(20);
    doc.text('Inspección Endoscópica', 20, 20);
    
    // Agregar información del horno
    doc.setFontSize(12);
    doc.text(`Horno: ${furnaceData.name}`, 20, 40);
    doc.text(`Cliente: ${furnaceData.client}`, 20, 50);
    doc.text(`Fecha: ${inspectionData.date}`, 20, 60);
    
    // Agregar imágenes y flechas
    let yOffset = 80;
    inspectionData.zones.forEach(zone => {
        doc.addImage(zone.image, 'JPEG', 20, yOffset, 170, 100);
        // Dibujar flecha
        drawArrow(doc, zone.arrowData);
        yOffset += 120;
        
        if (yOffset > 250) {
            doc.addPage();
            yOffset = 20;
        }
    });
    
    // Guardar PDF
    doc.save(`inspeccion_${inspectionId}.pdf`);
}