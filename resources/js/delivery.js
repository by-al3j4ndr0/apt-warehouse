/* ================================================================
 *  MÓDULO: Clientes por Origen
 *  - Mantiene el mismo estilo que la tabla de shipments
 *  - Filtros, ordenación, selección múltiple y búsqueda
 * ================================================================ */
(function () {
    'use strict';

    /* ---------- Configuración ---------- */
    const API_URL = '../api/getClients.php';
    const COLUMNS = ['name', 'count', 'city', 'state'];
    const COLUMN_LABELS = {
        name:  'Nombre',
        count: 'Envíos',
        city:  'Ciudad',
        state: 'Estado',
    };

    /* ---------- Estado ---------- */
    const state = {
        allClients: [],
        filters: { search: '' },
        sortBy: 'name',
        sortDir: 'asc',
    };

    /* ---------- Utilidades ---------- */
    function esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function debounce(fn, ms) {
        let t;
        return (...args) => {
            clearTimeout(t);
            t = setTimeout(() => fn(...args), ms);
        };
    }

    /* ---------- Filtrado + Ordenación ---------- */
    function getFilteredClients() {
        const q = state.filters.search.trim().toLowerCase();
        if (!q) return state.allClients;

        return state.allClients.filter(c =>
            String(c.name  ?? '').toLowerCase().includes(q) ||
            String(c.city  ?? '').toLowerCase().includes(q) ||
            String(c.state ?? '').toLowerCase().includes(q) ||
            String(c.ci    ?? '').toLowerCase().includes(q)
        );
    }

    function getSortedClients(rows) {
        if (!state.sortBy) return rows;
        const col = state.sortBy;
        const dir = state.sortDir === 'asc' ? 1 : -1;

        return [...rows].sort((a, b) => {
            // Ordenación numérica para 'count'
            if (col === 'count') {
                return (Number(a[col]) - Number(b[col])) * dir;
            }
            const va = String(a[col] ?? '').toLowerCase();
            const vb = String(b[col] ?? '').toLowerCase();
            if (va < vb) return -1 * dir;
            if (va > vb) return  1 * dir;
            return 0;
        });
    }

    /* ---------- Petición al backend ---------- */
    async function fetchClients(origenId, status, deliveryId) {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                origen_id: origenId,
                selected_status: status,
                delivery_id: deliveryId,
            }),
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }
        return res.json();
    }

    /* ---------- Render: estado vacío / error ---------- */
    function renderMessage(type, message) {
        const container = document.getElementById('clients-app');
        if (!container) return;
        container.innerHTML = `<div class="${type}">${esc(message)}</div>`;
    }

    /* ---------- Render principal ---------- */
    function render(clientsInfo) {
        const container = document.getElementById('clients-app');
        if (!container) return;

        // El backend puede devolver objeto o array → normalizamos
        const clients = Object.values(clientsInfo || {}).map(c => ({
            ci:    c.ci,
            name:  c.name,
            count: Number(c.count) || 0,
            city:  c.city,
            state: c.state,
            checked: !!c.checked,
        }));

        if (clients.length === 0) {
            renderMessage('empty', 'No hay clientes disponibles para este origen');
            return;
        }

        state.allClients = clients;

        container.innerHTML = `
            <div class="search-group">
                <label for="clients-search">Buscar</label>
                <input type="text" id="clients-search"
                       placeholder="Buscar por nombre, ciudad o estado..." />
            </div>
            <div class="toolbar">
                <div class="count" id="clients-count"></div>
                <div></div>
            </div>
            <div id="clients-table-container"></div>
        `;

        document.getElementById('clients-search')
            .addEventListener('input', debounce(e => {
                state.filters.search = e.target.value;
                renderTable();
            }, 200));

        renderTable();
    }

    /* ---------- Render tabla ---------- */
    function renderTable() {
        const filtered = getFilteredClients();
        const sorted   = getSortedClients(filtered);

        const container = document.getElementById('clients-table-container');
        const countEl   = document.getElementById('clients-count');

        countEl.textContent = filtered.length === 0
            ? 'Sin resultados'
            : `Mostrando ${sorted.length} de ${state.allClients.length} clientes`;

        // Cabecera
        const thead = `
            <thead>
                <tr>
                    <th class="checkbox-col">
                        <input type="checkbox" id="selectAll" />
                    </th>
                    ${COLUMNS.map(col => {
                        const isSorted = state.sortBy === col;
                        const arrow = isSorted
                            ? (state.sortDir === 'asc' ? '▲' : '▼')
                            : '⇅';
                        const align = col === 'count' ? 'style="text-align:center"' : '';
                        return `
                            <th class="sortable ${isSorted ? 'sorted' : ''}"
                                data-col="${col}" ${align}>
                                ${esc(COLUMN_LABELS[col])}
                                <span class="sort-arrow">${arrow}</span>
                            </th>
                        `;
                    }).join('')}
                </tr>
            </thead>
        `;

        // Cuerpo
        let tbody;
        if (sorted.length === 0) {
            tbody = `
                <tbody>
                    <tr class="no-rows">
                        <td colspan="${COLUMNS.length + 1}">No hay clientes que coincidan con la búsqueda.</td>
                    </tr>
                </tbody>
            `;
        } else {
            tbody = `
                <tbody>
                    ${sorted.map(client => `
                        <tr>
                            <td class="checkbox-col">
                                <input type="checkbox"
                                       class="client-checkbox"
                                       value="${esc(client.ci)}"
                                       name="clients[]"
                                       ${client.checked ? 'checked' : ''}>
                            </td>
                            <td>${esc(client.name)}</td>
                            <td style="text-align:center">${esc(client.count)}</td>
                            <td>${esc(client.city)}</td>
                            <td>${esc(client.state)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            `;
        }

        container.innerHTML = `<div class="table-wrap"><table>${thead}${tbody}</table></div>`;

        // Listener: ordenación
        container.querySelectorAll('th.sortable').forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.col;
                if (state.sortBy === col) {
                    state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    state.sortBy = col;
                    state.sortDir = 'asc';
                }
                renderTable();
            });
        });

        // Listener: seleccionar todos
        const selectAll = container.querySelector('#selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', () => {
                container.querySelectorAll('.client-checkbox').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
            });
        }

        // Sincronizar estado del "select all" cuando se cambia un checkbox individual
        container.querySelectorAll('.client-checkbox').forEach(cb => {
            cb.addEventListener('change', updateSelectAllState);
        });

        updateSelectAllState();
    }

    /* ---------- Actualizar estado del checkbox "select all" ---------- */
    function updateSelectAllState() {
        const container = document.getElementById('clients-table-container');
        if (!container) return;

        const selectAll = container.querySelector('#selectAll');
        const checkboxes = container.querySelectorAll('.client-checkbox');
        if (!selectAll || checkboxes.length === 0) return;

        const checked = container.querySelectorAll('.client-checkbox:checked').length;
        selectAll.checked       = checked === checkboxes.length;
        selectAll.indeterminate = checked > 0 && checked < checkboxes.length;
    }

    /* ================================================================
     *  API pública del módulo
     * ================================================================ */
    window.ClientsByOrigen = {
        /**
         * Carga clientes para el origen actualmente seleccionado
         */
        load() {
            const origenEl = document.getElementById('origen');
            const statusEl = document.querySelector('input[name="status"]:checked');
            const deliveryEl = document.getElementById('deliveryId');

            // Validar selección
            if (!origenEl || !origenEl.value) {
                renderMessage('empty', 'Seleccione un origen para ver los clientes');
                return;
            }

            const origenId   = origenEl.value;
            const status     = statusEl ? statusEl.value : '';
            const deliveryId = deliveryEl ? deliveryEl.value : '';

            renderMessage('loading', 'Cargando clientes...');

            fetchClients(origenId, status, deliveryId)
                .then(data => render(data))
                .catch(err => {
                    console.error('Error al cargar clientes:', err);
                    renderMessage('error', 'Error al cargar los clientes. Por favor, intente de nuevo.');
                });
        },

        /**
         * Devuelve los CIs de los clientes seleccionados
         */
        getSelected() {
            return Array.from(document.querySelectorAll('.client-checkbox:checked'))
                .map(cb => cb.value);
        },
    };

    /* ================================================================
     *  Inicialización
     * ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        const origenEl = document.getElementById('origen');
        if (origenEl) {
            origenEl.addEventListener('change', () => window.ClientsByOrigen.load());

            // Carga automática si ya hay un origen seleccionado
            if (origenEl.value && origenEl.value !== '') {
                window.ClientsByOrigen.load();
            }
        }
    });

})();

    /* ================================================================
     *  Buscador + Paginación de rutas
     *  - Filtro en cliente (instantáneo)
     *  - Paginación en cliente (oculta filas fuera de la página activa)
     * ================================================================ */
    (function () {
        'use strict';

        const INPUT_ID       = 'routes-search-input';
        const CLEAR_ID       = 'routes-clear';
        const TBODY_ID       = 'routes-table-body';
        const COUNT_ID       = 'routes-count';
        const PAGINATION_ID  = 'routes-pagination';

        /* ---------- Estado ---------- */
        const state = {
            allRows: [],       // todas las filas capturadas del DOM
            filter: '',
            page: 1,
            pageSize: 10,
        };

        /* ---------- Utilidades ---------- */
        function debounce(fn, ms) {
            let t;
            return (...args) => {
                clearTimeout(t);
                t = setTimeout(() => fn(...args), ms);
            };
        }

        /* ---------- Captura inicial de filas ---------- */
        function captureRows() {
            state.allRows = Array.from(document.querySelectorAll(`#${TBODY_ID} tr`))
                .filter(tr => !tr.classList.contains('no-rows'))
                .map(tr => ({
                    el:     tr,
                    id:     tr.dataset.id     || '',
                    name:   tr.dataset.name   || '',
                    status: tr.dataset.status || '',
                    origen: tr.dataset.origen || '',
                    driver: tr.dataset.driver || '',
                }));
        }

        /* ---------- Filtrado ---------- */
        function getFilteredRows() {
            const q = state.filter.trim().toLowerCase();
            if (!q) return state.allRows;

            return state.allRows.filter(row => {
                const haystack = [
                    row.id, row.name, row.status, row.origen, row.driver,
                ].join(' ').toLowerCase();
                return haystack.includes(q);
            });
        }

        /* ---------- Render principal (filtro + paginación) ---------- */
        function render() {
            const filtered = getFilteredRows();
            const total    = filtered.length;
            const pages    = Math.max(1, Math.ceil(total / state.pageSize));

            // Ajustar página si quedó fuera de rango
            if (state.page > pages) state.page = pages;
            if (state.page < 1)     state.page = 1;

            const start = (state.page - 1) * state.pageSize;
            const end   = start + state.pageSize;

            // Ocultar TODAS las filas primero
            state.allRows.forEach(row => { row.el.style.display = 'none'; });

            // Mostrar solo las visibles (filtradas + dentro de la página)
            filtered.slice(start, end).forEach(row => {
                row.el.style.display = '';
            });

            // Actualizar contador
            const countEl = document.getElementById(COUNT_ID);
            if (total === 0) {
                countEl.textContent = 'Sin resultados';
            } else if (total === state.allRows.length) {
                countEl.textContent = `Mostrando ${start + 1}–${Math.min(end, total)} de ${total} rutas`;
            } else {
                countEl.textContent =
                    `Mostrando ${start + 1}–${Math.min(end, total)} de ${total} rutas ` +
                    `(filtradas de ${state.allRows.length})`;
            }

            renderPagination(pages, total);
        }

        /* ---------- Paginación ---------- */
        function renderPagination(totalPages, totalRows) {
            const container = document.getElementById(PAGINATION_ID);
            if (!container) return;

            if (totalRows === 0 || totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            const { page, pageSize } = state;

            // Construir la lista de botones con elipsis
            const pages = [];
            const addPage = p => pages.push(p);
            const addEllipsis = () => pages.push('...');
            const maxButtons = 7;

            if (totalPages <= maxButtons) {
                for (let i = 1; i <= totalPages; i++) addPage(i);
            } else {
                addPage(1);
                const start = Math.max(2, page - 2);
                const end   = Math.min(totalPages - 1, page + 2);

                if (start > 2) addEllipsis();
                for (let i = start; i <= end; i++) addPage(i);
                if (end < totalPages - 1) addEllipsis();
                addPage(totalPages);
            }

            const buttonsHTML = pages.map(p => {
                if (p === '...') return `<button disabled>…</button>`;
                return `<button data-page="${p}" class="${p === page ? 'active' : ''}">${p}</button>`;
            }).join('');

            container.innerHTML = `
                <div class="pagination">
                    <button data-page="first" ${page === 1 ? 'disabled' : ''}>«</button>
                    <button data-page="prev"  ${page === 1 ? 'disabled' : ''}>‹</button>
                    ${buttonsHTML}
                    <button data-page="next"  ${page === totalPages ? 'disabled' : ''}>›</button>
                    <button data-page="last"  ${page === totalPages ? 'disabled' : ''}>»</button>
                    <select id="routes-page-size">
                        ${[5, 10, 25, 50, 100].map(n =>
                            `<option value="${n}" ${n === pageSize ? 'selected' : ''}>${n} / pág.</option>`
                        ).join('')}
                    </select>
                </div>
            `;

            // Listeners de botones
            container.querySelectorAll('button[data-page]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.dataset.page;
                    if (val === 'first')      state.page = 1;
                    else if (val === 'prev')  state.page = Math.max(1, state.page - 1);
                    else if (val === 'next')  state.page = Math.min(totalPages, state.page + 1);
                    else if (val === 'last')  state.page = totalPages;
                    else                      state.page = Number(val);
                    render();
                });
            });

            // Selector de tamaño de página
            const sizeSel = document.getElementById('routes-page-size');
            if (sizeSel) {
                sizeSel.addEventListener('change', e => {
                    state.pageSize = Number(e.target.value);
                    state.page = 1;
                    render();
                });
            }
        }

        /* ---------- Limpiar búsqueda ---------- */
        function clearFilter() {
            state.filter = '';
            state.page = 1;
            const input = document.getElementById(INPUT_ID);
            if (input) {
                input.value = '';
                input.focus();
            }
            render();
        }

        /* ---------- Init ---------- */
        document.addEventListener('DOMContentLoaded', function () {
            const input    = document.getElementById(INPUT_ID);
            const clearBtn = document.getElementById(CLEAR_ID);

            if (!input) return;

            captureRows();

            // Input de búsqueda (debounced, con e.target.value)
            input.addEventListener('input', debounce(function (e) {
                state.filter = e.target.value;
                state.page = 1;
                render();
            }, 200));

            // Botón limpiar
            if (clearBtn) {
                clearBtn.addEventListener('click', clearFilter);
            }

            // Render inicial
            render();
        });

    })();
