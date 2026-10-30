'use strict';
const csrfToken = window.APP_CONFIG.csrfToken;
const listElement = document.getElementById('log-list');
const contentElement = document.getElementById('content');
const statusElement = document.getElementById('status');
const metaElement = document.getElementById('selected-meta');
const clearButton = document.getElementById('clear');
const reloadButton = document.getElementById('reload');
let selectedFile = null;

function setStatus(message, isError = false) {
    statusElement.textContent = message;
    statusElement.classList.toggle('error', isError);
}
function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}
async function requestJson(url, options = {}) {
    const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', ...options });
    const data = await response.json();
    if (!response.ok || !data.success) throw new Error(data.error || 'La solicitud no pudo completarse.');
    return data;
}
async function loadLogs() {
    setStatus('Cargando archivos…');
    try {
        const data = await requestJson('../api/logView.php?action=list');
        listElement.replaceChildren();
        data.logs.forEach(log => {
            const item = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'log-item';
            button.disabled = !log.exists;
            button.setAttribute('aria-pressed', String(log.name === selectedFile));
            const name = document.createElement('span');
            name.textContent = log.name;
            const details = document.createElement('span');
            details.className = 'meta';
            details.textContent = log.exists ? formatSize(log.size) + ' · Modificado: ' + (log.modified || 'desconocido') : 'Todavía no existe';
            button.append(name, details);
            button.addEventListener('click', () => loadLog(log.name));
            item.append(button);
            listElement.append(item);
        });
        setStatus(data.logs.length + ' archivo(s) encontrado(s).');
        if (selectedFile && data.logs.some(log => log.name === selectedFile && log.exists)) {
            await loadLog(selectedFile);
        }
    } catch (error) {
        setStatus(error.message, true);
    }
}
async function loadLog(name) {
    selectedFile = name;
    clearButton.disabled = false;
    reloadButton.disabled = false;
    listElement.querySelectorAll('button').forEach(button => {
        button.setAttribute('aria-pressed', String(button.querySelector('span')?.textContent === name));
    });
    metaElement.textContent = name;
    contentElement.textContent = 'Cargando contenido…';
    try {
        const data = await requestJson('?action=read&file=' + encodeURIComponent(name));
        contentElement.textContent = data.content || '(El archivo está vacío.)';
        setStatus('Archivo cargado: ' + name);
    } catch (error) {
        contentElement.textContent = '';
        setStatus(error.message, true);
    }
}
async function clearLog() {
    if (!selectedFile) return;
    const name = selectedFile;
    const confirmed = window.confirm('¿Vaciar permanentemente "' + name + '"? Esta acción no se puede deshacer.');
    if (!confirmed) return;
    clearButton.disabled = true;
    try {
        const body = new URLSearchParams({ action: 'clear', file: name, csrf_token: csrfToken });
        const data = await requestJson(window.location.pathname, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': csrfToken },
            body
        });
        setStatus(data.message);
        await loadLogs();
        await loadLog(name);
    } catch (error) {
        setStatus(error.message, true);
    } finally {
        clearButton.disabled = false;
    }
}
document.getElementById('refresh').addEventListener('click', loadLogs);
reloadButton.addEventListener('click', () => selectedFile && loadLog(selectedFile));
clearButton.addEventListener('click', clearLog);
loadLogs();