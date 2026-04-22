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
    
    // Validar que el valor no esté vacío
    if (!origen) {
        const container = document.getElementById("tableDiv");
        if (container) {
            container.innerHTML = `
                <div class="form-control">
                    <div class="alert alert-warning text-center">
                        Por favor, seleccione un origen válido
                    </div>
                </div>
            `;
        }
        return;
    }
    
    // Enviar como JSON o como form data
    const requestData = JSON.stringify({ origen_id: origen });
    
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
                                    value="${escapeHtml(client.ci)}" name="clients[]">
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

// Función de búsqueda en la tabla
function searchTable() {
    let input, filter, table, tr, td, i, j, txtValue, found;
    input = document.getElementById("search_input");
    
    if (!input) return;
    
    filter = input.value.toUpperCase();
    table = document.getElementById("clientsTable");
    
    if (!table) return;
    
    tr = table.getElementsByTagName("tr");
    
    // Recorrer todas las filas (empezando desde 1 para saltar cabecera)
    for (i = 1; i < tr.length; i++) {
        found = false;
        td = tr[i].getElementsByTagName("td");
        
        // Recorrer todas las columnas
        for (j = 1; j < td.length; j++) { // Empezar desde 1 para saltar columna del checkbox
            if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? "" : "none";
    }
}

// Función para ordenar la tabla
function sortTableImproved(columnIndex) {
    const table = document.getElementById("clientsTable");
    if (!table) return;
    
    let switching = true;
    let dir = "asc";
    let switchcount = 0;
    const tbody = table.getElementsByTagName("tbody")[0];
    
    if (!tbody) return;
    
    const rows = Array.from(tbody.getElementsByTagName("tr"));
    
    // Ordenar las filas
    rows.sort((rowA, rowB) => {
        let cellA = rowA.getElementsByTagName("td")[columnIndex];
        let cellB = rowB.getElementsByTagName("td")[columnIndex];
        
        if (!cellA || !cellB) return 0;
        
        let valueA = cellA.textContent || cellA.innerText;
        let valueB = cellB.textContent || cellB.innerText;
        
        // Para la columna de envíos (índice 2), convertir a número
        if (columnIndex === 2) {
            valueA = parseInt(valueA) || 0;
            valueB = parseInt(valueB) || 0;
        }
        
        if (dir === "asc") {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });
    
    // Reinsertar las filas ordenadas
    rows.forEach(row => tbody.appendChild(row));
    
    // Cambiar la dirección para la próxima vez
    dir = dir === "asc" ? "desc" : "asc";
}

// Función para adjuntar evento de búsqueda
function attachSearchEvent() {
    const searchInput = document.getElementById("search_input");
    if (searchInput) {
        // Remover evento anterior si existe
        const newSearchInput = searchInput.cloneNode(true);
        searchInput.parentNode.replaceChild(newSearchInput, searchInput);
        newSearchInput.addEventListener("keyup", searchTable);
    }
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
