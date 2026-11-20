<?php
require_once __DIR__ . '/../api/auth.php';
requireLogin(true);
$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
    <title>Entrada y transferencia de mercancías</title>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <style>
        .io-wrap{max-width:1200px;margin:24px auto;padding:0 16px}
        .io-panel{background:#fff;border:1px solid #dee2e6;border-radius:12px;padding:20px;margin-bottom:18px}
        .io-scan{display:flex;gap:10px;flex-wrap:wrap}
        .io-scan input{flex:1;min-width:220px;font-size:1.2rem;padding:12px}
        .io-scan button{min-width:130px}
        .io-feedback{padding:12px 14px;border-radius:8px;margin-top:12px;background:#eef2f6}
        .io-feedback.success{background:#e5f5eb;color:#14532d}
        .io-feedback.warning{background:#fff4d6;color:#7c4a03}
        .io-feedback.error{background:#fde8e7;color:#842029}
        .io-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin:14px 0}
        .io-stat{border:1px solid #dee2e6;border-radius:10px;padding:14px;background:#f8fafc}
        .io-stat strong{display:block;font-size:1.7rem;line-height:1.2}
        .io-stat span{font-size:.88rem;color:#667085}
        .io-table{overflow-x:auto}
        .io-table table{width:100%;min-width:650px}
        .io-note{color:#667085;font-size:.92rem}
        .io-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0}
        .io-toolbar input{flex:1;min-width:230px}
        .io-pagination{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px}
        .io-pagination button:disabled{opacity:.45;cursor:not-allowed}
        @media(max-width:600px){.io-panel{padding:14px}.io-summary{grid-template-columns:1fr 1fr}.io-scan{display:grid}.io-scan button{width:100%}}
    </style>
</head>
<body>
    <?php include '../header.php'; ?>
    <main class="io-wrap">
        <div class="mb-3">
            <h2>Entrada y salida de mercancías</h2>
            <p class="text-muted mb-0">Control de recepción en almacén sincronizado con la aplicación Android.</p>
        </div>

        <section class="io-panel">
            <h4>Entrada a almacén</h4>
            <p class="io-note">Escanea los códigos de los envíos que se encuentran en estado <strong>at_customs</strong>. Al confirmar una entrada válida, el sistema cambia su estado a <strong>warehouse</strong>.</p>
            <div class="io-scan">
                <input id="shipmentCode" type="text" class="form-control" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Escanea o escribe el código HBL">
                <button id="registerEntry" type="button" class="btn btn-primary">Registrar entrada</button>
            </div>
            <div id="scanFeedback" class="io-feedback" role="status" aria-live="polite">Esperando escaneo…</div>
        </section>

        <section class="io-panel">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                <div>
                    <h4 class="mb-1">Control de recepción</h4>
                    <p class="io-note mb-0">Resumen general y envíos pendientes de entrada.</p>
                </div>
                <button id="refreshPending" type="button" class="btn btn-outline-secondary">Actualizar lista</button>
            </div>
            <div class="io-summary">
                <div class="io-stat"><strong id="pendingCount">—</strong><span>Envíos pendientes de escanear</span></div>
                <div class="io-stat"><strong id="weekScannedCount">—</strong><span>Entradas registradas en los últimos 7 días</span></div>
            </div>
            <div class="io-toolbar">
                <input id="pendingSearch" type="search" class="form-control" placeholder="Buscar por HBL, manifiesto, destinatario, municipio o provincia…" autocomplete="off">
                <span id="pendingSummary" class="text-muted small">Cargando envíos…</span>
            </div>
            <div class="io-table">
                <table class="table table-striped table-hover">
                    <thead><tr><th>Código HBL</th><th>Manifiesto</th><th>Destinatario</th><th>Municipio</th><th>Provincia</th></tr></thead>
                    <tbody id="pendingBody"><tr><td colspan="5" class="text-center">Cargando…</td></tr></tbody>
                </table>
            </div>
            <div class="io-pagination">
                <button id="previousPage" type="button" class="btn btn-outline-secondary" disabled>Anterior</button>
                <span id="pageInfo" class="small text-muted">Página 1 de 1</span>
                <button id="nextPage" type="button" class="btn btn-outline-secondary" disabled>Siguiente</button>
            </div>
        </section>

        <section class="io-panel">
            <h5>Transferencia entre almacenes</h5>
            <p class="io-note mb-0">El flujo de salida para transferencias a otro almacén queda reservado para una próxima etapa. La distribución de mercancías continúa gestionándose exclusivamente desde el módulo <strong>Deliveries</strong>.</p>
        </section>
    </main>
<script>
(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const pageSize = 25;
    let currentPage = 1, totalPages = 1, searchTimer = null, codeTimer = null, registering = false;

    function feedback(message, type = '') {
        const el = $('scanFeedback');
        el.className = 'io-feedback' + (type ? ' ' + type : '');
        el.textContent = message;
    }

    function renderShipments(shipments) {
        const body = $('pendingBody');
        body.replaceChildren();
        if (!shipments.length) {
            const tr = document.createElement('tr'), td = document.createElement('td');
            td.colSpan = 5; td.className = 'text-center text-muted';
            td.textContent = $('pendingSearch').value.trim()
                ? 'No se encontraron envíos que coincidan con la búsqueda.'
                : 'No hay mercancías pendientes de recepción.';
            tr.append(td); body.append(tr); return;
        }
        shipments.forEach(item => {
            const tr = document.createElement('tr');
            [item.code, item.manifest, item.recipient, item.municipality, item.province].forEach(value => {
                const td = document.createElement('td'); td.textContent = value || '—'; tr.append(td);
            });
            body.append(tr);
        });
    }

    async function loadPending(resetPage = false) {
        if (resetPage) currentPage = 1;
        $('pendingSummary').textContent = 'Actualizando…';
        $('refreshPending').disabled = true;
        try {
            const params = new URLSearchParams({
                page: String(currentPage),
                per_page: String(pageSize),
                search: $('pendingSearch').value.trim()
            });
            const response = await fetch('../api/getInOutShipments.php?' + params.toString(), {
                credentials: 'same-origin',
                headers: {'Accept':'application/json'}
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Error HTTP ' + response.status);

            currentPage = data.page;
            totalPages = data.total_pages;
            $('pendingCount').textContent = data.summary.pending.toLocaleString('es');
            $('weekScannedCount').textContent = data.summary.scanned_last_week.toLocaleString('es');
            $('pendingSummary').textContent = data.total.toLocaleString('es') + ' pendientes encontrados';
            $('pageInfo').textContent = 'Página ' + currentPage + ' de ' + totalPages + ' · ' + data.per_page + ' por página';
            $('previousPage').disabled = currentPage <= 1;
            $('nextPage').disabled = currentPage >= totalPages;
            renderShipments(data.shipments || []);
        } catch (error) {
            $('pendingSummary').textContent = 'No se pudo cargar la lista: ' + error.message;
        } finally {
            $('refreshPending').disabled = false;
        }
    }

    async function registerEntry(rawCode) {
        const code = String(rawCode || '').trim();
        if (!code || registering) return;
        registering = true;
        clearTimeout(codeTimer);
        $('registerEntry').disabled = true;
        feedback('Verificando ' + code + ' en la base de datos…');
        try {
            const eventId = (crypto.randomUUID ? crypto.randomUUID() : ('evt-' + Date.now() + '-' + Math.random().toString(36).slice(2))).replace(/[^A-Za-z0-9-]/g, '-');
            const response = await fetch('../api/scanner/sync.php', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-Token':csrf},
                body: JSON.stringify({records:[{
                    id:eventId, manifest:'Entrada desde inOutShipments', code, status:'FOUND',
                    recipient:'', municipality:'', province:'', timestamp:new Date().toISOString()
                }]})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Error HTTP ' + response.status);
            const result = data.results && data.results[eventId];
            if (result === 'FOUND') feedback('✓ Entrada registrada. ' + code + ' cambió a estado warehouse.', 'success');
            else if (result === 'DUPLICATE') feedback('⚠ ' + code + ' ya se encontraba en warehouse. No se modificó nuevamente.', 'warning');
            else feedback('✕ ' + code + ' no existe o no está pendiente de entrada (at_customs).', 'error');
            $('shipmentCode').value = '';
            await loadPending();
        } catch (error) {
            feedback('No se pudo registrar la entrada: ' + error.message, 'error');
        } finally {
            registering = false;
            $('registerEntry').disabled = false;
            $('shipmentCode').focus();
        }
    }

    $('registerEntry').addEventListener('click', () => registerEntry($('shipmentCode').value));
    $('shipmentCode').addEventListener('keyup', event => {
        clearTimeout(codeTimer);
        if (event.key === 'Enter') {
            event.preventDefault();
            registerEntry($('shipmentCode').value);
            return;
        }
        // HID scanners type rapidly; submit automatically after the input goes idle.
        codeTimer = setTimeout(() => {
            if ($('shipmentCode').value.trim()) registerEntry($('shipmentCode').value);
        }, 400);
    });
    $('pendingSearch').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => loadPending(true), 300);
    });
    $('pendingSearch').addEventListener('search', () => loadPending(true));
    $('previousPage').addEventListener('click', () => { if (currentPage > 1) { currentPage--; loadPending(); } });
    $('nextPage').addEventListener('click', () => { if (currentPage < totalPages) { currentPage++; loadPending(); } });
    $('refreshPending').addEventListener('click', () => loadPending());
    loadPending();
    $('shipmentCode').focus();
})();
</script>
</body>
</html>
