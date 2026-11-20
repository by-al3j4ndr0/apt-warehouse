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
        .io-summary{font-size:1rem;color:#495057}
        .io-table{overflow-x:auto}
        .io-table table{width:100%;min-width:650px}
        .io-note{color:#667085;font-size:.92rem}
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
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h4 class="mb-1">Pendientes de recepción</h4>
                    <div id="pendingSummary" class="io-summary">Cargando manifiestos…</div>
                </div>
                <button id="refreshPending" type="button" class="btn btn-outline-secondary">Actualizar lista</button>
            </div>
            <div class="io-table">
                <table class="table table-striped table-hover">
                    <thead><tr><th>Código HBL</th><th>Manifiesto</th><th>Destinatario</th><th>Municipio</th><th>Provincia</th></tr></thead>
                    <tbody id="pendingBody"><tr><td colspan="5" class="text-center">Cargando…</td></tr></tbody>
                </table>
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
    let pending = [];

    function feedback(message, type = '') {
        const el = $('scanFeedback');
        el.className = 'io-feedback' + (type ? ' ' + type : '');
        el.textContent = message;
    }

    async function loadPending() {
        $('pendingSummary').textContent = 'Actualizando…';
        try {
            const response = await fetch('../api/getInOutShipments.php', {credentials:'same-origin', headers:{'Accept':'application/json'}});
            const data = await response.json();
            if (!response.ok) throw new Error(data.error || 'Error HTTP ' + response.status);
            pending = data.shipments || [];
            $('pendingSummary').textContent = pending.length + ' envíos pendientes de entrada';
            const body = $('pendingBody');
            body.replaceChildren();
            if (!pending.length) {
                const tr = document.createElement('tr'), td = document.createElement('td');
                td.colSpan = 5; td.className = 'text-center text-muted'; td.textContent = 'No hay mercancías pendientes de recepción.';
                tr.append(td); body.append(tr); return;
            }
            pending.forEach(item => {
                const tr = document.createElement('tr');
                [item.code, item.manifest, item.recipient, item.municipality, item.province].forEach(value => {
                    const td = document.createElement('td'); td.textContent = value || '—'; tr.append(td);
                });
                body.append(tr);
            });
        } catch (error) {
            $('pendingSummary').textContent = 'No se pudo cargar la lista: ' + error.message;
        }
    }

    async function registerEntry(rawCode) {
        const code = String(rawCode || '').trim();
        if (!code) return;
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
            $('registerEntry').disabled = false;
            $('shipmentCode').focus();
        }
    }

    $('registerEntry').addEventListener('click', () => registerEntry($('shipmentCode').value));
    $('shipmentCode').addEventListener('keydown', event => {
        if (event.key === 'Enter') { event.preventDefault(); registerEntry($('shipmentCode').value); }
    });
    $('refreshPending').addEventListener('click', loadPending);
    loadPending();
    $('shipmentCode').focus();
})();
</script>
</body>
</html>