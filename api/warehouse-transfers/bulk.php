<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
requireCsrf();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); echo json_encode(['error'=>'Método no permitido']); exit; }
$data=json_decode(file_get_contents('php://input'),true)?:[];
$codes=$data['codes']??null; $direction=(string)($data['direction']??'');
if (!is_array($codes)||count($codes)<1||count($codes)>500||!in_array($direction,['outbound','inbound'],true)) { http_response_code(400); echo json_encode(['error'=>'Lote inválido (máximo 500 códigos).']); exit; }
require_once __DIR__.'/../db_connect.php';
$results=[]; $counts=['registered'=>0,'invalid_status'=>0,'not_found'=>0,'duplicates'=>0];
try {
    foreach(array_chunk(array_values(array_unique(array_map(fn($v)=>trim((string)$v),$codes))),1) as $chunk) {
        $code=$chunk[0]; if($code==='')continue;
        $conn->begin_transaction();
        try {
            $stmt=$conn->prepare('SELECT hbl,status,manifest FROM shipments WHERE hbl=? LIMIT 1 FOR UPDATE'); $stmt->bind_param('s',$code); $stmt->execute(); $s=$stmt->get_result()->fetch_assoc(); $stmt->close();
            if(!$s){$results[]=['code'=>$code,'result'=>'NOT_FOUND','manifest'=>''];$counts['not_found']++;$conn->commit();continue;}
            if($direction==='outbound') {
                if($s['status']!=='warehouse'){$results[]=['code'=>$code,'result'=>'INVALID_STATUS','manifest'=>$s['manifest'],'shipment_status'=>$s['status']];$counts['invalid_status']++;$conn->commit();continue;}
                $uuid=strtolower((string)($data['transfer_uuid']??''));$dest=(int)($data['destination_id']??0);$driver=trim((string)($data['driver']??''));$plate=trim((string)($data['vehicle_plate']??''));
                if(!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',$uuid)||$dest<1||$driver===''||$plate==='')throw new RuntimeException('Datos de transferencia inválidos.');
                $loc=$conn->prepare('SELECT name FROM warehouse_locations WHERE id=? AND is_active=1 AND name <> 'Almacén Arceo & Padrón'');$loc->bind_param('i',$dest);$loc->execute();$l=$loc->get_result()->fetch_assoc();$loc->close();if(!$l)throw new RuntimeException('Destino no disponible.');
                $f=$conn->prepare('SELECT id,destination_id,driver,vehicle_plate FROM warehouse_transfers WHERE transfer_uuid=? FOR UPDATE');$f->bind_param('s',$uuid);$f->execute();$t=$f->get_result()->fetch_assoc();$f->close();
                if(!$t){$origin='Almacén Arceo & Padrón';$date=gmdate('Y-m-d H:i:s');$total=0;$ins=$conn->prepare('INSERT INTO warehouse_transfers (transfer_uuid,transfer_date,username,origin_name,destination_id,destination_name,driver,vehicle_plate,total_shipments) VALUES (?,?,?,?,?,?,?,?,?)');$user=(string)($_SESSION['username']??'unknown');$ins->bind_param('ssssisssi',$uuid,$date,$user,$origin,$dest,$l['name'],$driver,$plate,$total);$ins->execute();$tid=$conn->insert_id;$ins->close();}
                else{$tid=(int)$t['id'];if((int)$t['destination_id']!==$dest||$t['driver']!==$driver||$t['vehicle_plate']!==$plate)throw new RuntimeException('La sesión de transferencia no coincide.');}
                $ins=$conn->prepare('INSERT INTO warehouse_transfer_items (transfer_id,shipment_hbl,manifest_name) VALUES (?,?,?)');$ins->bind_param('iss',$tid,$code,$s['manifest']);$ins->execute();$ins->close();
                $u=$conn->prepare("UPDATE shipments SET status='in_transfer' WHERE hbl=? AND status='warehouse'");$u->bind_param('s',$code);$u->execute();if($u->affected_rows!==1)throw new RuntimeException('El envío cambió de estado durante el proceso.');$u->close();
                $u=$conn->prepare('UPDATE warehouse_transfers SET total_shipments=total_shipments+1 WHERE id=?');$u->bind_param('i',$tid);$u->execute();$u->close();
            } else {
                $uuid=strtolower((string)($data['transfer_uuid']??''));$f=$conn->prepare('SELECT id FROM warehouse_transfers WHERE transfer_uuid=? FOR UPDATE');$f->bind_param('s',$uuid);$f->execute();$t=$f->get_result()->fetch_assoc();$f->close();if(!$t)throw new RuntimeException('Transferencia no encontrada.');
                if($s['status']!=='in_transfer'){$results[]=['code'=>$code,'result'=>'INVALID_STATUS','manifest'=>$s['manifest'],'shipment_status'=>$s['status']];$counts['invalid_status']++;$conn->commit();continue;}
                $tid=(int)$t['id'];$f=$conn->prepare('SELECT id,received_at FROM warehouse_transfer_items WHERE transfer_id=? AND shipment_hbl=? FOR UPDATE');$f->bind_param('is',$tid,$code);$f->execute();$item=$f->get_result()->fetch_assoc();$f->close();if(!$item){$results[]=['code'=>$code,'result'=>'NOT_IN_TRANSFER','manifest'=>$s['manifest'],'shipment_status'=>$s['status']];$counts['invalid_status']++;$conn->commit();continue;}if($item['received_at']!==null){$results[]=['code'=>$code,'result'=>'DUPLICATE','manifest'=>$s['manifest'],'shipment_status'=>$s['status']];$counts['duplicates']++;$conn->commit();continue;}
                $u=$conn->prepare("UPDATE shipments SET status='warehouse' WHERE hbl=? AND status='in_transfer'");$u->bind_param('s',$code);$u->execute();if($u->affected_rows!==1)throw new RuntimeException('El envío cambió de estado.');$u->close();$now=gmdate('Y-m-d H:i:s');$user=(string)($_SESSION['username']??'unknown');$u=$conn->prepare('UPDATE warehouse_transfer_items SET received_at=?,received_by=? WHERE id=?');$iid=(int)$item['id'];$u->bind_param('ssi',$now,$user,$iid);$u->execute();$u->close();
            }
            $results[]=['code'=>$code,'result'=>'FOUND','manifest'=>$s['manifest'],'shipment_status'=>$direction==='outbound'?'in_transfer':'warehouse'];$counts['registered']++;$conn->commit();
        } catch(Throwable $e) {$conn->rollback();throw $e;}
    }
    echo json_encode(['ok'=>true,'counts'=>$counts,'results'=>$results],JSON_UNESCAPED_UNICODE);
} catch(Throwable $error) {error_log('Warehouse transfer bulk error: '.$error->getMessage());http_response_code(400);echo json_encode(['error'=>'No se pudo completar el procesamiento masivo. Revise los datos e inténtelo nuevamente.']);}
