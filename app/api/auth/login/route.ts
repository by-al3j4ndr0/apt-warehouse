import { NextResponse } from "next/server";
import { db } from "@/lib/db";
import { createSession, verifyLegacyPassword } from "@/lib/auth";

export const runtime = "nodejs";
export async function POST(req: Request) {
  try {
    const { username, password } = await req.json();
    if (typeof username !== "string" || typeof password !== "string" || !username || !password) return NextResponse.json({error:"Usuario y contraseña son obligatorios"},{status:400});
    const [rows] = await db().execute("SELECT password, first_name, last_name FROM auth_user WHERE username = ? LIMIT 1", [username]);
    const row = (rows as Array<{password:string;first_name:string;last_name:string}>)[0];
    if (!row || !verifyLegacyPassword(password, row.password)) return NextResponse.json({error:"Usuario o contraseña incorrectos"},{status:401});
    await createSession({username, firstName:row.first_name, lastName:row.last_name});
    return NextResponse.json({ok:true});
  } catch (error) { console.error(error); return NextResponse.json({error:"Error interno de autenticación"},{status:500}); }
}
