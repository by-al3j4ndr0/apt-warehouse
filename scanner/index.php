<?php
require_once __DIR__ . '/../api/auth.php';
requireLogin(true);
$csrf = csrfToken();
?>
<!doctype html>
<html lang="es"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#123b5d"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes"><link rel="icon" href="./icons/icon.svg" type="image/svg+xml"><meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<title>Control QR | APT Warehouse</title><link rel="manifest" href="./manifest.webmanifest"><link rel="stylesheet" href="./style.css">
</head><body>
<header class="topbar"><div><strong>APT Warehouse</strong><span>Control de mercancías</span></div><div class="topbar-actions"><button id="installButton" type="button" hidden>Instalar app</button><span id="connection" class="pill">Comprobando conexión…</span></div></header>
<main>
<section class="panel operation-picker"><div class="section-head"><div><h1>Control QR</h1><p>Selecciona qué operación vas a realizar para mostrar sus herramientas.</p></div></div><label class="field-label" for="transferMode">Tipo de operación</label><select id="transferMode" class="form-control"><option value="" selected>Selecciona una opción para comenzar…</option><option value="manifest">Entrada de mercancía (manifiesto)</option><option value="outbound">Salida a otro almacén</option><option value="inbound">Entrada / recepción de transferencia</option></select></section>
<div id="scannerContent" hidden>
<section class="panel" id="manifestLoaderPanel"><div class="section-head"><div><h1>Verificación QR</h1><p>Selecciona un manifiesto del sistema o importa un CSV.</p></div></div>
<label class="field-label" for="systemManifestSelect">Manifiestos disponibles en apt-warehouse</label>
<div class="scan-row"><select id="systemManifestSelect"><option value="">Cargando manifiestos…</option></select><button id="loadSystemManifest" type="button">Cargar</button></div>
<p class="hint" id="systemManifestStatus">Se mostrarán manifiestos con mercancía pendiente o ya recibida en almacén.</p>
<div class="manifest-divider">O utiliza un archivo CSV</div>
<label class="field-label" for="manifestName">Nombre de la sesión / manifiesto CSV</label><input id="manifestName" type="text" placeholder="Ej. Manifiesto 20-11-2026" maxlength="255">
<label class="file-picker" for="csvFile"><span>＋</span><span><b>Seleccionar archivo CSV</b><small id="fileName">Código, destinatario, municipio y provincia</small></span></label><input id="csvFile" type="file" accept=".csv,text/csv" hidden>
<div class="manifest-status" id="manifestStatus">No hay ningún listado cargado.</div></section>
<section class="panel scanner-panel" id="manifestEntryPanel"><div class="section-head"><div><h2>Escanear mercancía</h2><p>Conecta el lector Bluetooth en modo teclado.</p></div><span class="scan-icon">▦</span></div>
<label class="field-label" for="scanInput">Código QR</label><div class="scan-row"><input id="scanInput" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Esperando escaneo…" disabled><button id="scanButton" type="button" disabled>Verificar</button></div>
<div id="result" class="result neutral" role="status" aria-live="polite">Selecciona un manifiesto o carga un CSV para habilitar el escaneo.</div><div id="shipmentInfo" class="shipment-info" hidden></div>
<p class="hint">El código se procesa automáticamente al terminar la lectura del escáner o tras dejar de escribir. También puedes pulsar Verificar.</p></section>
<section class="stats-grid" id="manifestStats" aria-label="Resumen">
<div class="stat"><b id="countFound">0</b><span>Encontrados</span></div><div class="stat"><b id="countMissing">0</b><span>No encontrados</span></div><div class="stat"><b id="countDuplicate">0</b><span>Duplicados</span></div><div class="stat"><b id="countPending">0</b><span>Pendientes</span></div></section>
<section class="panel" id="manifestHistory"><div class="section-head"><div><h2>Últimos registros</h2><p id="recordCount">0 escaneos registrados</p></div><button id="exportButton" class="secondary" type="button">Exportar CSV</button></div>
<div class="table-wrap"><table><thead><tr><th>Hora</th><th>Código</th><th>Destinatario</th><th>Resultado</th></tr></thead><tbody id="recordsBody"><tr><td colspan="4" class="empty">Todavía no hay escaneos.</td></tr></tbody></table></div>
<div class="actions"><button id="syncButton" type="button" class="secondary">Sincronizar registros</button><span id="syncStatus">Los registros se guardan en este dispositivo.</span></div></section>
<section class="panel" id="warehouseTransferPanel">
<div class="section-head"><div><h2>Transferencias entre almacenes</h2><p>Registra salidas y recibe mercancía enviada desde Almacén Arceo &amp; Padrón.</p></div></div>
<div id="outboundFields">
<label class="field-label" for="transferDestination">Almacén de destino</label><select id="transferDestination" class="form-control"><option value="">Cargando almacenes…</option></select>
<label class="field-label" for="transferDriver">Chofer</label><input id="transferDriver" type="text" maxlength="180" placeholder="Nombre del chofer">
<label class="field-label" for="transferPlate">Matrícula</label><input id="transferPlate" type="text" maxlength="40" placeholder="Matrícula del vehículo">
<p class="hint">UUID de la transferencia activa: <strong id="activeTransferUuid">—</strong></p>
<button id="finishTransfer" class="secondary" type="button">Cerrar sesión de escaneo</button>
</div>
<div id="inboundFields" hidden>
<label class="field-label" for="incomingTransfer">Transferencia pendiente de recepción</label><div class="scan-row"><select id="incomingTransfer" class="form-control"></select><button id="refreshIncomingTransfers" type="button" class="secondary">Actualizar</button></div>
</div>
<div id="transferScanFields">
<label class="field-label" id="transferScanLabel" for="transferScanInput">Código HBL</label>
<div class="scan-row"><input id="transferScanInput" type="text" autocomplete="off" autocapitalize="off" spellcheck="false" placeholder="Escanea o escribe el código HBL"><button id="transferScanButton" type="button">Registrar escaneo</button></div>
<div id="transferFeedback" class="result neutral" role="status" aria-live="polite">Listo para escanear.</div>
</div></section>
</div></main><script src="./app.js" defer></script><script src="./transfers.js" defer></script></body></html>