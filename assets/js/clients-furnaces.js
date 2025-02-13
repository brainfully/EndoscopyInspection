// Gestión de Clientes
function viewClient(id) {
    fetch(`api/clients.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar detalles del cliente
                showClientDetails(data.client);
            }
        });
}

function editClient(id) {
    fetch(`api/clients.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Llenar el formulario con los datos del cliente
                fillClientForm(data.client);
                $('#clientModal').modal('show');
            }
        });
}

function saveClient() {
    const form = document.getElementById('clientForm');
    const formData = new FormData(form);

    fetch('api/clients.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#clientModal').modal('hide');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

// Gestión de Hornos
function viewFurnace(id) {
    fetch(`api/furnaces.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar detalles del horno
                showFurnaceDetails(data.furnace);
            }
        });
}

function editFurnace(id) {
    fetch(`api/furnaces.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Llenar el formulario con los datos del horno
                fillFurnaceForm(data.furnace);
                $('#furnaceModal').modal('show');
            }
        });
}

function saveFurnace() {
    const form = document.getElementById('furnaceForm');
    const formData = new FormData(form);

    fetch('api/furnaces.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            $('#furnaceModal').modal('hide');
            location.reload();
        } else {
            alert(data.message);
        }
    });
}

function newInspection(furnaceId) {
    // Redirigir a la página de nueva inspección
    window.location.href = `?section=inspections&action=new&furnace_id=${furnaceId}`;
}

// Filtros
document.getElementById('clientFilter').addEventListener('change', function() {
    const clientId = this.value;
    updateFurnacesList(clientId);
});

function updateFurnacesList(clientId) {
    fetch(`api/furnaces.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar la tabla de hornos con los resultados filtrados
                updateFurnacesTable(data.furnaces);
            }
        });
}