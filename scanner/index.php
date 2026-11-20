<?php
require_once __DIR__ . '/../api/auth.php';
requireLogin(true);
$csrf = csrfToken();
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#123b5d"><meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<title>Control QR | APT Warehouse</title><link rel="manifest" href="./manifest.webmanifest"><link rel="stylesheet" href="./style.css">
</head><body>
<header class="topbar"><div><strong>APT Warehouse</strong><span>Control de mercancías</span></div><span id="connection" class="pill">Comprobando conexión…</span></header>
<main>
<section class="panel"><div class="section-head"><div><h1>Verificación QR</h1><p>Importa el listado CSV y comienza a escanear.</p></div></div>
<label class="field-label" for="manifestName">Nombre de la sesión / manifiesto</label><input id="manifestName" type="text" placeholder="Ej. Manifiesto 20-11-2026" maxlength="120">
<label class="file-picker" for="csvFile"><span>＋</span><span><b>Seleccionar archivo CSV</b><small id="fileName">Código, destinatario, municipio y provincia</small></span></label><input id="csvFile" type="file" accept=".csv,text/csv" hidden>
<div class="manifest-status" id="manifestStatus">No hay ningún listado cargado.</div></section>
<section class="panel scanner-panel"><div class="section-head"><div><h2>Escanear mercancía</h2><p>Conecta el lector Bluetooth en modo teclado.</p></div><span class="scan-icon">▦</span></div>
<label class="field-label" for="scanInput">Código QR</label><div class="scan-row"><input id="scanInput" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Esperando escaneo…" disabled><button id="scanButton" type="button" disabled>Verificar</button></div>
<div id="result" class="result neutral" role="status" aria-live="polite">Carga un CSV para habilitar el escaneo.</div><div id="shipmentInfo" class="shipment-info" hidden></div>
<p class="hint">El lector debe enviar Enter al finalizar cada lectura. También puedes escribir un código y pulsar Verificar.</p></section>
<section class="stats-grid" aria-label="Resumen">
<div class="stat"><b id="countFound">0</b><span>Encontrados</span></div><div class="stat"><b id="countMissing">0</b><span>No encontrados</span></div><div class="stat"><b id="countDuplicate">0</b><span>Duplicados</span></div><div class="stat"><b id="countPending">0</b><span>Pendientes</span></div></section>
<section class="panel"><div class="section-head"><div><h2>Últimos registros</h2><p id="recordCount">0 escaneos registrados</p></div><button id="exportButton" class="secondary" type="button">Exportar CSV</button></div>
<div class="table-wrap"><table><thead><tr><th>Hora</th><th>Código</th><th>Destinatario</th><th>Resultado</th></tr></thead><tbody id="recordsBody"><tr><td colspan="4" class="empty">Todavía no hay escaneos.</td></tr></tbody></table></div>
<div class="actions"><button id="syncButton" type="button" class="secondary">Sincronizar registros</button><span id="syncStatus">Los registros se guardan en este dispositivo.</span></div></section>
</main><script src="./app.js" defer></script></body></html>