(() => {
"use strict";
const $ = id => document.getElementById(id);
const DB_NAME = "aptWarehouseScanner";
const DB_VERSION = 1;
let db, rows = [], manifest = "", processing = false;

function openDb() {
 return new Promise((resolve,reject)=>{
  const req=indexedDB.open(DB_NAME,DB_VERSION);
  req.onupgradeneeded=()=>{
   const d=req.result;
   if(!d.objectStoreNames.contains("manifests"))d.createObjectStore("manifests",{keyPath:"name"});
   if(!d.objectStoreNames.contains("scans")){const s=d.createObjectStore("scans",{keyPath:"id"});s.createIndex("manifest","manifest",{unique:false});}
  };
  req.onsuccess=()=>resolve(req.result);req.onerror=()=>reject(req.error);
 });
}
function requestResult(req){return new Promise((resolve,reject)=>{req.onsuccess=()=>resolve(req.result);req.onerror=()=>reject(req.error);});}
function store(name,mode="readonly"){return db.transaction(name,mode).objectStore(name);}
function normalize(v){return String(v??"").replace(/^\uFEFF/,"").trim().toLocaleLowerCase("es").normalize("NFD").replace(/[\u0300-\u036f]/g,"").replace(/[^a-z0-9]/g,"");}
function parseCsv(text){
 const first=text.split(/\r?\n/,1)[0],delimiter=(first.match(/;/g)||[]).length>(first.match(/,/g)||[]).length?";":",";
 const out=[];let row=[],cell="",quoted=false;
 for(let i=0;i<text.length;i++){
  const c=text[i];
  if(c==='"'){if(quoted&&text[i+1]==='"'){cell+='"';i++;}else quoted=!quoted;}
  else if(c===delimiter&&!quoted){row.push(cell);cell="";}
  else if((c==="\n"||c==="\r")&&!quoted){if(c==="\r"&&text[i+1]==="\n")i++;row.push(cell);if(row.some(v=>v.trim()!==""))out.push(row);row=[];cell="";}
  else cell+=c;
 }
 row.push(cell);if(row.some(v=>v.trim()!==""))out.push(row);
 if(out.length<2)throw new Error("El CSV no contiene filas de datos.");
 const headers=out.shift().map(normalize),find=names=>headers.findIndex(h=>names.includes(h));
 const ix={code:find(["codigoenvio","codigo","hbl","codigodelenvio","shipmentcode","tracking"]),recipient:find(["destinatario","name","nombre","cliente"]),municipality:find(["municipio","city"]),province:find(["provincia","state"])};
 if(ix.code<0)throw new Error("No se encontró la columna del código. Usa codigo, codigo del envio o hbl.");
 const items=new Map();
 out.forEach(line=>{const code=String(line[ix.code]||"").trim();if(code)items.set(code,{code,recipient:ix.recipient<0?"":String(line[ix.recipient]||"").trim(),municipality:ix.municipality<0?"":String(line[ix.municipality]||"").trim(),province:ix.province<0?"":String(line[ix.province]||"").trim()});});
 if(!items.size)throw new Error("No se encontraron códigos válidos en el CSV.");
 return [...items.values()];
}
async function loadManifest(name){
 const saved=await requestResult(store("manifests").get(name));if(!saved)return false;
 manifest=name;rows=saved.rows;$("manifestName").value=name;$("fileName").textContent=saved.fileName+" · "+rows.length+" códigos";
 $("manifestStatus").textContent="Listado activo: "+name+" ("+rows.length+" códigos).";enableScanner(true);await render();return true;
}
async function saveManifest(name,data,fileName){
 await requestResult(store("manifests","readwrite").put({name,rows:data,fileName,updatedAt:new Date().toISOString()}));
 manifest=name;rows=data;$("manifestStatus").textContent="Listado cargado: "+name+" · "+rows.length+" códigos.";
 $("fileName").textContent=fileName+" · "+rows.length+" códigos";enableScanner(true);await render();
}
function enableScanner(enabled){$("scanInput").disabled=!enabled;$("scanButton").disabled=!enabled;if(enabled)$("scanInput").focus();}
async function getScans(){const all=await requestResult(store("scans").getAll());return all.filter(s=>s.manifest===manifest).sort((a,b)=>b.timestamp.localeCompare(a.timestamp));}
async function render(){
 const scans=await getScans(),seen=new Set(scans.filter(s=>s.status!=="NOT_FOUND").map(s=>s.code));
 const found=scans.filter(s=>s.status==="FOUND").length,missing=scans.filter(s=>s.status==="NOT_FOUND").length,duplicates=scans.filter(s=>s.status==="DUPLICATE").length;
 $("countFound").textContent=found;$("countMissing").textContent=missing;$("countDuplicate").textContent=duplicates;$("countPending").textContent=Math.max(0,rows.length-seen.size);
 $("recordCount").textContent=scans.length+" escaneos registrados";
 const body=$("recordsBody");body.replaceChildren();
 if(!scans.length){const tr=document.createElement("tr"),td=document.createElement("td");td.colSpan=4;td.className="empty";td.textContent="Todavía no hay escaneos.";tr.append(td);body.append(tr);return;}
 scans.slice(0,30).forEach(s=>{
  const tr=document.createElement("tr");
  [new Date(s.timestamp).toLocaleTimeString("es",{hour:"2-digit",minute:"2-digit"}),s.code,s.recipient||"—"].forEach(v=>{const td=document.createElement("td");td.textContent=v;tr.append(td);});
  const td=document.createElement("td"),tag=document.createElement("span");tag.className="tag "+(s.status==="FOUND"?"found":s.status==="DUPLICATE"?"duplicate":"missing");
  tag.textContent=s.status==="FOUND"?"ENCONTRADO":s.status==="DUPLICATE"?"DUPLICADO":"NO ENCONTRADO";td.append(tag);tr.append(td);body.append(tr);
 });
}
async function scan(code){
 code=String(code||"").trim();if(!code||processing||!manifest)return;processing=true;$("scanInput").value="";
 try{
  const match=rows.find(item=>item.code===code),all=await requestResult(store("scans").getAll()),already=all.some(s=>s.code===code&&s.status!=="NOT_FOUND");
  const status=!match?"NOT_FOUND":already?"DUPLICATE":"FOUND";
  const record={id:crypto.randomUUID?crypto.randomUUID():String(Date.now())+"-"+Math.random(),manifest,code,status,recipient:match?.recipient||"",municipality:match?.municipality||"",province:match?.province||"",timestamp:new Date().toISOString(),synced:false};
  await requestResult(store("scans","readwrite").put(record));
  const result=$("result");result.className="result "+(status==="FOUND"?"found":status==="DUPLICATE"?"duplicate":"missing");
  result.textContent=status==="FOUND"?"✓ CÓDIGO ENCONTRADO":status==="DUPLICATE"?"⚠ ESTE CÓDIGO YA FUE ESCANEADO":"✕ CÓDIGO NO INCLUIDO EN EL LISTADO";
  const info=$("shipmentInfo");info.hidden=!match;info.replaceChildren();
  if(match)[["Destinatario",match.recipient],["Municipio",match.municipality],["Provincia",match.province],["Código",match.code]].forEach(pair=>{const d=document.createElement("div"),sp=document.createElement("span"),b=document.createElement("b");sp.textContent=pair[0];b.textContent=pair[1]||"—";d.append(sp,b);info.append(d);});
  await render();
 }finally{processing=false;$("scanInput").focus();}
}
function csvEscape(v){return '"'+String(v??"").replace(/"/g,'""')+'"';}
async function exportCsv(){
 const scans=await getScans(),headers=["codigo","destinatario","municipio","provincia","resultado","fecha_hora","manifiesto"];
 const lines=[headers.join(";"),...scans.map(s=>[s.code,s.recipient,s.municipality,s.province,s.status,s.timestamp,s.manifest].map(csvEscape).join(";"))];
 const blob=new Blob(["\uFEFF"+lines.join("\r\n")],{type:"text/csv;charset=utf-8"}),url=URL.createObjectURL(blob),a=document.createElement("a");
 a.href=url;a.download="escaneos-"+normalize(manifest)+".csv";a.click();URL.revokeObjectURL(url);
}
async function sync(){
 if(!navigator.onLine){$("syncStatus").textContent="Sin conexión. Los registros permanecen guardados en el dispositivo.";return;}
 const pending=(await getScans()).filter(s=>!s.synced);if(!pending.length){$("syncStatus").textContent="No hay registros pendientes de sincronización.";return;}
 $("syncStatus").textContent="Sincronizando "+pending.length+" registros…";
 try{
  const response=await fetch("../api/scanner/sync.php",{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json","Accept":"application/json","X-CSRF-Token":document.querySelector('meta[name="csrf-token"]').content},body:JSON.stringify({records:pending})});
  const data=await response.json();if(!response.ok)throw new Error(data.error||"Error HTTP "+response.status);
  const tx=db.transaction("scans","readwrite"),os=tx.objectStore("scans");pending.forEach(s=>{s.synced=true;os.put(s);});
  tx.oncomplete=()=>$("syncStatus").textContent="Sincronización completada. Nuevos registros: "+data.saved+".";
 }catch(error){$("syncStatus").textContent="No se pudo sincronizar: "+error.message+" (se conservaron localmente).";}
}
async function init(){
 db=await openDb();
 $("csvFile").addEventListener("change",async e=>{const file=e.target.files[0];if(!file)return;const name=$("manifestName").value.trim()||file.name.replace(/\.csv$/i,"");
  try{await saveManifest(name,parseCsv(await file.text()),file.name);$("result").className="result neutral";$("result").textContent="Listado listo. Puedes comenzar a escanear.";}catch(err){$("manifestStatus").textContent=err.message;}e.target.value="";
 });
 $("scanButton").addEventListener("click",()=>scan($("scanInput").value));
 $("scanInput").addEventListener("keydown",e=>{if(e.key==="Enter"){e.preventDefault();scan($("scanInput").value);}});
 $("exportButton").addEventListener("click",exportCsv);$("syncButton").addEventListener("click",sync);
 $("manifestName").addEventListener("change",async()=>{const name=$("manifestName").value.trim();if(name)await loadManifest(name);});
 const connection=()=>{$("connection").textContent=navigator.onLine?"En línea":"Sin conexión";$("connection").style.background=navigator.onLine?"#ffffff20":"#8a5a00";};
 window.addEventListener("online",connection);window.addEventListener("offline",connection);connection();
 const names=await requestResult(store("manifests").getAllKeys());if(names.length)await loadManifest(names[names.length-1]);
 if("serviceWorker"in navigator)navigator.serviceWorker.register("./sw.js").catch(()=>{});
}
document.addEventListener("DOMContentLoaded",()=>init().catch(e=>{$("manifestStatus").textContent="Error al iniciar almacenamiento local: "+e.message;}));
})();