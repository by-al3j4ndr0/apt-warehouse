import { db } from "@/lib/db";
import { getSession } from "@/lib/auth";
import { NextResponse } from "next/server";

export const runtime = "nodejs";

export async function POST(req: Request) {
  if (!(await getSession())) return NextResponse.json({error:"No autorizado"},{status:401});
  const body=await req.json(); const {name,driver,vehicule,origen,status="draft",clients}=body;
  if(!name||driver==null||vehicule==null||origen==null||!Array.isArray(clients)||!clients.length) return NextResponse.json({error:"name, driver, vehicule, origen y clients son obligatorios"},{status:400});
  const conn=await db().getConnection();
  try { await conn.beginTransaction(); const [r]=await conn.execute("INSERT INTO delivery (name,driver,vehicule,status,shipments,origen) VALUES (?,?,?,?,?,?)",[String(name).trim(),Number(driver),Number(vehicule),String(status),clients.map(String).join(", "),Number(origen)]); const id=(r as {insertId:number}).insertId; for(const ci of clients) { const [u]=await conn.execute("UPDATE shipments SET status=?, route_id=? WHERE ci=? AND status='warehouse'",[String(status),id,String(ci).trim()]); if((u as {affectedRows:number}).affectedRows===0) throw new Error(`Shipment ${ci} not found in warehouse`); } await conn.execute("UPDATE delivery d JOIN (SELECT COUNT(ci) total_shipments,COALESCE(SUM(tariff),0) total_tariff FROM shipments WHERE route_id=?) s ON d.id=? SET d.total_shipments=s.total_shipments,d.total_tariff=s.total_tariff",[id,id]); await conn.commit(); return NextResponse.json({ok:true,id},{status:201}); }
  catch(e){await conn.rollback();console.error(e);return NextResponse.json({error:"No se pudo crear la ruta"},{status:500});} finally{conn.release();}
}
