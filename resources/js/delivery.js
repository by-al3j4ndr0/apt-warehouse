// Esperar a que el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", function() {
    // Verificar que el elemento origen existe antes de asignar el evento
    const origenElement = document.getElementById("origen");
    if (origenElement) {
        origenElement.onchange = function() { getClientsByOrigen() };
    }
});

function getClientsByOrigen() {
    let origenMenu = document.getElementById("origen");
    let selectedStatus = document.querySelector('input[name="status"]:checked');
    let deliveryIdInput = document.getElementById("deliveryId");
    
    // Validar que se haya seleccionado una opción
    if (!origenMenu || origenMenu.selectedIndex === 0) {
        // Si no hay selección, mostrar mensaje o limpiar tabla
        const container = document.getElementById("tableDiv");
        if (container) {
            container.innerHTML = `
                <div class="form-control">
                    <div class="alert alert-info text-center">
                        Seleccione un origen para ver los clientes
                    </div>
                </div>
            `;
        }
        return;
    }
    
    // Obtener el valor (ID) del option seleccionado
    let origen = origenMenu.value; // Usar value en lugar de id
    let status = selectedStatus.value;
    let deliveryId = deliveryIdInput.value;
    
    // Enviar como JSON o como form data
    const requestData = JSON.stringify({ origen_id: origen, selected_status: status, delivery_id: deliveryId });
    
    const request = new XMLHttpRequest();
    request.onload = function() {
        try {
            // Verificar el estado de la respuesta
            if (request.status !== 200) {
                throw new Error("Error en la petición: " + request.status);
            }
            
            const clientsInfo = JSON.parse(this.responseText);
            const container = document.getElementById("tableDiv");
            
            if (!container) {
                console.error("Elemento tableDiv no encontrado");
                return;
            }
            
            if (Object.values(clientsInfo).length > 0) {
                // Construir tabla completa
                let html = `
                    <div class="form-control">
                        <div class="mb-3">
                            <input type="text" id="search_input" class="form-control" placeholder="Buscar por nombre.." onkeyup="searchTable()">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="clientsTable">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="50">
                                            <input type="checkbox" id="selectAll" onclick="seleccionarTodos()">
                                        </th>
                                        <th onclick="sortTableImproved(1)" style="cursor: pointer;">Nombre ▲▼</th>
                                        <th onclick="sortTableImproved(2)" style="cursor: pointer;">Envíos ▲▼</th>
                                        <th onclick="sortTableImproved(3)" style="cursor: pointer;">Ciudad ▲▼</th>
                                        <th onclick="sortTableImproved(4)" style="cursor: pointer;">Estado ▲▼</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsTableBody">
                `;
                
                // Agregar filas
                Object.values(clientsInfo).forEach(client => {
                    html += `
                        <tr>
                            <td class="text-center">
                                <input class="form-check-input" type="checkbox" 
                                    value="${escapeHtml(client.ci)}" name="clients[]"
                                    ${client.checked ? 'checked' : ''}>
                            </td>
                            <td>${escapeHtml(client.name)}</td>
                            <td class="text-center">${client.count}</td>
                            <td>${escapeHtml(client.city)}</td>
                            <td>${escapeHtml(client.state)}</td>
                        </tr>
                    `;
                });
                
                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                
                container.innerHTML = html;
                
                // Inicializar la búsqueda después de cargar la tabla
                if (typeof attachSearchEvent === 'undefined') {
                    attachSearchEvent();
                }
            } else {
                container.innerHTML = `
                    <div class="form-control">
                        <div class="alert alert-info text-center">
                            No hay clientes disponibles para este origen
                        </div>
                    </div>
                `;
            }
        } catch (error) {
            console.error("Error al procesar la respuesta:", error);
            const container = document.getElementById("tableDiv");
            if (container) {
                container.innerHTML = `
                    <div class="form-control">
                        <div class="alert alert-danger text-center">
                            Error al cargar los clientes. Por favor, intente de nuevo.
                        </div>
                    </div>
                `;
            }
        }
    };
    
    request.onerror = function() {
        console.error("Error de red en la petición");
        const container = document.getElementById("tableDiv");
        if (container) {
            container.innerHTML = `
                <div class="form-control">
                    <div class="alert alert-danger text-center">
                        Error de conexión. Verifique su conexión a internet.
                    </div>
                </div>
            `;
        }
    };
    
    request.open("POST", "../api/getClients.php", true);
    request.setRequestHeader("Content-type", "application/json");
    request.send(requestData);
}

// Función para seleccionar/deseleccionar todos
function seleccionarTodos() {
    const selectAll = document.getElementById("selectAll");
    if (!selectAll) return;
    
    const checkboxes = document.querySelectorAll('input[name="clients[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

// Función de seguridad para evitar XSS
function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Inicializar cuando el DOM esté listo
document.addEventListener("DOMContentLoaded", function() {
    // Si ya hay un origen seleccionado, cargar los clientes automáticamente
    const origenElement = document.getElementById("origen");
    if (origenElement && origenElement.value && origenElement.value !== "") {
        getClientsByOrigen();
    }
});
