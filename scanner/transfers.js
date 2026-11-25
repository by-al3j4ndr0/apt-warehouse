(() => {
'use strict';
const $=id=>document.getElementById(id),csrf=document.querySelector('meta[name="csrf-token"]').content;
let transferUuid=null,locationsLoaded=false,transfersLoaded=false;
async function api(url,body=null){const r=await fetch(url,{method:body?'POST':'GET',credentials:'same-origin',headers:body?{'Content-Type':'application/json','X-CSRF-Token':csrf,'Accept':'application/json'}:{'Accept':'application/json'},body:body?JSON.stringify(body):undefined});const d=await r.json();if(!r.ok)throw new Error(d.error||'Error HTTP '+r.status);return d;}
function uuid(){return crypto.randomUUID();}
function feedback(msg,type=''){const el=$('transferFeedback');if(!el)return;el.textContent=msg;el.className='result '+(type||'neutral');}
async function loadLocations(){if(locationsLoaded)return;const d=await api('../api/warehouse-transfers/locations.php'),items=(d.locations||[]).filter(x=>Number(x.is_active)===1),select=$('transferDestination');select.replaceChildren();const first=document.createElement('option');first.value='';first.textContent='Selecciona almacén destino';select.append(first);items.forEach(x=>{const o=document.createElement('option');o.value=x.id;o.textContent=x.name;select.append(o);});locationsLoaded=true;}
async function loadTransfers(){if(transfersLoaded)return;const d=await api('../api/warehouse-transfers/list.php'),items=(d.transfers||[]).filter(t=>Number(t.received_count)<Number(t.total_shipments)),select=$('incomingTransfer');select.replaceChildren();const first=document.createElement('option');first.value='';first.textContent=items.length?'Selecciona una transferencia pendiente':'No hay transferencias pendientes';select.append(first);items.forEach(t=>{const o=document.createElement('option');o.value=t.transfer_uuid;o.textContent=t.destination_name+' · '+t.transfer_date+' · '+t.received_count+'/'+t.total_shipments+' recibidos';select.append(o);});transfersLoaded=true;}
async function updateMode(){const mode=$('transferMode').value,manifest=mode==='manifest',out=mode==='outbound',selected=Boolean(mode);
$('scannerContent').hidden=!selected;
$('manifestLoaderPanel').hidden=!manifest;$('manifestEntryPanel').hidden=!manifest;$('manifestStats').hidden=!manifest;$('manifestHistory').hidden=!manifest;
$('warehouseTransferPanel').hidden=manifest||!selected;
$('outboundFields').hidden=!out;$('inboundFields').hidden=mode!=='inbound';$('transferScanFields').hidden=manifest||!selected;
if(!selected)return;
try{if(out)await loadLocations();if(mode==='inbound')await loadTransfers();if(manifest)await window.initializeManifestMode?.();feedback(out?'Prepara los datos de la salida y comienza a escanear.':mode==='inbound'?'Selecciona la transferencia que vas a recibir.':'Carga un manifiesto para comenzar.');}
catch(e){feedback(e.message,'missing');}
if(!manifest)$('transferScanInput').focus();else $('scanInput').focus();}
async function scan(){const code=$('transferScanInput').value.trim();if(!code)return;const direction=$('transferMode').value,payload={direction,code};if(direction==='outbound'){if(!$('transferDestination').value||!$('transferDriver').value.trim()||!$('transferPlate').value.trim()){feedback('Selecciona destino e indica chofer y matrícula.','missing');return;}transferUuid=transferUuid||uuid();Object.assign(payload,{transfer_uuid:transferUuid,destination_id:Number($('transferDestination').value),driver:$('transferDriver').value.trim(),vehicle_plate:$('transferPlate').value.trim()});}else{if(!$('incomingTransfer').value){feedback('Selecciona la transferencia que vas a recibir.','missing');return;}payload.transfer_uuid=$('incomingTransfer').value;}
$('transferScanButton').disabled=true;try{const d=await api('../api/warehouse-transfers/scan.php',payload);feedback((direction==='outbound'?'Salida registrada: ':'Entrada registrada: ')+d.code,'found');$('transferScanInput').value='';if(direction==='outbound')$('activeTransferUuid').textContent=transferUuid;else{transfersLoaded=false;await loadTransfers();}$('transferScanInput').focus();}catch(e){feedback(e.message,'missing');}finally{$('transferScanButton').disabled=false;}}
$('transferMode').addEventListener('change',updateMode);$('transferScanButton').addEventListener('click',scan);$('transferScanInput').addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();scan();}});
$('finishTransfer').addEventListener('click',()=>{transferUuid=null;$('activeTransferUuid').textContent='—';$('transferDriver').value='';$('transferPlate').value='';$('transferDestination').value='';feedback('Sesión de escaneo cerrada. Los envíos registrados conservan su UUID.');});
$('refreshIncomingTransfers').addEventListener('click',()=>{transfersLoaded=false;loadTransfers().catch(e=>feedback(e.message,'missing'));});
updateMode();
})();
