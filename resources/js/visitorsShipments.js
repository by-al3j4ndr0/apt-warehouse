/* ================================================================
 *  CONFIGURACIÓN
 * ================================================================ */
const API_URL = '../api/getVisitorsShipments.php';
const IS_STAFF = 'false';
const COLUMNS = ['origen', 'hbl', 'name', 'city', 'state', 'route_id', 'status'];
const COLUMN_LABELS = {
  origen: 'Origen',
  hbl: 'HBL',
  name: 'Nombre',
  city: 'Municipio',
  state: 'Provincia',
  route_id: 'Route ID',
  status: 'Estado',
};

/* ================================================================
 *  ESTADO GLOBAL
 * ================================================================ */
const state = {
  allRows: [],           // filas crudas del backend
  filters: {
    status: '',             // filtros activos
    city: '',
    state: '',
    search: '',
  },
  sortBy: null,          // columna actual de orden
  sortDir: 'asc',        // 'asc' | 'desc'
  page: 1,
  pageSize: 10,
};

/* ================================================================
 *  UTILIDADES
 * ================================================================ */
function esc(value) {
  if (value === null || value === undefined) return '';
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/** Debounce simple para el input de búsqueda */
function debounce(fn, ms) {
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

/* ================================================================
 *  FILTRADO + ORDENACIÓN
 * ================================================================ */
function getFilteredRows() {
  const { status, city, state: st, search } = state.filters;
  const q = search.trim().toLowerCase();

  return state.allRows.filter(row => {
    if (status && row.status !== status) return false;
    if (city   && row.city   !== city)   return false;
    if (st     && row.state  !== st)     return false;

    if (q) {
      const hit = COLUMNS.some(col =>
        String(row[col] ?? '').toLowerCase().includes(q)
      );
      if (!hit) return false;
    }
    return true;
  });
}

function getSortedRows(rows) {
  if (!state.sortBy) return rows;

  const col = state.sortBy;
  const dir = state.sortDir === 'asc' ? 1 : -1;

  return [...rows].sort((a, b) => {
    const va = String(a[col] ?? '').toLowerCase();
    const vb = String(b[col] ?? '').toLowerCase();
    if (va < vb) return -1 * dir;
    if (va > vb) return  1 * dir;
    return 0;
  });
}

function getPaginatedRows(rows) {
  const start = (state.page - 1) * state.pageSize;
  return rows.slice(start, start + state.pageSize);
}

/* ================================================================
 *  PETICIÓN AL BACKEND
 * ================================================================ */
async function fetchShipments() {
  const res = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ is_staff: IS_STAFF }),
  });

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.error || `HTTP ${res.status}`);
  }
  return res.json();
}

/* ================================================================
 *  RENDER PRINCIPAL
 * ================================================================ */
function render(data) {
  const app = document.getElementById('app');
  const { header_data } = data;

  app.innerHTML = `
    <div class="filters">
      <div class="filter-group">
        <label for="f-search">Buscar</label>
        <input type="text" id="f-search" placeholder="Buscar en todas las columnas..." />
      </div>
      <div class="filter-group">
        <label for="f-status">Estado</label>
        <select id="f-status">
          <option value="">Todas</option>
          ${header_data.status.map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join('')}
        </select>
      </div>
      <div class="filter-group">
        <label for="f-city">Municipio</label>
        <select id="f-city">
          <option value="">Todas</option>
          ${header_data.city.map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join('')}
        </select>
      </div>
      <div class="filter-group">
        <label for="f-state">Provincia</label>
        <select id="f-state">
          <option value="">Todos</option>
          ${header_data.state.map(v => `<option value="${esc(v)}">${esc(v)}</option>`).join('')}
        </select>
      </div>
      <div class="filter-group">
        <label>&nbsp;</label>
        <button id="clear-filters" class="btn btn-secondary" type="button">Limpiar</button>
      </div>
    </div>

    <div class="toolbar">
      <div class="count" id="count"></div>
      <button id="export-csv" class="btn btn-success" type="button">
        ⬇ Exportar CSV
      </button>
    </div>

    <div id="table-container"></div>
    <div id="pagination-container"></div>
  `;

  // Listeners de filtros
  document.getElementById('f-search').addEventListener('input', debounce(e => {
    state.filters.search = e.target.value;
    state.page = 1;
    renderTable();
  }, 200));

  document.getElementById('f-status').addEventListener('change', e => {
    state.filters.status = e.target.value;
    state.page = 1;
    renderTable();
  });
  
  document.getElementById('f-city').addEventListener('change', e => {
    state.filters.city = e.target.value;
    state.page = 1;
    renderTable();
  });

  document.getElementById('f-state').addEventListener('change', e => {
    state.filters.state = e.target.value;
    state.page = 1;
    renderTable();
  });

  document.getElementById('clear-filters').addEventListener('click', () => {
    state.filters = { status: '', city: '', state: '', search: '' };
    state.page = 1;
    document.getElementById('f-search').value = '';
    document.getElementById('f-status').value   = '';
    document.getElementById('f-city').value   = '';
    document.getElementById('f-state').value  = '';
    renderTable();
  });

  document.getElementById('export-csv').addEventListener('click', exportCSV);

  // Aplicar filtros desde URL (si existen)
  applyFiltersFromURL();

  renderTable();
}

