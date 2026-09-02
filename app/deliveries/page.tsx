import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getSession } from "@/lib/auth";

export const dynamic = "force-dynamic";
export default async function DeliveriesPage(){
 if(!(await getSession())) redirect("/login");
 const [rows]=await db().query("SELECT * FROM delivery WHERE id > 760 ORDER BY id DESC");
 const deliveries=rows as Array<Record<string,unknown>>;
 return <div className="page"><div className="card"><h1>Rutas</h1><p className="muted">{deliveries.length} rutas.</p></div><div className="card"><table className="table"><thead><tr><th>ID</th><th>Nombre</th><th>Estado</th><th>Origen</th><th>Chofer</th><th>Arancel</th><th>Paquetes</th></tr></thead><tbody>{deliveries.map(d=><tr key={String(d.id)}><td>{String(d.id)}</td><td>{String(d.name??"")}</td><td>{String(d.status??"")}</td><td>{String(d.origen??"")}</td><td>{String(d.driver??"")}</td><td>${String(d.total_tariff??"0")}</td><td>{String(d.total_shipments??"0")}</td></tr>)}</tbody></table></div></div>;
}
