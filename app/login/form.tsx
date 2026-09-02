"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";

export default function LoginForm(){
 const router=useRouter(); const [username,setUsername]=useState(""); const [password,setPassword]=useState(""); const [error,setError]=useState(""); const [loading,setLoading]=useState(false);
 async function submit(e:React.FormEvent){e.preventDefault();setLoading(true);setError("");const r=await fetch("/api/auth/login",{method:"POST",headers:{"Content-Type":"application/json"},body:JSON.stringify({username,password})});const data=await r.json();if(!r.ok){setError(data.error||"No se pudo iniciar sesión");setLoading(false);return}router.push("/");router.refresh();}
 return <form onSubmit={submit}><label>Nombre de usuario<input className="input" value={username} onChange={e=>setUsername(e.target.value)} autoComplete="username" required/></label><label>Contraseña<input className="input" type="password" value={password} onChange={e=>setPassword(e.target.value)} autoComplete="current-password" required/></label>{error&&<p className="error">{error}</p>}<button className="btn" disabled={loading}>{loading?"Entrando…":"Iniciar sesión"}</button></form>;
}
