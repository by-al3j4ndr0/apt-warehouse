import Link from "next/link";
import { getSession } from "@/lib/auth";
import "./globals.css";

export const metadata = { title: "APT Warehouse", description: "Warehouse and delivery management" };

export default async function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  const session = await getSession();
  return <html lang="es"><body>
    {session && <header className="nav"><strong>APT Warehouse</strong><nav><Link href="/">Inicio</Link><Link href="/clients">Clientes</Link><Link href="/deliveries">Rutas</Link><form action="/api/auth/logout" method="post"><button>Cerrar sesión</button></form></nav></header>}
    <main>{children}</main>
  </body></html>;
}
