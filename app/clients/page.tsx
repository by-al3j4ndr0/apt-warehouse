import { redirect } from "next/navigation";
import { db } from "@/lib/db";
import { getSession } from "@/lib/auth";

export const dynamic = "force-dynamic";
export default async function ClientsPage(){
 if(!(await getSession())) redirect("/login");
 const [rows]=await db().query("SELECT * FROM clients ORDER BY name");
 const clients=rows as Array<Record<string,unknown>>;
 return <div className="page"><div className="card"><h1>Clientes</h1><p className="muted">{clients.length} clientes registrados.</p></div><div className="card"><table className="table"><thead><tr><th>CI</th><th>Nombre</th><th>Teléfono</th><th>Dirección</th><th>Ciudad</th></tr></thead><tbody>{clients.map(c=><tr key={String(c.ci)}><td>{String(c.ci??"")}</td><td>{String(c.name??"")}</td><td>{String(c.phone??"")}</td><td>{String(c.address??"")}</td><td>{String(c.city??"")}</td></tr>)}</tbody></table></div></div>;
}
