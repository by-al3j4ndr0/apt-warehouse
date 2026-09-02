import { NextResponse } from "next/server";
import { db } from "@/lib/db";
import { getSession } from "@/lib/auth";

export const runtime = "nodejs";
export async function GET(){
 if(!(await getSession())) return NextResponse.json({error:"No autorizado"},{status:401});
 const [rows]=await db().query("SELECT * FROM delivery WHERE id > 760 ORDER BY id DESC");
 return NextResponse.json(rows);
}
