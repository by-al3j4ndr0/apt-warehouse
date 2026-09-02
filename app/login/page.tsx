import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import LoginForm from "./form";

export default async function LoginPage() {
  if (await getSession()) redirect("/");
  return <div className="page"><div className="card login"><h2>Iniciar sesión</h2><LoginForm /></div></div>;
}
