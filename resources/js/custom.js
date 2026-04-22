// Configuración de la tabla
const tableConfig = {
    searchInput: null,
    table: null,
    currentSortColumn: -1,
    currentSortDir: 'asc'
};

// Inicializar funciones de tabla
function initTableFunctions() {
    tableConfig.searchInput = document.getElementById("search_input");
    tableConfig.table = document.getElementById("clientsTable");
    
    if (tableConfig.searchInput) {
        // Búsqueda en tiempo real
        tableConfig.searchInput.addEventListener('keyup', function() {
            searchTableImproved();
        });
    }
}

// Función de búsqueda mejorada con resaltado
function searchTableImproved() {
    if (!tableConfig.searchInput || !tableConfig.table) return;
    
    let filter = tableConfig.searchInput.value.toUpperCase().trim();
    let tr = tableConfig.table.getElementsByTagName("tr");
    
    // Si no hay filtro, mostrar todas
    if (filter === "") {
        for (let i = 1; i < tr.length; i++) {
            tr[i].style.display = "";
            tr[i].classList.remove('filtered-out');
        }
        return;
    }
    
    for (let i = 1; i < tr.length; i++) {
        let found = false;
        let td = tr[i].getElementsByTagName("td");
        
        // Saltar la columna del checkbox (índice 0 generalmente)
        for (let j = 1; j < td.length; j++) {
            if (td[j]) {
                let txtValue = getCleanText(td[j]);
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        
        tr[i].style.display = found ? "" : "none";
        if (!found) tr[i].classList.add('filtered-out');
        else tr[i].classList.remove('filtered-out');
    }
}

// Función de ordenamiento mejorada
function sortTableImproved(columnIndex) {
    if (!tableConfig.table) return;
    
    // Cambiar dirección si es la misma columna
    if (tableConfig.currentSortColumn === columnIndex) {
        tableConfig.currentSortDir = tableConfig.currentSortDir === 'asc' ? 'desc' : 'asc';
    } else {
        tableConfig.currentSortColumn = columnIndex;
        tableConfig.currentSortDir = 'asc';
    }
    
    let rows = Array.from(tableConfig.table.rows).slice(1); // Excluir cabecera
    
    // Ordenar filas
    rows.sort((rowA, rowB) => {
        let cellA = rowA.getElementsByTagName("td")[columnIndex];
        let cellB = rowB.getElementsByTagName("td")[columnIndex];
        
        let valueA = getSortValue(cellA, columnIndex);
        let valueB = getSortValue(cellB, columnIndex);
        
        let comparison = compareValues(valueA, valueB);
        return tableConfig.currentSortDir === 'asc' ? comparison : -comparison;
    });
    
    // Reinsertar filas ordenadas
    let tbody = tableConfig.table.tBodies[0];
    rows.forEach(row => tbody.appendChild(row));
    
    // Actualizar indicadores visuales
    updateSortIndicators(columnIndex);
}

// Obtener valor para ordenamiento según tipo de columna
function getSortValue(cell, columnIndex) {
    if (!cell) return "";
    
    // Columna 0: Checkbox
    if (columnIndex === 0) {
        const checkbox = cell.querySelector('input[type="checkbox"]');
        return checkbox ? (checkbox.checked ? 1 : 0) : 0;
    }
    
    // Columna 2: Envíos (número)
    if (columnIndex === 2) {
        const text = getCleanText(cell);
        const number = parseInt(text);
        return isNaN(number) ? 0 : number;
    }
    
    // Otras columnas: texto
    return getCleanText(cell);
}

// Obtener texto limpio de una celda
function getCleanText(element) {
    if (!element) return "";
    
    // Clonar y remover elementos no deseados
    const clone = element.cloneNode(true);
    const inputs = clone.querySelectorAll('input, button, .badge');
    inputs.forEach(input => input.remove());
    
    return (clone.textContent || clone.innerText || "").trim();
}

// Comparar valores genéricamente
function compareValues(a, b) {
    // Si ambos son números
    if (typeof a === 'number' && typeof b === 'number') {
        return a - b;
    }
    
    // Convertir a string para comparación
    const strA = String(a).toLowerCase();
    const strB = String(b).toLowerCase();
    
    return strA.localeCompare(strB, 'es', { numeric: true });
}

// Actualizar indicadores visuales de ordenamiento
function updateSortIndicators(sortedColumn) {
    // Remover indicadores existentes
    document.querySelectorAll('#clientsTable th .sort-indicator').forEach(ind => ind.remove());
    
    // Agregar indicador a la columna ordenada
    const headers = document.querySelectorAll('#clientsTable th');
    if (headers[sortedColumn]) {
        const indicator = document.createElement('span');
        indicator.className = 'sort-indicator ms-1';
        indicator.textContent = tableConfig.currentSortDir === 'asc' ? ' ▲' : ' ▼';
        headers[sortedColumn].appendChild(indicator);
    }
}

// Función para filtrar por checkbox seleccionados
function filterSelectedOnly() {
    const checkboxes = document.querySelectorAll('#clientsTable input[type="checkbox"]');
    const showOnlySelected = document.getElementById('showSelectedOnly')?.checked || false;
    
    const rows = document.querySelectorAll('#clientsTable tbody tr');
    
    rows.forEach((row, index) => {
        if (showOnlySelected) {
            const checkbox = row.querySelector('input[type="checkbox"]');
            row.style.display = checkbox && checkbox.checked ? "" : "none";
        } else {
            row.style.display = "";
        }
    });
}

// Event listeners para ordenamiento
function attachSortEvents() {
    const headers = document.querySelectorAll('#clientsTable th');
    headers.forEach((header, index) => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => sortTableImproved(index));
    });
}

// Inicializar todo
document.addEventListener('DOMContentLoaded', function() {
    initTableFunctions();
    attachSortEvents();
    
    // Opcional: Agregar checkbox para filtrar seleccionados
    const filterContainer = document.createElement('div');
    filterContainer.className = 'mb-3';
    filterContainer.innerHTML = `
        <label class="form-check-label">
            <input type="checkbox" id="showSelectedOnly" class="form-check-input">
            Mostrar solo seleccionados
        </label>
    `;
    
    const searchInput = document.getElementById('search_input');
    if (searchInput && searchInput.parentNode) {
        searchInput.parentNode.appendChild(filterContainer);
        document.getElementById('showSelectedOnly').addEventListener('change', filterSelectedOnly);
    }
});
