/* ================================================================
 *  MÓDULO: Buscador de Clients (Clientes)
 *  Archivo: clients-search.js
 * ================================================================ */
(function () {
    'use strict';

    /* ---------- Configuración ---------- */
    const MIN_CHARS = 5;
    const ROOT_ID = 'clients-search-app';

    const COLUMNS = [
        { key: 'name',  label: 'Nombre', link: 'ci' },
        { key: 'ci',    label: 'CI'        },
        { key: 'city',  label: 'Municipio' },
        { key: 'state', label: 'Provincia' },
    ];

    /* ---------- Estado ---------- */
    const state = {
        rows: [],
        filter: '',
        sortBy: null,
        sortDir: 'asc',
        inputId: 'searchClientInput',
        resultsId: 'clientsResults',
        endpoint: '../api/searchClientInfo.php',
        label: 'Buscar clientes',
        placeholder: 'Buscar por nombre, CI, ciudad...',
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

    /* ---------- Render inicial: input + botón + contenedor ---------- */
    function renderScaffold() {
        const root = document.getElementById(ROOT_ID);
        if (!root) {
            console.error(`#${ROOT_ID} no encontrado en el DOM`);
            return;
        }

        root.innerHTML = `
            <div class="search-bar">
                <div class="search-group">
                    <label for="${state.inputId}">${esc(state.label)}</label>
                    <input type="text"
                           id="${state.inputId}"
                           placeholder="${esc(state.placeholder)}"
                           autocomplete="off" />
                </div>
                <button type="button" class="btn btn-secondary" id="clear-clients">
                    Limpiar
                </button>
            </div>
            <div id="${state.resultsId}">
                <div class="loading">Escriba al menos ${MIN_CHARS} caracteres para buscar clientes</div>
            </div>
        `;

        const inputEl = document.getElementById(state.inputId);

        inputEl.addEventListener('input', debounce(function () {
            window.ClientsSearch.search(inputEl.value);
        }, 300));

        document.getElementById('clear-clients')
            .addEventListener('click', () => clearSearch());
    }

    /* ---------- Limpiar ---------- */
    function clearSearch() {
        state.rows = [];
        state.filter = '';
        state.sortBy = null;
        state.sortDir = 'asc';

        const input = document.getElementById(state.inputId);
        if (input) {
            input.value = '';
            input.focus();
        }

        renderMessage(state.resultsId, 'loading',
            'Escriba al menos ' + MIN_CHARS + ' caracteres para buscar clientes');
    }

    /* ---------- Estados de mensaje ---------- */
    function renderMessage(containerId, type, message) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `<div class="${type}">${esc(message)}</div>`;
    }

    /* ---------- Filtro + Orden ---------- */
    function applyFilter(rows, query) {
        const q = query.trim().toLowerCase();
        if (!q) return rows;
        return rows.filter(row =>
            COLUMNS.some(col =>
                String(row[col.key] ?? '').toLowerCase().includes(q)
            )
        );
    }

    function applySort(rows) {
        if (!state.sortBy) return rows;
        const dir = state.sortDir === 'asc' ? 1 : -1;

        return [...rows].sort((a, b) => {
            const va = a[state.sortBy] ?? '';
            const vb = b[state.sortBy] ?? '';

            const na = Number(va), nb = Number(vb);
            if (!isNaN(na) && !isNaN(nb) && va !== '' && vb !== '') {
                return (na - nb) * dir;
            }
            const sa = String(va).toLowerCase();
            const sb = String(vb).toLowerCase();
            if (sa < sb) return -1 * dir;
            if (sa > sb) return  1 * dir;
            return 0;
        });
    }

    /* ---------- Render tabla ---------- */
    function renderTable() {
        const container = document.getElementById(state.resultsId);
        if (!container) return;

        const filtered = applyFilter(state.rows, state.filter);
        const sorted   = applySort(filtered);

        if (sorted.length === 0) {
            renderMessage(state.resultsId, 'empty',
                state.rows.length === 0
                    ? 'No hay clientes'
                    : 'No hay coincidencias con la búsqueda');
            return;
        }

        const thead = `
            <thead>
                <tr>
                    ${COLUMNS.map(col => {
                        const isSorted = state.sortBy === col.key;
                        const arrow = isSorted
                            ? (state.sortDir === 'asc' ? '▲' : '▼')
                            : '⇅';
                        const align = col.align ? `style="text-align:${col.align}"` : '';
                        return `
                            <th class="sortable ${isSorted ? 'sorted' : ''}"
                                data-col="${col.key}" ${align}>
                                ${esc(col.label)}
                                <span class="sort-arrow">${arrow}</span>
                            </th>
                        `;
                    }).join('')}
                </tr>
            </thead>
        `;

        const tbody = `
            <tbody>
                ${sorted.map(row => `
                    <tr>
                        ${COLUMNS.map(col => {
                            const value = row[col.key];
                            const align = col.align ? `style="text-align:${col.align}"` : '';
                            let cell;

                            if (col.link && row[col.link]) {
                                cell = `
                                    <a class="row-link"
                                       href="./details.php?ci=${encodeURIComponent(row[col.link])}">
                                        ${esc(value)}
                                    </a>
                                `;
                            } else {
                                cell = esc(value);
                            }
                            return `<td ${align}>${cell}</td>`;
                        }).join('')}
                    </tr>
                `).join('')}
            </tbody>
        `;

        container.innerHTML = `
            <div class="toolbar">
                <div class="count">
                    Mostrando ${sorted.length} de ${state.rows.length} clientes
                </div>
                <div></div>
            </div>
            <div class="table-wrap"><table>${thead}${tbody}</table></div>
        `;

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
    }

    /* ---------- Petición ---------- */
    async function postJSON(url, payload) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        if (!res.ok) throw new Error(`HTTP ${res.status}`);

        const text = await res.text();
        if (!text || text.trim() === '') {
            throw new Error('Respuesta vacía del servidor');
        }
        return JSON.parse(text);
    }

    /* ---------- Búsqueda ---------- */
    async function search(query) {
        const q = String(query ?? '').trim();

        if (q.length === 0) {
            renderMessage(state.resultsId, 'loading',
                'Escriba al menos ' + MIN_CHARS + ' caracteres para buscar clientes');
            return;
        }
        if (q.length < MIN_CHARS) {
            renderMessage(state.resultsId, 'warning',
                'Ingrese al menos ' + MIN_CHARS + ' caracteres para buscar');
            return;
        }

        renderMessage(state.resultsId, 'loading', 'Buscando clientes...');

        try {
            const data = await postJSON(state.endpoint, { search_param: q });
            const rows = Array.isArray(data) ? data : Object.values(data);

            state.rows = rows;
            state.filter = '';
            state.sortBy = null;
            state.sortDir = 'asc';

            if (rows.length === 0) {
                renderMessage(state.resultsId, 'empty', 'No hay clientes coincidentes');
                return;
            }
            renderTable();

        } catch (err) {
            console.error('Error al buscar clientes:', err);
            renderMessage(state.resultsId, 'error',
                'Error al cargar los clientes: ' + err.message);
        }
    }

    /* ================================================================
     *  API pública
     * ================================================================ */
    window.ClientsSearch = {
        search(value) {
            const input = document.getElementById(state.inputId);
            const q = value !== undefined ? value : (input ? input.value : '');
            return search(q);
        },
        clear() {
            clearSearch();
        },
    };

    /* ================================================================
     *  Init
     * ================================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        renderScaffold();
    });

})();