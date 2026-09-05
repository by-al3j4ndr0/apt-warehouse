// Esperar a que el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", function () {
    // Verificar que el elemento origen existe antes de asignar el evento
    const searchShipmentInputElement = document.getElementById("searchShipmentInput");
    const searchClientInputElement = document.getElementById("searchClientInput");
    if (searchShipmentInputElement) {
        // Cambiar onkeyup por input para mejor experiencia de usuario
        searchShipmentInputElement.addEventListener("input", debounce(function() { 
            getShipmentsInfo(this.value); 
        }, 300));
    } else if (searchClientInputElement) {
        // Cambiar onkeyup por input para mejor experiencia de usuario
        searchClientInputElement.addEventListener("input", debounce(function() { 
            getClientInfo(this.value); 
        }, 300));
    }
});

function getShipmentsInfo(searchValue) {
    // CORRECCIÓN 1: Obtener el valor del input correctamente
    let searchInputField = document.getElementById("searchShipmentInput");
    let searchParam = searchValue !== undefined ? searchValue : searchInputField.value;
    
    // CORRECCIÓN 2: Verificar longitud correctamente
    if (searchParam.length >= 5) {
        const requestData = JSON.stringify({ search_param: searchParam });

        const request = new XMLHttpRequest();
        request.onload = function() {
            try {
                // Verificar el estado de la respuesta
                if (request.status !== 200) {
                    throw new Error("Error en la petición: " + request.status);
                }
                
                // CORRECCIÓN 3: Verificar que la respuesta no esté vacía
                if (!this.responseText || this.responseText.trim() === '') {
                    throw new Error("Respuesta vacía del servidor");
                }
                
                const clientsInfo = JSON.parse(this.responseText);
                const container = document.getElementById("clientsSearchTable");
                
                if (!container) {
                    console.error("Elemento Table no encontrado");
                    return;
                }
                
                // CORRECCIÓN 4: Asegurar que clientsInfo sea un array
                const clientsArray = Array.isArray(clientsInfo) ? clientsInfo : Object.values(clientsInfo);
                
                if (clientsArray && clientsArray.length > 0) {
                    // Construir tabla completa
                    let html = `
                        <div class="form-control">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="clientsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th onclick="sortTableImproved(1)" style="cursor: pointer;">Agencia</th>
                                            <th onclick="sortTableImproved(2)" style="cursor: pointer;">HBL</th>
                                            <th onclick="sortTableImproved(3)" style="cursor: pointer;">Nombre</th>
                                            <th onclick="sortTableImproved(4)" style="cursor: pointer;">Ruta</th>
                                            <th onclick="sortTableImproved(5)" style="cursor: pointer;">Estado</th>
                                            <th onclick="sortTableImproved(6)" style="cursor: pointer;">Manifiesto</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientsTableBody">
                    `;
                    
                    // Agregar filas - CORRECCIÓN 5: Verificar que los campos existan
                    clientsArray.forEach(client => {
                        html += `
                            <tr>
                                <td>${escapeHtml(client.origen)}</td>
                                <td>${escapeHtml(client.hbl)}</td>
                                <td>${escapeHtml(client.name)}</td>
                                <td class="text-center">${escapeHtml(client.route_id)}</td>
                                <td>${escapeHtml(client.status)}</td>
                                <td class="text-center">${escapeHtml(client.manifest)}</td>
                            </a>
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
                    if (typeof attachSearchEvent === 'function') {
                        attachSearchEvent();
                    }
                } else {
                    container.innerHTML = `
                        <div class="form-control">
                            <div class="alert alert-info text-center">
                                No hay clientes coincidentes
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error("Error al procesar la respuesta:", error);
                const container = document.getElementById("clientsSearchTable");
                if (container) {
                    container.innerHTML = `
                        <div class="form-control">
                            <div class="alert alert-danger text-center">
                                Error al cargar los clientes: ${error.message}
                            </div>
                        </div>
                    `;
                }
            }
        };

        request.onerror = function() {
            console.error("Error de red en la petición");
            const container = document.getElementById("clientsSearchTable");
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
        
        request.open("POST", "../api/searchShipmentsInfo.php", true);
        request.setRequestHeader("Content-type", "application/json");
        request.send(requestData);
    } else if (searchParam.length > 0 && searchParam.length < 5) {
        // CORRECCIÓN 6: Mensaje para búsquedas cortas
        const container = document.getElementById("clientsSearchTable");
        if (container) {
            container.innerHTML = `
                <div class="form-control">
                    <div class="alert alert-warning text-center">
                        Ingrese al menos 5 caracteres para buscar
                    </div>
                </div>
            `;
        }
    }
}

function getClientInfo(searchValue) {
    // CORRECCIÓN 1: Obtener el valor del input correctamente
    let searchInputField = document.getElementById("searchClientInput");
    let searchParam = searchValue !== undefined ? searchValue : searchInputField.value;
    
    // CORRECCIÓN 2: Verificar longitud correctamente
    if (searchParam.length >= 5) {
        const requestData = JSON.stringify({ search_param: searchParam });

        const request = new XMLHttpRequest();
        request.onload = function() {
            try {
                // Verificar el estado de la respuesta
                if (request.status !== 200) {
                    throw new Error("Error en la petición: " + request.status);
                }
                
                // CORRECCIÓN 3: Verificar que la respuesta no esté vacía
                if (!this.responseText || this.responseText.trim() === '') {
                    throw new Error("Respuesta vacía del servidor");
                }
                
                const clientsInfo = JSON.parse(this.responseText);
                const container = document.getElementById("clientsSearchTable");
                
                if (!container) {
                    console.error("Elemento Table no encontrado");
                    return;
                }
                
                // CORRECCIÓN 4: Asegurar que clientsInfo sea un array
                const clientsArray = Array.isArray(clientsInfo) ? clientsInfo : Object.values(clientsInfo);
                
                if (clientsArray && clientsArray.length > 0) {
                    // Construir tabla completa
                    let html = `
                        <div class="form-control">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover" id="clientsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th onclick="sortTableImproved(1)" style="cursor: pointer;">Nombre</th>
                                            <th onclick="sortTableImproved(2)" style="cursor: pointer;">CI</th>
                                            <th onclick="sortTableImproved(3)" style="cursor: pointer;">Municipio</th>
                                            <th onclick="sortTableImproved(4)" style="cursor: pointer;">Provincia</th>
                                        </tr>
                                    </thead>
                                    <tbody id="clientsTableBody">
                    `;
                    
                    // Agregar filas - CORRECCIÓN 5: Verificar que los campos existan
                    clientsArray.forEach(client => {
                        html += `
                            <tr>
                                <td>
                                    <a class="link-dark link-underline link-underline-opacity-0" href="./details.php?ci=${escapeHtml(client.ci)}">
                                        ${escapeHtml(client.name)}
                                    </a>
                                </td>
                                <td>${escapeHtml(client.ci)}</td>
                                <td>${escapeHtml(client.city)}</td>
                                <td>${escapeHtml(client.state)}</td>
                            </a>
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
                    if (typeof attachSearchEvent === 'function') {
                        attachSearchEvent();
                    }
                } else {
                    container.innerHTML = `
                        <div class="form-control">
                            <div class="alert alert-info text-center">
                                No hay clientes coincidentes
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error("Error al procesar la respuesta:", error);
                const container = document.getElementById("clientsSearchTable");
                if (container) {
                    container.innerHTML = `
                        <div class="form-control">
                            <div class="alert alert-danger text-center">
                                Error al cargar los clientes: ${error.message}
                            </div>
                        </div>
                    `;
                }
            }
        };

        request.onerror = function() {
            console.error("Error de red en la petición");
            const container = document.getElementById("clientsSearchTable");
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
        
        request.open("POST", "../api/searchClientInfo.php", true);
        request.setRequestHeader("Content-type", "application/json");
        request.send(requestData);
    } else if (searchParam.length > 0 && searchParam.length < 5) {
        // CORRECCIÓN 6: Mensaje para búsquedas cortas
        const container = document.getElementById("clientsSearchTable");
        if (container) {
            container.innerHTML = `
                <div class="form-control">
                    <div class="alert alert-warning text-center">
                        Ingrese al menos 5 caracteres para buscar
                    </div>
                </div>
            `;
        }
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

// Agregar esta función
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}