/* ================================================================
 *  RENDER DE LA TABLA + PAGINACIÓN
 * ================================================================ */
function renderTable() {
  const filtered  = getFilteredRows();
  const sorted    = getSortedRows(filtered);
  const totalRows = sorted.length;
  const totalPages = Math.max(1, Math.ceil(totalRows / state.pageSize));

  // Ajustar página si quedó fuera de rango
  if (state.page > totalPages) state.page = totalPages;

  const pageRows = getPaginatedRows(sorted);

  const container = document.getElementById('table-container');
  const countEl   = document.getElementById('count');

  countEl.textContent = totalRows === 0
    ? 'Sin resultados'
    : `Mostrando ${pageRows.length} de ${totalRows} envíos (${state.allRows.length} totales)`;

  // --- Cabecera ---
  const thead = `
    <thead>
      <tr>
        ${COLUMNS.map(col => {
          const isSorted = state.sortBy === col;
          const arrow = isSorted ? (state.sortDir === 'asc' ? '▲' : '▼') : '⇅';
          return `
            <th class="sortable ${isSorted ? 'sorted' : ''}" data-col="${col}">
              ${esc(COLUMN_LABELS[col] || col)}
              <span class="sort-arrow">${arrow}</span>
            </th>
          `;
        }).join('')}
      </tr>
    </thead>
  `;

  // --- Cuerpo ---
  let tbody;
  if (pageRows.length === 0) {
    tbody = `
      <tbody>
        <tr class="no-rows">
          <td colspan="${COLUMNS.length}">No hay envíos que coincidan con los filtros.</td>
        </tr>
      </tbody>
    `;
  } else {
    tbody = `
      <tbody>
        ${pageRows.map(row => `
          <tr>
            ${COLUMNS.map(col => `<td>${esc(row[col])}</td>`).join('')}
          </tr>
        `).join('')}
      </tbody>
    `;
  }

  container.innerHTML = `<div class="table-wrap"><table>${thead}${tbody}</table></div>`;

  // Listeners de ordenación
  container.querySelectorAll('th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      const col = th.dataset.col;
      if (state.sortBy === col) {
        state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
      } else {
        state.sortBy = col;
        state.sortDir = 'asc';
      }
      state.page = 1;
      renderTable();
    });
  });

  renderPagination(totalPages);
  saveFiltersToURL();
}

/* ================================================================
 *  PAGINACIÓN
 * ================================================================ */
