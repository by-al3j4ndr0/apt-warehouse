// Función de búsqueda en la tabla de los delivery
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
        for (j = 1; j < 2; j++) { // Empezar desde 1 para saltar columna del checkbox
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

// Función de búsqueda en la tabla de los clientes generales
function searchGClientsTable() {
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
        for (j = 0; j < 3; j++) { // Empezar desde 1 para saltar columna del checkbox
            if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = !found ? "none" : "";
        tr[i].style.display = found ? "" : "none";
    }
}

// Función de búsqueda en la tabla de los envios
function searchGShipmentsTable() {
    let input, filter, table, tr, td, i, j, txtValue, found;
    input = document.getElementById("search_input");
    
    if (!input) return;
    
    filter = input.value.toUpperCase();
    table = document.getElementById("shipmentsTable");
    
    if (!table) return;
    
    tr = table.getElementsByTagName("tr");
    
    // Recorrer todas las filas (empezando desde 1 para saltar cabecera)
    for (i = 1; i < tr.length; i++) {
        found = false;
        td = tr[i].getElementsByTagName("td");
        
        // Recorrer todas las columnas
        for (j = 0; j < 3; j++) { // Empezar desde 1 para saltar columna del checkbox
            if (td[j]) {
                txtValue = td[j].textContent || td[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = !found ? "none" : "";
        tr[i].style.display = found ? "" : "none";
    }
}

// Función de búsqueda en la tabla de las rutas
function searchDeliveryTable() {
    let input, filter, table, tr, td, i, j, txtValue, found;
    input = document.getElementById("search_input");
    
    if (!input) return;
    
    filter = input.value.toUpperCase();
    table = document.getElementById("table");
    
    if (!table) return;
    
    tr = table.getElementsByTagName("tr");
    
    // Recorrer todas las filas (empezando desde 1 para saltar cabecera)
    for (i = 1; i < tr.length; i++) {
        found = false;
        td = tr[i].getElementsByTagName("td");
        
        // Recorrer todas las columnas
        for (j = 0; j < 3; j++) { // Empezar desde 1 para saltar columna del checkbox
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
