<?php
require_once __DIR__ . '/../api/auth.php';
requireAdmin();
$csrf = csrfToken();
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
<title>Almacenes de destino</title>
<link rel="stylesheet" href="../resources/css/custom.css"><link rel="stylesheet" href="../resources/css/bootstrap.min.css">
<script src="../resources/js/bootstrap.bundle.min.js"></script></head><body>
<?php include '../header.php'; ?>
<main class="container py-4"><h2>Almacenes de destino</h2>
<p class="text-muted">Administra los destinos disponibles para transferencias desde Almacén Arceo &amp; Padrón.</p>
<div class="card p-3 mb-3"><label for="locationName" class="form-label">Nombre del almacén</label>
<div class="d-flex gap-2 flex-wrap"><input id="locationName" class="form-control" maxlength="150" placeholder="Nombre del almacén" style="max-width:520px"><button id="saveLocation" class="btn btn-primary">Agregar almacén</button><button id="cancelEdit" class="btn btn-outline-secondary" hidden>Cancelar</button></div><div id="locationFeedback" class="small mt-2" role="status"></div></div>
<div class="table-responsive"><table class="table table-striped"><thead><tr><th>Almacén</th><th>Estado</th><th>Acciones</th></tr></thead><tbody id="locationsBody"><tr><td colspan="3">Cargando…</td></tr></tbody></table></div></main>
<script>
(()=>{const $=id=>document.getElementById(id),csrf=document.querySelector('meta[name="csrf-token"]').content;let editing=0;
async function request(body=null){const r=await fetch('../api/warehouse-transfers/locations.php',{method:body?'POST':'GET',credentials:'same-origin',headers:body?{'Content-Type':'application/json','X-CSRF-Token':csrf,'Accept':'application/json'}:{'Accept':'application/json'},body:body?JSON.stringify(body):undefined});const d=await r.json();if(!r.ok)throw new Error(d.error||'Error HTTP '+r.status);return d;}
function reset(){editing=0;$('locationName').value='';$('saveLocation').textContent='Agregar almacén';$('cancelEdit').hidden=true;}
async function load(){try{const d=await request(),body=$('locationsBody');body.replaceChildren();if(!d.locations.length){body.innerHTML='<tr><td colspan="3">No hay almacenes configurados.</td></tr>';return;}d.locations.forEach(item=>{const tr=document.createElement('tr'),name=document.createElement('td'),status=document.createElement('td'),actions=document.createElement('td');name.textContent=item.name;status.textContent=Number(item.is_active)===1?'Activo':'Inactivo';const edit=document.createElement('button');edit.className='btn btn-sm btn-outline-primary me-2';edit.textContent='Editar';edit.onclick=()=>{editing=Number(item.id);$('locationName').value=item.name;$('saveLocation').textContent='Guardar cambios';$('cancelEdit').hidden=false;$('locationName').focus();};const toggle=document.createElement('button');toggle.className='btn btn-sm btn-outline-secondary';toggle.textContent=Number(item.is_active)===1?'Desactivar':'Activar';toggle.onclick=async()=>{if(!confirm((Number(item.is_active)===1?'¿Desactivar ':'¿Activar ')+item.name+'?'))return;try{await request({action:'toggle',id:item.id,is_active:Number(item.is_active)===1?0:1});await load();}catch(e){$('locationFeedback').textContent=e.message;}};actions.append(edit,toggle);tr.append(name,status,actions);body.append(tr);});}catch(e){$('locationFeedback').textContent=e.message;}}
$('saveLocation').onclick=async()=>{try{await request({action:'save',id:editing,name:$('locationName').value.trim()});$('locationFeedback').textContent='Almacén guardado.';reset();await load();}catch(e){$('locationFeedback').textContent=e.message;}};$('cancelEdit').onclick=reset;load();})();
</script></body></html>