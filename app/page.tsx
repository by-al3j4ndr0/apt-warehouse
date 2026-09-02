import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";

export default async function Home() {
  const session = await getSession();
  if (!session) redirect("/login");
  return <div className="page"><div className="card"><h1>Bienvenido/a {session.firstName}</h1><p className="muted">Panel de administración de APT Warehouse.</p><div className="grid"><a className="btn" href="/clients">Gestionar clientes</a><a className="btn" href="/deliveries">Gestionar rutas</a></div></div></div>;
}