function renderPagination(totalPages) {
  const container = document.getElementById('pagination-container');
  if (totalPages <= 1) { container.innerHTML = ''; return; }

  const { page, pageSize } = state;

  // Rango de botones con elipsis (máx 7 visibles)
  const pages = [];
  const addPage = p => pages.push(p);
  const addEllipsis = () => pages.push('...');

  const maxButtons = 7;
  if (totalPages <= maxButtons) {
    for (let i = 1; i <= totalPages; i++) addPage(i);
  } else {
    addPage(1);
    let start = Math.max(2, page - 2);
    let end   = Math.min(totalPages - 1, page + 2);

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
      <select id="page-size">
        ${[5, 10, 25, 50, 100].map(n =>
          `<option value="${n}" ${n === pageSize ? 'selected' : ''}>${n} / pág.</option>`
        ).join('')}
      </select>
    </div>
  `;

  container.querySelectorAll('button[data-page]').forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.dataset.page;
      if (val === 'first')      state.page = 1;
      else if (val === 'prev')  state.page = Math.max(1, state.page - 1);
      else if (val === 'next')  state.page = Math.min(totalPages, state.page + 1);
      else if (val === 'last')  state.page = totalPages;
      else                      state.page = Number(val);
      renderTable();
    });
  });

  document.getElementById('page-size').addEventListener('change', e => {
    state.pageSize = Number(e.target.value);
    state.page = 1;
    renderTable();
  });
}

/* ================================================================
 *  EXPORTAR A CSV
 *  - Exporta TODAS las filas filtradas (no solo la página actual)
 * ================================================================ */
function exportCSV() {
  const rows = getSortedRows(getFilteredRows());

  if (rows.length === 0) {
    alert('No hay datos para exportar.');
    return;
  }

  // Encabezados legibles
  const headers = COLUMNS.map(c => COLUMN_LABELS[c] || c);

  // Escapar valores para CSV (comillas dobles + comillas duplicadas)
  const escapeCSV = value => {
    const s = value === null || value === undefined ? '' : String(value);
    return `"${s.replace(/"/g, '""')}"`;
  };

  const lines = [
    headers.map(escapeCSV).join(','),
    ...rows.map(row => COLUMNS.map(c => escapeCSV(row[c])).join(',')),
  ];

  // BOM para que Excel respete UTF-8
  const csv = '\uFEFF' + lines.join('\r\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url  = URL.createObjectURL(blob);

  const ts = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
  const link = document.createElement('a');
  link.href = url;
  link.download = `shipments_${ts}.csv`;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

/* ================================================================
 *  PERSISTIR FILTROS EN LA URL
 * ================================================================ */
function saveFiltersToURL() {
  const params = new URLSearchParams();
  if (state.filters.origen) params.set('origen', state.filters.origen);
  if (state.filters.city)   params.set('city',   state.filters.city);
  if (state.filters.state)  params.set('state',  state.filters.state);
  if (state.filters.search) params.set('q',      state.filters.search);
  if (state.sortBy)         params.set('sort',   state.sortBy);
  if (state.sortDir !== 'asc') params.set('dir', state.sortDir);
  if (state.page > 1)       params.set('page',   state.page);

  const qs = params.toString();
  const url = qs ? `${location.pathname}?${qs}` : location.pathname;
  history.replaceState({}, '', url);
}

function applyFiltersFromURL() {
  const p = new URLSearchParams(location.search);

  state.filters.origen = p.get('origen') || '';
  state.filters.city   = p.get('city')   || '';
  state.filters.state  = p.get('state')  || '';
  state.filters.search = p.get('q')      || '';
  state.sortBy         = p.get('sort')   || null;
  state.sortDir        = p.get('dir')    || 'asc';
  state.page           = Number(p.get('page')) || 1;

  // Reflejar en los inputs
  const setVal = (id, v) => { const el = document.getElementById(id); if (el) el.value = v; };
  setVal('f-search', state.filters.search);
  setVal('f-origen', state.filters.origen);
  setVal('f-city',   state.filters.city);
  setVal('f-state',  state.filters.state);
}

/* ================================================================
 *  INIT
 * ================================================================ */
(async function init() {
  const app = document.getElementById('app');
  try {
    const data = await fetchShipments();
    state.allRows = data.row_data || [];
    render(data);
  } catch (err) {
    console.error(err);
    app.innerHTML = `<div class="error">Error: ${esc(err.message)}</div>`;
  }
})();